<?php

namespace App\Domain\Manufacturing\Services;

use App\Models\BillOfMaterial;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ResolveProductionBom
{
    public function resolve(int $companyId, int $productId, string $date, ?int $bomId = null, ?string $styleCode = null): BillOfMaterial
    {
        $product = Product::findOrFail($productId);
        $styleCode ??= $product->style_code ?? $product->parentProduct?->style_code;
        $ids = array_filter([$product->id, $product->parent_product_id]);
        $boms = BillOfMaterial::where('company_id', $companyId)->where(fn ($q) => $q->whereIn('product_id', $ids)->when(filled($styleCode), fn ($query) => $query->orWhere(fn ($style) => $style->whereNull('product_id')->whereRaw('LOWER(style_code) = ?', [mb_strtolower(trim($styleCode))]))))->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date))
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))
            ->when($bomId, fn ($q) => $q->whereKey($bomId))->with('items.product.unit')->orderBy('id')->lockForUpdate()->get();
        $productBoms = $boms->where('product_id', $product->id);
        $parentBoms = $product->parent_product_id ? $boms->where('product_id', $product->parent_product_id) : collect();
        $styleBoms = $boms->filter(fn ($candidate) => $candidate->product_id === null && filled($candidate->style_code));
        $candidates = $productBoms->isNotEmpty() ? $productBoms : ($parentBoms->isNotEmpty() ? $parentBoms : $styleBoms);
        if (!$bomId && $candidates->count() > 1) {
            throw ValidationException::withMessages(['bill_of_material_id' => __('variants.bom_selection_required')]);
        }
        $bom = $candidates->first();
        if ($bom) {
            // A size-specific material must not disappear silently from an otherwise matching color.
            $colorItems = $bom->items->filter(fn ($item) => trim($item->applies_to_color ?? '') === ''
                || mb_strtolower(trim($item->applies_to_color)) === mb_strtolower(trim($product->color ?? '')));
            foreach ($colorItems->groupBy(fn ($item) => $item->product_id.'|'.($item->operation ?? '')) as $group) {
                $hasSize = $group->contains(fn ($item) => trim($item->applies_to_size ?? '') === ''
                    || mb_strtolower(trim($item->applies_to_size)) === mb_strtolower(trim($product->size ?? '')));
                if (!$hasSize) {
                    throw ValidationException::withMessages(['bill_of_material_id' => __('variants.bom_missing_size', [
                        'item' => $group->first()->product?->code, 'color' => $product->color ?: '—', 'size' => $product->size ?: '—',
                    ])]);
                }
            }
            $bom->setRelation('items', $bom->items->filter(function ($item) use ($product) {
                foreach (['color', 'size'] as $dimension) {
                    $selector = trim($item->{'applies_to_'.$dimension} ?? '');
                    if ($selector !== '' && mb_strtolower($selector) !== mb_strtolower(trim($product->$dimension ?? ''))) return false;
                }
                return true;
            })->values());
        }
        if (!$bom || (float) $bom->output_quantity <= 0 || $bom->items->isEmpty()) {
            throw ValidationException::withMessages(['output_product_id' => __('ui.workflow.active_bom_not_found')]);
        }
        return $bom;
    }
}
