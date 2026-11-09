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
        return view('super.customers.index');
    }

    public function dt(CustomerTableQuery $q)
    {
        return DataTables::eloquent($q->builder())

            ->addColumn('status', function (Customer $customer) {
                return $customer->is_active
                    ? 'Active'
                    : 'Not Active';
            })

            ->addColumn('actions', function (Customer $customer) {

                $actions = [
                    [
                        'type' => 'edit',
                        'label' => 'Edit',
                        'icon' => 'edit-2',

                        'update_url' => route(
                            'super.customers.update',
                            $customer->getRouteKey()
                        ),

                        'payload' => [
                            'id' => $customer->id,
                            'code' => $customer->code,
                            'name' => $customer->name,
                            'customer_type' => $customer->customer_type,
                            'contact_person' => $customer->contact_person,
                            'phone' => $customer->phone,
                            'email' => $customer->email,
                            'website' => $customer->website,
                            'tax_number' => $customer->tax_number,
                            'address' => $customer->address,
                            'city' => $customer->city,
                            'province' => $customer->province,
                            'postal_code' => $customer->postal_code,
                            'payment_term' => $customer->payment_term,
                            'credit_limit' => $customer->credit_limit,
                            'notes' => $customer->notes,
                            'is_active' => $customer->is_active,
                        ],
                    ],

                    [
                        'type' => 'delete',
                        'url' => route(
                            'super.customers.destroy',
                            $customer->getRouteKey()
                        ),
                        'label' => 'Delete',
                        'icon' => 'trash-2',
                        'confirm' =>
                        "Hapus customer {$customer->name}?",
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

        return redirect()->route('super.customers.index')->with('customer_import_success', 'Data customer berhasil diimpor.');
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
