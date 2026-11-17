<?php

namespace App\Domain\Manufacturing\Services;

use App\Models\BillOfMaterial;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ResolveProductionBom
{
    public function resolve(int $companyId, int $productId, string $date): BillOfMaterial
    {
        $product = Product::findOrFail($productId);
        $ids = array_filter([$product->id, $product->parent_product_id]);
        $boms = BillOfMaterial::where('company_id', $companyId)->whereIn('product_id', $ids)->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date))
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))
            ->with('items.product.unit')->lockForUpdate()->get();
        $bom = $boms->firstWhere('product_id', $product->id) ?? $boms->firstWhere('product_id', $product->parent_product_id);
        if ($bom) {
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
