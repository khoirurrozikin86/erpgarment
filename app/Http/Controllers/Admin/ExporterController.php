<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Exporters\Queries\ExporterTableQuery;
use App\Domain\Exporters\Services\ExporterService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExporterStoreRequest;
use App\Http\Requests\Admin\ExporterUpdateRequest;
use App\Models\Exporter;
use Yajra\DataTables\Facades\DataTables;

class ExporterController extends Controller
{
    public function index()
    {
        return view('super.exporters.index');
    }

    public function dt(ExporterTableQuery $query)
    {
        return DataTables::eloquent($query->builder())
            ->addColumn('status', fn (Exporter $exporter) => $exporter->is_active ? __('ui.master.status_active') : __('ui.master.status_inactive'))
            ->addColumn('actions', function (Exporter $exporter) {
                $actions = [];
                if (auth()->user()->can('exporters.update')) {
                    $actions[] = [
                        'type' => 'edit',
                        'label' => __('ui.common.edit'),
                        'icon' => 'edit-2',
                        'update_url' => route('super.exporters.update', $exporter),
                        'payload' => $exporter->only(['id', 'tax_number', 'name', 'address', 'is_active']),
                    ];
                }
                if (auth()->user()->can('exporters.delete')) {
                    $actions[] = [
                        'type' => 'delete',
                        'url' => route('super.exporters.destroy', $exporter),
                        'label' => __('ui.common.delete'),
                        'icon' => 'trash-2',
                        'confirm' => __('ui.master.delete_exporter_confirmation'),
                        'disabled' => false,
                    ];
                }

                return view('admin.partials.table-actions', compact('actions'))->render();
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function store(ExporterStoreRequest $request, ExporterService $service)
    {
        $exporter = $service->create($request->validated());

        return response()->json(['message' => __('ui.master.exporter_created'), 'id' => $exporter->id], 201);
    }

    public function update(ExporterUpdateRequest $request, Exporter $exporter, ExporterService $service)
    {
        $service->update($exporter, $request->validated());

        return response()->json(['message' => __('ui.master.exporter_updated')]);
    }

    public function destroy(Exporter $exporter, ExporterService $service)
    {
        $service->delete($exporter);

        return response()->json(['message' => __('ui.master.exporter_deleted')]);
    }
}
