<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Customers\Queries\CustomerTableQuery;
use App\Domain\Customers\Services\CustomerService;
use App\Exports\CustomersExport;
use App\Exports\CustomersTemplateExport;
use App\Imports\CustomersImport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerStoreRequest;
use App\Http\Requests\Admin\CustomerUpdateRequest;
use App\Models\Customer;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    public function index()
    {
        $countries = collect(config('countries'))->union(
            Customer::query()->whereNotNull('country')->distinct()->orderBy('country')->pluck('country')->mapWithKeys(fn (string $country) => [$country => $country])->all()
        )->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('super.customers.index', compact('countries'));
    }

    public function dt(CustomerTableQuery $q)
    {
        return DataTables::eloquent($q->builder())

            ->addColumn('actions', function (Customer $customer) {

                $actions = [
                    [
                        'type' => 'edit',
                        'label' => __('ui.common.edit'),
                        'icon' => 'edit-2',

                        'update_url' => route(
                            'super.customers.update',
                            $customer->getRouteKey()
                        ),

                        'payload' => $customer->only([
                            'id', 'code', 'name', 'address', 'contact_person',
                            'phone', 'fax', 'email', 'country', 'is_active',
                        ]),
                    ],

                    [
                        'type' => 'delete',
                        'url' => route(
                            'super.customers.destroy',
                            $customer->getRouteKey()
                        ),
                        'label' => __('ui.common.delete'),
                        'icon' => 'trash-2',
                        'confirm' =>
                        __('ui.master.delete_confirmation', ['type' => __('ui.common.customer'), 'name' => $customer->name]),
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

    public function export()
    {
        return Excel::download(
            new CustomersExport,
            'customers-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(new CustomersTemplateExport(), 'template-customer.xlsx');
    }

    public function import(Request $request, CustomerService $service)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            Excel::import(new CustomersImport($service), $request->file('file'));
        } catch (ExcelValidationException $exception) {
            $message = collect($exception->failures())
                ->map(fn ($failure) => 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors()))
                ->take(10)
                ->implode(' | ');

            return back()->withErrors(['file' => $message ?: 'Data customer pada file tidak valid.']);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['file' => 'Import gagal. Pastikan kode customer tidak duplikat dan format file sesuai template.']);
        }

        return redirect()->route('super.customers.index')->with('customer_import_success', __('ui.master.imported', ['type' => __('ui.common.customer')]));
    }

    public function store(
        CustomerStoreRequest $request,
        CustomerService $service
    ) {
        $customer = $service->create(
            $request->validated()
        );

        return $request->ajax() || $request->expectsJson()
            ? response()->json([
                'message' => 'Customer created',
                'id' => $customer->id,
            ], 201)
            : back()->with(
                'success',
                'Customer created'
            );
    }

    public function update(
        CustomerUpdateRequest $request,
        Customer $customer,
        CustomerService $service
    ) {
        $service->update(
            $customer,
            $request->validated()
        );

        return $request->ajax() || $request->expectsJson()
            ? response()->json([
                'message' => 'Customer updated',
            ])
            : back()->with(
                'success',
                'Customer updated'
            );
    }

    public function destroy(
        Customer $customer,
        CustomerService $service
    ) {
        $service->delete($customer);

        return request()->ajax() || request()->expectsJson()
            ? response()->json([
                'message' => 'Customer deleted',
            ])
            : redirect()
            ->route('super.customers.index')
            ->with(
                'success',
                'Customer deleted'
            );
    }
}
