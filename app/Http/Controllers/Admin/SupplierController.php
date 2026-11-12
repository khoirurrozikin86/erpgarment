<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Suppliers\Queries\SupplierTableQuery;
use App\Domain\Suppliers\Services\SupplierService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupplierStoreRequest;
use App\Http\Requests\Admin\SupplierUpdateRequest;
use App\Models\Supplier;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\SuppliersExport;
use App\Exports\SuppliersTemplateExport;
use App\Imports\SuppliersImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $countries = collect(config('countries'))->union(
            Supplier::query()->whereNotNull('country')->distinct()->orderBy('country')->pluck('country')->mapWithKeys(fn (string $country) => [$country => $country])->all()
        )->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('super.suppliers.index', compact('countries'));
    }

    public function dt(SupplierTableQuery $q)
    {
        return DataTables::eloquent($q->builder())

            ->addColumn('actions', function (Supplier $supplier) {

                $actions = [
                    [
                        'type' => 'edit',
                        'label' => __('ui.common.edit'),
                        'icon' => 'edit-2',

                        'update_url' => route(
                            'super.suppliers.update',
                            $supplier->getRouteKey()
                        ),

                        'payload' => $supplier->only([
                            'id', 'code', 'name', 'address', 'contact_person',
                            'phone', 'fax', 'email', 'country', 'is_active',
                        ]),
                    ],

                    [
                        'type' => 'delete',
                        'url' => route(
                            'super.suppliers.destroy',
                            $supplier->getRouteKey()
                        ),
                        'label' => __('ui.common.delete'),
                        'icon' => 'trash-2',
                        'confirm' =>
                        __('ui.master.delete_confirmation', ['type' => __('ui.common.supplier'), 'name' => $supplier->name]),
                        'disabled' => false,
                    ],
                ];

                return view(
                    'admin.partials.table-actions',
                    compact('actions')
                )->render();
            })

            ->rawColumns(['actions'])

            ->toJson();
    }

    public function store(
        SupplierStoreRequest $request,
        SupplierService $service
    ) {
        $supplier = $service->create(
            $request->validated()
        );

        return $request->ajax() || $request->expectsJson()
            ? response()->json([
                'message' => 'Supplier created',
                'id' => $supplier->id,
            ], 201)
            : back()->with(
                'success',
                'Supplier created'
            );
    }

    public function update(
        SupplierUpdateRequest $request,
        Supplier $supplier,
        SupplierService $service
    ) {
        $service->update(
            $supplier,
            $request->validated()
        );

        return $request->ajax() || $request->expectsJson()
            ? response()->json([
                'message' => 'Supplier updated',
            ])
            : back()->with(
                'success',
                'Supplier updated'
            );
    }

    public function destroy(
        Supplier $supplier,
        SupplierService $service
    ) {
        /*
         * Nanti setelah modul Purchasing dibuat,
         * supplier yang sudah mempunyai transaksi
         * sebaiknya tidak boleh dihapus.
         *
         * Untuk sekarang masih boleh dihapus.
         */

        $service->delete($supplier);

        return request()->ajax() || request()->expectsJson()
            ? response()->json([
                'message' => 'Supplier deleted',
            ])
            : redirect()
            ->route('super.suppliers.index')
            ->with(
                'success',
                'Supplier deleted'
            );
    }


    public function export()
    {
        return Excel::download(
            new SuppliersExport,
            'suppliers-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(new SuppliersTemplateExport(), 'template-supplier.xlsx');
    }

    public function import(Request $request, SupplierService $service)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            Excel::import(new SuppliersImport($service), $request->file('file'));
        } catch (ExcelValidationException $exception) {
            $message = collect($exception->failures())
                ->map(fn ($failure) => 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors()))
                ->take(10)
                ->implode(' | ');

            return back()->withErrors(['file' => $message ?: 'Data supplier pada file tidak valid.']);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['file' => 'Import gagal. Pastikan kode supplier tidak duplikat dan format file sesuai template.']);
        }

        return redirect()->route('super.suppliers.index')->with('supplier_import_success', __('ui.master.imported', ['type' => __('ui.common.supplier')]));
    }
}
