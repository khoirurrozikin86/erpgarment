<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Importers\Queries\ImporterTableQuery;
use App\Domain\Importers\Services\ImporterService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImporterStoreRequest;
use App\Http\Requests\Admin\ImporterUpdateRequest;
use App\Models\Importer;
use Yajra\DataTables\Facades\DataTables;

class ImporterController extends Controller
{
    public function index()
    {
        return view('super.importers.index');
    }

    public function dt(ImporterTableQuery $query)
    {
        return DataTables::eloquent($query->builder())
            ->addColumn('status', fn (Importer $importer) => $importer->is_active ? __('ui.master.status_active') : __('ui.master.status_inactive'))
            ->addColumn('actions', function (Importer $importer) {
                $actions = [];
                if (auth()->user()->can('importers.update')) {
                    $actions[] = [
                        'type' => 'edit',
                        'label' => __('ui.common.edit'),
                        'icon' => 'edit-2',
                        'update_url' => route('super.importers.update', $importer),
                        'payload' => $importer->only(['id', 'tax_number', 'name', 'address', 'apip', 'is_active']),
                    ];
                }
                if (auth()->user()->can('importers.delete')) {
                    $actions[] = [
                        'type' => 'delete',
                        'url' => route('super.importers.destroy', $importer),
                        'label' => __('ui.common.delete'),
                        'icon' => 'trash-2',
                        'confirm' => __('ui.master.delete_importer_confirmation'),
                        'disabled' => false,
                    ];
                }

                return view('admin.partials.table-actions', compact('actions'))->render();
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function store(ImporterStoreRequest $request, ImporterService $service)
    {
        $importer = $service->create($request->validated());

        return response()->json(['message' => __('ui.master.importer_created'), 'id' => $importer->id], 201);
    }

    public function update(ImporterUpdateRequest $request, Importer $importer, ImporterService $service)
    {
        $service->update($importer, $request->validated());

        return response()->json(['message' => __('ui.master.importer_updated')]);
    }

    public function destroy(Importer $importer, ImporterService $service)
    {
        $service->delete($importer);

        return response()->json(['message' => __('ui.master.importer_deleted')]);
    }
}
