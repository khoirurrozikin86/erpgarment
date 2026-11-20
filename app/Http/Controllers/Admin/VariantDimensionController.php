<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\Services\AuditLogService;
use App\Http\Controllers\Controller;
use App\Models\VariantDimension;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VariantDimensionController extends Controller
{
    public function index(Request $request, string $kind): View
    {
        $entries = VariantDimension::options($kind)->with('aliases')->paginate(25);
        $entry = null;
        if ($request->filled('edit') && $request->user()->can('products.update')) {
            $entry = VariantDimension::options($kind)->with('aliases')->findOrFail($request->integer('edit'));
        }

        return view('super.variant-dimensions.index', compact('kind', 'entries', 'entry'));
    }

    public function save(Request $request, string $kind, string $id = ''): RedirectResponse
    {
        $data = $request->validate([
            'code' => [$id === '' ? 'required' : 'nullable', 'string', 'max:'.($kind === 'color' ? 80 : 40)],
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
            'aliases' => ['nullable', 'string', 'max:2000'],
        ]);
        $aliases = collect(explode(',', $data['aliases'] ?? ''))->map(VariantDimension::normalize(...))->filter(fn ($alias) => $alias !== '')->unique();
        foreach ($aliases as $alias) {
            if (mb_strlen($alias) > ($kind === 'color' ? 80 : 40)) {
                throw ValidationException::withMessages(['aliases' => __('variants.dimension_alias_too_long')]);
            }
        }

        try {
            DB::transaction(function () use ($kind, $id, $data, $aliases) {
                $entry = $id === '' ? new VariantDimension(['kind' => $kind, 'code' => VariantDimension::normalize($data['code'])])
                    : VariantDimension::options($kind)->lockForUpdate()->findOrFail($id);
                $before = $entry->exists ? $entry->load('aliases')->toArray() : null;
                $entry->fill(['name' => $data['name'], 'sort_order' => $data['sort_order']])->save();

                $codes = $aliases->reject(fn ($alias) => $alias === $entry->code);
                $entry->aliases()->whereNotIn('code', $codes->all())->delete();
                foreach ($codes as $code) {
                    $entry->aliases()->firstOrCreate(['kind' => $kind, 'code' => $code]);
                }

                app(AuditLogService::class)->log(
                    action: $before ? 'UPDATE' : 'CREATE',
                    module: 'VARIANT_DIMENSION',
                    description: $kind.' '.$entry->code,
                    model: $entry,
                    oldValues: $before,
                    newValues: $entry->load('aliases')->toArray(),
                );
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['aliases' => __('variants.dimension_code_conflict')]);
        }

        return redirect()->route('super.variant-dimensions.index', $kind)->with('success', __('variants.dimension_saved'));
    }
}
