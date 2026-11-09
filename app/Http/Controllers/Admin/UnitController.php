<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Units\Queries\UnitTableQuery;
use App\Domain\Units\Services\UnitService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UnitStoreRequest;
use App\Http\Requests\Admin\UnitUpdateRequest;
use App\Models\Unit;
use App\Exports\UnitsTemplateExport;
use App\Exports\UnitsExport;
use App\Imports\UnitsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
    public function index()
    {
        return view('super.units.index');
    }

    public function template()
    {
        return Excel::download(new UnitsTemplateExport(), 'template-satuan.xlsx');
    }

    public function export()
    {
        return Excel::download(new UnitsExport(), 'satuan-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    public function import(Request $request, UnitService $service)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);

        try {
            Excel::import(new UnitsImport($service), $request->file('file'));
        } catch (ExcelValidationException $exception) {
            $message = collect($exception->failures())
                ->map(fn ($failure) => 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors()))
                ->take(10)
                ->implode(' | ');

            return back()->withErrors(['file' => $message ?: 'Data satuan pada file tidak valid.']);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['file' => 'Import gagal. Pastikan kode satuan tidak duplikat dan format file sesuai template.']);
        }

        return redirect()->route('super.units.index')->with('unit_import_success', 'Data satuan berhasil diimpor.');
    }

    public function dt(UnitTableQuery $q)
    {
        return DataTables::eloquent($q->builder())

            ->addColumn('status', function (Unit $unit) {
                return $unit->is_active
                    ? 'Active'
                    : 'Not Active';
            })

            ->addColumn('actions', function (Unit $unit) {

                $actions = [
                    [
                        'type' => 'edit',
                        'label' => 'Edit',
                        'icon' => 'edit-2',

                        'update_url' => route(
                            'super.units.update',
                            $unit->getRouteKey()
                        ),

                        'payload' => [
                            'id' => $unit->id,
                            'code' => $unit->code,
                            'name' => $unit->name,
                            'symbol' => $unit->symbol,
                            'description' => $unit->description,
                            'is_active' => $unit->is_active,
                        ],
                    ],

                    [
                        'type' => 'delete',
                        'url' => route(
                            'super.units.destroy',
                            $unit->getRouteKey()
                        ),
                        'label' => 'Delete',
                        'icon' => 'trash-2',
                        'confirm' => "Hapus satuan {$unit->name}?",
                        'disabled' => false,
                    ],
                ];

                return view(
                    'admin.partials.table-actions',
                    compact('actions')
                )->render();
            })

            ->rawColumns([
                'status',
                'actions',
            ])

            ->toJson();
    }

    public function store(
        UnitStoreRequest $request,
        UnitService $service
    ) {
        $unit = $service->create(
            $request->validated()
        );

        return $request->ajax() || $request->expectsJson()
            ? response()->json([
                'message' => 'Unit created',
                'id' => $unit->id,
            ], 201)
            : back()->with(
                'success',
                'Unit created'
            );
    }

    public function update(
        UnitUpdateRequest $request,
        Unit $unit,
        UnitService $service
    ) {
        $service->update(
            $unit,
            $request->validated()
        );

        return $request->ajax() || $request->expectsJson()
            ? response()->json([
                'message' => 'Unit updated',
            ])
            : back()->with(
                'success',
                'Unit updated'
            );
    }

    public function destroy(
        Unit $unit,
        UnitService $service
    ) {
        if ($unit->products()->exists()) {
            return response()->json([
                'message' =>
                'Unit cannot be deleted because it is already used by a product.',
            ], 422);
        }

        $service->delete($unit);

        return request()->ajax() || request()->expectsJson()
            ? response()->json([
                'message' => 'Unit deleted',
            ])
            : redirect()
            ->route('super.units.index')
            ->with(
                'success',
                'Unit deleted'
            );
    }
}
