<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Queries\CompanyTableQuery;
use App\Domain\Companies\Services\CompanyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyStoreRequest;
use App\Http\Requests\Admin\CompanyUpdateRequest;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class CompanyController extends Controller
{
    public function index()
    {
        return view('super.companies.index');
    }

    public function dt(CompanyTableQuery $q)
    {
        return DataTables::eloquent($q->builder())
            ->editColumn('logo', function (Company $company) {
                if (!$company->logo) {
                    return '<span class="text-muted">No Logo</span>';
                }

                return '<img src="' . e(Storage::disk('public')->url($company->logo)) . '"
                    alt="' . e($company->name) . '"
                    style="width:50px;height:50px;object-fit:contain;border-radius:6px;">';
            })
            ->editColumn(
                'is_active',
                fn(Company $company) => $company->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Not Active</span>'
            )
            ->addColumn('actions', function (Company $company) {
                $actions = [
                    [
                        'type' => 'edit',
                        'label' => 'Edit',
                        'icon' => 'edit-2',
                        'class' => 'btn-edit-role',
                        'update_url' => route(
                            'super.companies.update',
                            $company->getRouteKey()
                        ),
                        'payload' => [
                            'id' => $company->id,
                            'code' => $company->code,
                            'name' => $company->name,
                            'logo' => $company->logo,
                            'email' => $company->email,
                            'phone' => $company->phone,
                            'website' => $company->website,
                            'tax_number' => $company->tax_number,
                            'address' => $company->address,
                            'city' => $company->city,
                            'province' => $company->province,
                            'postal_code' => $company->postal_code,
                            'currency' => $company->currency,
                            'timezone' => $company->timezone,
                            'date_format' => $company->date_format,
                            'invoice_header' => $company->invoice_header,
                            'invoice_footer' => $company->invoice_footer,
                            'receipt_header' => $company->receipt_header,
                            'receipt_footer' => $company->receipt_footer,
                            'is_active' => $company->is_active,
                        ],
                    ],
                    [
                        'type' => 'delete',
                        'label' => 'Delete',
                        'icon' => 'trash-2',
                        'class' => 'btn-delete-role',
                        'url' => route(
                            'super.companies.destroy',
                            $company->getRouteKey()
                        ),
                        'confirm' => "Company \"{$company->name}\" akan dihapus.",
                        'disabled' => false,
                    ],
                ];

                return view('admin.partials.table-actions', compact('actions'))->render();
            })
            ->rawColumns(['logo', 'is_active', 'actions'])
            ->toJson();
    }

    public function store(
        CompanyStoreRequest $request,
        CompanyService $service
    ) {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies', 'public');
        }

        $company = $service->create($data);

        return response()->json([
            'message' => 'Company berhasil dibuat.',
            'id' => $company->id,
        ], 201);
    }

    public function update(
        CompanyUpdateRequest $request,
        Company $company,
        CompanyService $service
    ) {
        $data = $request->validated();
        $oldLogo = $company->logo;

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies', 'public');
        } else {
            $data['logo'] = $company->logo;
        }

        $service->update($company, $data);

        if (
            $request->hasFile('logo') &&
            $oldLogo &&
            $oldLogo !== $data['logo']
        ) {
            Storage::disk('public')->delete($oldLogo);
        }

        return response()->json([
            'message' => 'Company berhasil diupdate.',
        ]);
    }

    public function destroy(
        Company $company,
        CompanyService $service
    ) {
        $logo = $company->logo;

        $service->delete($company);

        if ($logo) {
            Storage::disk('public')->delete($logo);
        }

        return response()->json([
            'message' => 'Company berhasil dihapus.',
        ]);
    }
}
