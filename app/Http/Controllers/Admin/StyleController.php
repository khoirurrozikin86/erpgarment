<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Styles\Queries\StyleTableQuery;
use App\Domain\Styles\Services\StyleService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StyleStoreRequest;
use App\Http\Requests\Admin\StyleUpdateRequest;
use App\Models\Product;
use App\Models\Style;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StyleController extends Controller
{
    public function index()
    {
        return view('super.styles.index');
    }

    public function dt(StyleTableQuery $query)
    {
        return DataTables::eloquent($query->builder())
            ->addColumn('actions', function (Style $style) {
                $actions = [];
                if (auth()->user()?->can('styles.update')) {
                    $actions[] = [
                        'type' => 'edit', 'label' => __('ui.common.edit'), 'icon' => 'edit-2',
                        'update_url' => route('super.styles.update', $style->getRouteKey()),
                        'payload' => $style->only(['id', 'code', 'name']),
                    ];
                }
                if (auth()->user()?->can('styles.delete')) {
                    $actions[] = [
                        'type' => 'delete', 'url' => route('super.styles.destroy', $style->getRouteKey()),
                        'label' => __('ui.common.delete'), 'icon' => 'trash-2',
                        'confirm' => __('ui.master.delete_confirmation', ['type' => 'Style', 'name' => $style->code]),
                        'disabled' => false,
                    ];
                }

                return view('admin.partials.table-actions', compact('actions'))->render();
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function store(StyleStoreRequest $request, StyleService $service)
    {
        $style = $service->create($request->validated());

        return $request->ajax() || $request->expectsJson()
            ? response()->json(['message' => 'Style created', 'id' => $style->id], 201)
            : back()->with('success', 'Style created');
    }

    public function update(StyleUpdateRequest $request, Style $style, StyleService $service)
    {
        if ($request->validated('code') !== $style->code && Product::query()->where('style_code', $style->code)->exists()) {
            return response()->json(['message' => 'Kode style sudah dipakai barang dan tidak dapat diubah.'], 422);
        }

        $service->update($style, $request->validated());

        return $request->ajax() || $request->expectsJson()
            ? response()->json(['message' => 'Style updated'])
            : back()->with('success', 'Style updated');
    }

    public function destroy(Style $style, StyleService $service)
    {
        if (Product::query()->where('style_code', $style->code)->exists()) {
            return response()->json(['message' => __('ui.master.in_use_cannot_delete', ['type' => 'Style'])], 422);
        }

        $service->delete($style);

        return request()->ajax() || request()->expectsJson()
            ? response()->json(['message' => 'Style deleted'])
            : redirect()->route('super.styles.index')->with('success', 'Style deleted');
    }
}
