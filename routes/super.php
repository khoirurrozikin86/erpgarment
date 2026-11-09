<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Super\{RoleController, PermissionController, UserManageController, UserController};
use App\Http\Controllers\Admin\{
    DashboardController,
    AuditLogController,
    CompanyController,
    GeneralSettingController,
    EmailSettingController,
    DocumentNumberingController,
    CategoryController,
    UnitController,
    SupplierController,
    CustomerController,
    ProductController,
    PriceListController,
    MaterialRequestController,
    PurchaseRequestController,
    PurchaseOrderController,
    GoodsReceiptController,
    PurchaseReturnController,
    SalesQuotationController,
    SalesOrderController,
    DeliveryController,
    SalesInvoiceController,
    CustomerReturnController,
    AccountReceivableController,
    CashBankController,
    AccountPayableController,
    PosSessionController,
    PosController,
    ChartOfAccountController,
    JournalEntryController,
    ProfitLossController,
    SalesReportController,
    PosSalesReportController,
    PurchaseReportController,
    InventoryReportController,
    CashBankReportController,
    StockController,
    StockCardController,
    StockOpnameController,
};

Route::middleware(['auth'])
    ->prefix('super')->name('super.')->group(function () {


        Route::get(
            '/',
            [DashboardController::class, 'index']
        )
            ->name('dashboard')
            ->middleware('permission:dashboard.view');

        /* ===== Access Control ===== */

        // ROLES
        Route::middleware('permission:role.read')->get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::middleware('permission:role.read')->get('/roles/dt', [RoleController::class, 'datatable'])->name('roles.dt');
        Route::middleware('permission:role.create')->get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::middleware('permission:role.create')->post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::middleware('permission:role.update')->get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::middleware('permission:role.update')->put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::middleware('permission:role.delete')->delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        Route::middleware('permission:role.update')->get('/roles/{role}/permissions', [RoleController::class, 'editPermissions'])->name('roles.permissions.edit');
        Route::middleware('permission:role.update')->put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');

        // PERMISSIONS
        Route::middleware('permission:permission.read')->get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::middleware('permission:permission.read')->get('/permissions/dt', [PermissionController::class, 'datatable'])->name('permissions.dt');
        Route::middleware('permission:permission.create')->get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
        Route::middleware('permission:permission.create')->post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::middleware('permission:permission.update')->get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::middleware('permission:permission.update')->put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::middleware('permission:permission.delete')->delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        // USERS (manajemen user)
        Route::prefix('users')->name('user.')->group(function () {
            Route::middleware('permission:user.read')->get('/', [UserController::class, 'index'])->name('index');
            Route::middleware('permission:user.read')->get('/dt', [UserController::class, 'datatable'])->name('dt');
            Route::middleware('permission:user.create')->get('/create', [UserController::class, 'create'])->name('create');
            Route::middleware('permission:user.create')->post('/', [UserController::class, 'store'])->name('store');
            Route::middleware('permission:user.update')->get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::middleware('permission:user.update')->put('/{user}', [UserController::class, 'update'])->name('update');
            Route::middleware('permission:user.delete')->delete('/{user}', [UserController::class, 'destroy'])->name('destroy');

            Route::get('/users/export', [UserController::class, 'export'])
                ->name('export');

            Route::middleware('permission:user.update')->put('/{user}/roles', [UserManageController::class, 'syncRoles'])->name('roles.sync');
            Route::middleware('permission:user.update')->put('/{user}/perms', [UserManageController::class, 'syncPermissions'])->name('perms.sync');
        });

        /* ===== Settings ===== */




        Route::prefix('audit-logs')
            ->name('audit-logs.')
            ->group(function () {

                Route::get('/', [
                    AuditLogController::class,
                    'index'
                ])
                    ->middleware('permission:audit-logs.view')
                    ->name('index');

                Route::get('/dt', [
                    AuditLogController::class,
                    'dt'
                ])
                    ->middleware('permission:audit-logs.view')
                    ->name('dt');



                Route::get('/export', [
                    AuditLogController::class,
                    'export'
                ])
                    ->middleware('permission:audit-logs.view')
                    ->name('export');




                Route::get('/{auditLog}', [
                    AuditLogController::class,
                    'show'
                ])
                    ->middleware('permission:audit-logs.view')
                    ->name('show');
            });







        // COMPANY
        Route::prefix('companies')
            ->name('companies.')
            ->group(function () {

                // VIEW
                Route::middleware('permission:company.view')
                    ->get('/', [CompanyController::class, 'index'])
                    ->name('index');

                Route::middleware('permission:company.view')
                    ->get('/dt', [CompanyController::class, 'dt'])
                    ->name('dt');

                // CREATE
                Route::middleware('permission:company.create')
                    ->post('/', [CompanyController::class, 'store'])
                    ->name('store');

                // UPDATE
                Route::middleware('permission:company.update')
                    ->put('/{company}', [CompanyController::class, 'update'])
                    ->name('update');

                // DELETE
                Route::middleware('permission:company.delete')
                    ->delete('/{company}', [CompanyController::class, 'destroy'])
                    ->name('destroy');
            });


        // SETTINGS

        Route::prefix('settings')->name('settings.')->group(function () {

            Route::middleware('permission:general-settings.view')
                ->get('/general', [GeneralSettingController::class, 'index'])
                ->name('general');

            Route::middleware('permission:general-settings.update')
                ->put('/general', [GeneralSettingController::class, 'update'])
                ->name('general.update');
        });



        Route::prefix('settings')->name('settings.')->group(function () {

            // General
            Route::middleware('permission:general-settings.view')
                ->get('/general', [GeneralSettingController::class, 'index'])
                ->name('general');

            Route::middleware('permission:general-settings.update')
                ->put('/general', [GeneralSettingController::class, 'update'])
                ->name('general.update');


            // Email
            Route::middleware('permission:email-settings.view')
                ->get('/email', [EmailSettingController::class, 'index'])
                ->name('email');

            Route::middleware('permission:email-settings.update')
                ->put('/email', [EmailSettingController::class, 'update'])
                ->name('email.update');

            Route::middleware('permission:email-settings.update')
                ->post('/email/test', [EmailSettingController::class, 'test'])
                ->name('email.test');



            // Document Numbering
            Route::middleware('permission:document-numbering.view')
                ->get('/document-numbering', [
                    DocumentNumberingController::class,
                    'index'
                ])
                ->name('document-numbering');

            Route::middleware('permission:document-numbering.update')
                ->put('/document-numbering/{numbering}', [
                    DocumentNumberingController::class,
                    'update'
                ])
                ->name('document-numbering.update');
        });


        Route::prefix('categories')
            ->name('categories.')
            ->group(function () {

                Route::middleware('permission:categories.view')
                    ->get('/', [
                        CategoryController::class,
                        'index',
                    ])
                    ->name('index');

                Route::middleware('permission:categories.view')
                    ->get('/dt', [
                        CategoryController::class,
                        'dt',
                    ])
                    ->name('dt');

                Route::middleware('permission:categories.view')
                    ->get('/template', [CategoryController::class, 'template'])
                    ->name('template');

                Route::middleware('permission:categories.view')
                    ->get('/export', [CategoryController::class, 'export'])
                    ->name('export');

                Route::middleware('permission:categories.create')
                    ->post('/import', [CategoryController::class, 'import'])
                    ->name('import');

                Route::middleware('permission:categories.create')
                    ->post('/', [
                        CategoryController::class,
                        'store',
                    ])
                    ->name('store');

                Route::middleware('permission:categories.update')
                    ->put('/{category}', [
                        CategoryController::class,
                        'update',
                    ])
                    ->name('update');

                Route::middleware('permission:categories.delete')
                    ->delete('/{category}', [
                        CategoryController::class,
                        'destroy',
                    ])
                    ->name('destroy');
            });


        Route::prefix('units')->name('units.')->group(function () {

            Route::middleware('permission:units.view')
                ->get('/', [UnitController::class, 'index'])
                ->name('index');

            Route::middleware('permission:units.view')
                ->get('/dt', [UnitController::class, 'dt'])
                ->name('dt');

            Route::middleware('permission:units.view')
                ->get('/template', [UnitController::class, 'template'])
                ->name('template');

            Route::middleware('permission:units.view')
                ->get('/export', [UnitController::class, 'export'])
                ->name('export');

            Route::middleware('permission:units.create')
                ->post('/import', [UnitController::class, 'import'])
                ->name('import');

            Route::middleware('permission:units.create')
                ->post('/', [UnitController::class, 'store'])
                ->name('store');

            Route::middleware('permission:units.update')
                ->put('/{unit}', [UnitController::class, 'update'])
                ->name('update');

            Route::middleware('permission:units.delete')
                ->delete('/{unit}', [UnitController::class, 'destroy'])
                ->name('destroy');
        });


        Route::prefix('suppliers')->name('suppliers.')->group(function () {

            Route::middleware('permission:suppliers.view')
                ->get('/', [SupplierController::class, 'index'])
                ->name('index');

            Route::middleware('permission:suppliers.view')
                ->get('/dt', [SupplierController::class, 'dt'])
                ->name('dt');

            Route::middleware('permission:suppliers.view')
                ->get('/export', [SupplierController::class, 'export'])
                ->name('export');

            Route::middleware('permission:suppliers.view')
                ->get('/template', [SupplierController::class, 'template'])
                ->name('template');



            Route::middleware('permission:suppliers.create')
                ->post('/', [SupplierController::class, 'store'])
                ->name('store');

            Route::middleware('permission:suppliers.create')
                ->post('/import', [SupplierController::class, 'import'])
                ->name('import');

            Route::middleware('permission:suppliers.update')
                ->put('/{supplier}', [SupplierController::class, 'update'])
                ->name('update');

            Route::middleware('permission:suppliers.delete')
                ->delete('/{supplier}', [SupplierController::class, 'destroy'])
                ->name('destroy');
        });


        Route::prefix('material-requests')->name('material-requests.')->group(function () {
            Route::middleware('permission:material-requests.view')->get('/', [MaterialRequestController::class, 'index'])->name('index');
            Route::middleware('permission:material-requests.view')->get('/dt', [MaterialRequestController::class, 'dt'])->name('dt');
            Route::middleware('permission:material-requests.view')->get('/{materialRequest}', [MaterialRequestController::class, 'show'])->name('show');
            Route::middleware('permission:material-requests.view')->get('/{materialRequest}/pdf', [MaterialRequestController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:material-requests.view')->get('/{materialRequest}/pdf/download', [MaterialRequestController::class, 'downloadPdf'])->name('pdf.download');
            Route::middleware('permission:material-requests.create')->post('/', [MaterialRequestController::class, 'store'])->name('store');
            Route::middleware('permission:material-requests.approve')->put('/{materialRequest}/approve', [MaterialRequestController::class, 'approve'])->name('approve');
            Route::middleware('permission:material-requests.approve')->put('/{materialRequest}/reject', [MaterialRequestController::class, 'reject'])->name('reject');
        });

        Route::prefix('purchase-requests')->name('purchase-requests.')->group(function () {
            Route::middleware('permission:purchase-requests.view')->get('/', [PurchaseRequestController::class, 'index'])->name('index');
            Route::middleware('permission:purchase-requests.view')->get('/dt', [PurchaseRequestController::class, 'dt'])->name('dt');
            Route::middleware('permission:purchase-requests.create')->get('/material-requests/eligible', [PurchaseRequestController::class, 'eligible'])->name('eligible');
            Route::middleware('permission:purchase-requests.create')->get('/material-requests/eligible/{materialRequestId}', [PurchaseRequestController::class, 'source'])->whereNumber('materialRequestId')->name('source');
            Route::middleware('permission:purchase-requests.create')->get('/create', [PurchaseRequestController::class, 'create'])->name('create');
            Route::middleware('permission:purchase-requests.create')->post('/', [PurchaseRequestController::class, 'store'])->name('store');
            Route::middleware('permission:purchase-requests.view')->get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show');
            Route::middleware('permission:purchase-requests.view')->get('/{purchaseRequest}/pdf', [PurchaseRequestController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:purchase-requests.view')->get('/{purchaseRequest}/pdf/download', [PurchaseRequestController::class, 'downloadPdf'])->name('pdf.download');
            Route::middleware('permission:purchase-requests.approve')->put('/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve'])->name('approve');
            Route::middleware('permission:purchase-requests.approve')->put('/{purchaseRequest}/reject', [PurchaseRequestController::class, 'reject'])->name('reject');
        });

        Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
            Route::middleware('permission:purchase-orders.view')->get('/', [PurchaseOrderController::class, 'index'])->name('index');
            Route::middleware('permission:purchase-orders.view')->get('/dt', [PurchaseOrderController::class, 'dt'])->name('dt');
            Route::middleware('permission:purchase-orders.create')->get('/purchase-requests/eligible', [PurchaseOrderController::class, 'eligible'])->name('eligible');
            Route::middleware('permission:purchase-orders.create')->get('/purchase-requests/eligible/{purchaseRequestId}', [PurchaseOrderController::class, 'source'])->whereNumber('purchaseRequestId')->name('source');
            Route::middleware('permission:purchase-orders.create')->get('/create', [PurchaseOrderController::class, 'create'])->name('create');
            Route::middleware('permission:purchase-orders.create')->post('/', [PurchaseOrderController::class, 'store'])->name('store');
            Route::middleware('permission:purchase-orders.view')->get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show');
            Route::middleware('permission:purchase-orders.view')->get('/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:purchase-orders.view')->get('/{purchaseOrder}/pdf/download', [PurchaseOrderController::class, 'downloadPdf'])->name('pdf.download');
            Route::middleware('permission:purchase-orders.view')->get('/{purchaseOrder}/emails', [PurchaseOrderController::class, 'emailHistory'])->name('emails');
            Route::middleware('permission:purchase-orders.issue')->put('/{purchaseOrder}/issue', [PurchaseOrderController::class, 'issue'])->name('issue');
            Route::middleware('permission:purchase-orders.email')->post('/{purchaseOrder}/email', [PurchaseOrderController::class, 'email'])->name('email');
        });

        Route::prefix('goods-receipts')->name('goods-receipts.')->group(function () {
            Route::middleware('permission:goods-receipts.view')->get('/', [GoodsReceiptController::class, 'index'])->name('index');
            Route::middleware('permission:goods-receipts.view')->get('/dt', [GoodsReceiptController::class, 'dt'])->name('dt');
            Route::middleware('permission:goods-receipts.create')->get('/purchase-orders/eligible', [GoodsReceiptController::class, 'eligible'])->name('eligible');
            Route::middleware('permission:goods-receipts.create')->get('/purchase-orders/eligible/{purchaseOrderId}', [GoodsReceiptController::class, 'source'])->whereNumber('purchaseOrderId')->name('source');
            Route::middleware('permission:goods-receipts.create')->get('/create', [GoodsReceiptController::class, 'create'])->name('create');
            Route::middleware('permission:goods-receipts.create')->post('/', [GoodsReceiptController::class, 'store'])->name('store');
            Route::middleware('permission:goods-receipts.view')->get('/{goodsReceipt}', [GoodsReceiptController::class, 'show'])->name('show');
            Route::middleware('permission:goods-receipts.view')->get('/{goodsReceipt}/pdf', [GoodsReceiptController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:goods-receipts.view')->get('/{goodsReceipt}/pdf/download', [GoodsReceiptController::class, 'downloadPdf'])->name('pdf.download');
        });

        Route::prefix('purchase-returns')->name('purchase-returns.')->group(function () {
            Route::middleware('permission:purchase-returns.view')->get('/', [PurchaseReturnController::class, 'index'])->name('index');
            Route::middleware('permission:purchase-returns.view')->get('/dt', [PurchaseReturnController::class, 'dt'])->name('dt');
            Route::middleware('permission:purchase-returns.create')->get('/goods-receipts/eligible', [PurchaseReturnController::class, 'eligible'])->name('eligible');
            Route::middleware('permission:purchase-returns.create')->get('/goods-receipts/eligible/{goodsReceiptId}', [PurchaseReturnController::class, 'source'])->whereNumber('goodsReceiptId')->name('source');
            Route::middleware('permission:purchase-returns.create')->get('/create', [PurchaseReturnController::class, 'create'])->name('create');
            Route::middleware('permission:purchase-returns.create')->post('/', [PurchaseReturnController::class, 'store'])->name('store');
            Route::middleware('permission:purchase-returns.view')->get('/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('show');
            Route::middleware('permission:purchase-returns.view')->get('/{purchaseReturn}/pdf', [PurchaseReturnController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:purchase-returns.view')->get('/{purchaseReturn}/pdf/download', [PurchaseReturnController::class, 'downloadPdf'])->name('pdf.download');
        });

        Route::prefix('sales-quotations')->name('sales-quotations.')->group(function () {
            Route::middleware('permission:sales-quotations.view')->get('/', [SalesQuotationController::class, 'index'])->name('index');
            Route::middleware('permission:sales-quotations.view')->get('/dt', [SalesQuotationController::class, 'dt'])->name('dt');
            Route::middleware('permission:sales-quotations.create')->get('/products/dt', [SalesQuotationController::class, 'productsDt'])->name('products.dt');
            Route::middleware('permission:sales-quotations.create')->get('/create', [SalesQuotationController::class, 'create'])->name('create');
            Route::middleware('permission:sales-quotations.create')->post('/', [SalesQuotationController::class, 'store'])->name('store');
            Route::middleware('permission:sales-quotations.view')->get('/{salesQuotation}', [SalesQuotationController::class, 'show'])->name('show');
            Route::middleware('permission:sales-quotations.view')->get('/{salesQuotation}/emails', [SalesQuotationController::class, 'emailHistory'])->name('emails');
            Route::middleware('permission:sales-quotations.view')->get('/{salesQuotation}/pdf', [SalesQuotationController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:sales-quotations.view')->get('/{salesQuotation}/pdf/download', [SalesQuotationController::class, 'downloadPdf'])->name('pdf.download');
            Route::middleware('permission:sales-quotations.issue')->put('/{salesQuotation}/issue', [SalesQuotationController::class, 'issue'])->name('issue');
            Route::middleware('permission:sales-quotations.review')->put('/{salesQuotation}/review', [SalesQuotationController::class, 'review'])->name('review');
            Route::middleware('permission:sales-quotations.email')->post('/{salesQuotation}/email', [SalesQuotationController::class, 'email'])->name('email');
        });

        Route::prefix('sales-orders')->name('sales-orders.')->group(function () {
            Route::middleware('permission:sales-orders.view')->get('/', [SalesOrderController::class, 'index'])->name('index');
            Route::middleware('permission:sales-orders.view')->get('/dt', [SalesOrderController::class, 'dt'])->name('dt');
            Route::middleware('permission:sales-orders.create')->get('/quotations/eligible', [SalesOrderController::class, 'eligible'])->name('eligible');
            Route::middleware('permission:sales-orders.create')->get('/quotations/eligible/{quotationId}', [SalesOrderController::class, 'source'])->whereNumber('quotationId')->name('source');
            Route::middleware('permission:sales-orders.create')->get('/create', [SalesOrderController::class, 'create'])->name('create');
            Route::middleware('permission:sales-orders.create')->post('/', [SalesOrderController::class, 'store'])->name('store');
            Route::middleware('permission:sales-orders.view')->get('/{salesOrder}', [SalesOrderController::class, 'show'])->name('show');
            Route::middleware('permission:sales-orders.view')->get('/{salesOrder}/pdf', [SalesOrderController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:sales-orders.view')->get('/{salesOrder}/pdf/download', [SalesOrderController::class, 'downloadPdf'])->name('pdf.download');
            Route::middleware('permission:sales-orders.confirm')->put('/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->name('confirm');
        });

        Route::prefix('deliveries')->name('deliveries.')->group(function () {
            Route::middleware('permission:deliveries.view')->get('/', [DeliveryController::class, 'index'])->name('index');
            Route::middleware('permission:deliveries.view')->get('/dt', [DeliveryController::class, 'dt'])->name('dt');
            Route::middleware('permission:deliveries.create')->get('/sales-orders/eligible', [DeliveryController::class, 'eligible'])->name('eligible');
            Route::middleware('permission:deliveries.create')->get('/sales-orders/eligible/{salesOrderId}', [DeliveryController::class, 'source'])->whereNumber('salesOrderId')->name('source');
            Route::middleware('permission:deliveries.create')->get('/create', [DeliveryController::class, 'create'])->name('create');
            Route::middleware('permission:deliveries.create')->post('/', [DeliveryController::class, 'store'])->name('store');
            Route::middleware('permission:deliveries.view')->get('/{delivery}', [DeliveryController::class, 'show'])->name('show');
            Route::middleware('permission:deliveries.view')->get('/{delivery}/pdf', [DeliveryController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:deliveries.view')->get('/{delivery}/pdf/download', [DeliveryController::class, 'downloadPdf'])->name('pdf.download');
            Route::middleware('permission:deliveries.post')->put('/{delivery}/post', [DeliveryController::class, 'post'])->name('post');
            Route::middleware('permission:deliveries.create')->delete('/{delivery}', [DeliveryController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('sales-invoices')->name('sales-invoices.')->group(function () {
            Route::middleware('permission:sales-invoices.view')->get('/', [SalesInvoiceController::class, 'index'])->name('index');
            Route::middleware('permission:sales-invoices.view')->get('/dt', [SalesInvoiceController::class, 'dt'])->name('dt');
            Route::middleware('permission:sales-invoices.create')->get('/deliveries/eligible', [SalesInvoiceController::class, 'eligible'])->name('eligible');
            Route::middleware('permission:sales-invoices.create')->get('/deliveries/eligible/{deliveryId}', [SalesInvoiceController::class, 'source'])->whereNumber('deliveryId')->name('source');
            Route::middleware('permission:sales-invoices.create')->get('/create', [SalesInvoiceController::class, 'create'])->name('create');
            Route::middleware('permission:sales-invoices.create')->post('/', [SalesInvoiceController::class, 'store'])->name('store');
            Route::middleware('permission:sales-invoices.view')->get('/{salesInvoice}', [SalesInvoiceController::class, 'show'])->name('show');
            Route::middleware('permission:sales-invoices.view')->get('/{salesInvoice}/emails', [SalesInvoiceController::class, 'emailHistory'])->name('emails');
            Route::middleware('permission:sales-invoices.view')->get('/{salesInvoice}/pdf', [SalesInvoiceController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:sales-invoices.view')->get('/{salesInvoice}/pdf/download', [SalesInvoiceController::class, 'downloadPdf'])->name('pdf.download');
            Route::middleware('permission:sales-invoices.issue')->put('/{salesInvoice}/issue', [SalesInvoiceController::class, 'issue'])->name('issue');
            Route::middleware('permission:sales-invoices.email')->post('/{salesInvoice}/email', [SalesInvoiceController::class, 'email'])->name('email');
        });

        Route::prefix('customer-returns')->name('customer-returns.')->group(function () {
            Route::middleware('permission:customer-returns.view')->get('/', [CustomerReturnController::class, 'index'])->name('index');
            Route::middleware('permission:customer-returns.view')->get('/dt', [CustomerReturnController::class, 'dt'])->name('dt');
            Route::middleware('permission:customer-returns.create')->get('/sales-invoices/eligible', [CustomerReturnController::class, 'eligible'])->name('eligible');
            Route::middleware('permission:customer-returns.create')->get('/sales-invoices/eligible/{salesInvoiceId}', [CustomerReturnController::class, 'source'])->whereNumber('salesInvoiceId')->name('source');
            Route::middleware('permission:customer-returns.create')->get('/create', [CustomerReturnController::class, 'create'])->name('create');
            Route::middleware('permission:customer-returns.create')->post('/', [CustomerReturnController::class, 'store'])->name('store');
            Route::middleware('permission:customer-returns.view')->get('/{customerReturn}', [CustomerReturnController::class, 'show'])->name('show');
            Route::middleware('permission:customer-returns.view')->get('/{customerReturn}/pdf', [CustomerReturnController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:customer-returns.view')->get('/{customerReturn}/pdf/download', [CustomerReturnController::class, 'downloadPdf'])->name('pdf.download');
        });

        Route::prefix('account-receivable')->name('account-receivable.')->group(function () {
            Route::middleware('permission:account-receivable.view')->get('/', [AccountReceivableController::class, 'index'])->name('index');
            Route::middleware('permission:account-receivable.view')->get('/dt', [AccountReceivableController::class, 'dt'])->name('dt');
            Route::middleware('permission:account-receivable.receive')->post('/invoices/{salesInvoice}/payments', [AccountReceivableController::class, 'receivePayment'])->name('payments.store');
            Route::middleware('permission:account-receivable.view')->get('/invoices/{salesInvoice}/payments', [AccountReceivableController::class, 'paymentHistory'])->name('payments.history');
        });

        Route::prefix('account-payable')->name('account-payable.')->group(function () {
            Route::middleware('permission:account-payable.view')->get('/', [AccountPayableController::class, 'index'])->name('index');
            Route::middleware('permission:account-payable.view')->get('/dt', [AccountPayableController::class, 'dt'])->name('dt');
            Route::middleware('permission:account-payable.pay')->post('/receipts/{goodsReceipt}/payments', [AccountPayableController::class, 'pay'])->name('payments.store');
            Route::middleware('permission:account-payable.view')->get('/receipts/{goodsReceipt}/payments', [AccountPayableController::class, 'history'])->name('payments.history');
        });

        Route::prefix('pos-sessions')->name('pos-sessions.')->group(function () {
            Route::middleware('permission:pos-sessions.view')->get('/', [PosSessionController::class, 'index'])->name('index');
            Route::middleware('permission:pos-sessions.view')->get('/dt', [PosSessionController::class, 'dt'])->name('dt');
            Route::middleware('permission:pos-sessions.open')->post('/', [PosSessionController::class, 'open'])->name('open');
            Route::middleware('permission:pos-sessions.close')->post('/{posSession}/close', [PosSessionController::class, 'close'])->name('close');
        });

        Route::prefix('pos')->name('pos.')->group(function () {
            Route::middleware('permission:pos.view')->get('/', [PosController::class, 'index'])->name('index');
            Route::middleware('permission:pos.view')->get('/products', [PosController::class, 'products'])->name('products');
            Route::middleware('permission:pos.view')->get('/sales/history', [PosController::class, 'history'])->name('history');
            Route::middleware('permission:pos.view')->get('/sales/{posSale}/receipt', [PosController::class, 'receipt'])->name('receipt');
            Route::middleware('permission:pos.sell')->post('/checkout', [PosController::class, 'checkout'])->name('checkout');
            Route::middleware('permission:pos.void')->put('/sales/{posSale}/void', [PosController::class, 'void'])->name('void');
        });

        Route::prefix('cash-bank')->name('cash-bank.')->group(function () {
            Route::middleware('permission:cash-bank.view')->get('/', [CashBankController::class, 'index'])->name('index');
            Route::middleware('permission:cash-bank.view')->get('/accounts/dt', [CashBankController::class, 'accountsDt'])->name('accounts.dt');
            Route::middleware('permission:cash-bank.view')->get('/transactions/dt', [CashBankController::class, 'transactionsDt'])->name('transactions.dt');
            Route::middleware('permission:cash-bank.create')->post('/accounts', [CashBankController::class, 'storeAccount'])->name('accounts.store');
            Route::middleware('permission:cash-bank.create')->post('/transactions', [CashBankController::class, 'storeTransaction'])->name('transactions.store');
            Route::middleware('permission:cash-bank.update')->put('/transactions/{cashBankTransaction}', [CashBankController::class, 'updateTransaction'])->name('transactions.update');
            Route::middleware('permission:cash-bank.delete')->delete('/transactions/{cashBankTransaction}', [CashBankController::class, 'deleteTransaction'])->name('transactions.destroy');
        });

        Route::prefix('chart-of-accounts')->name('chart-of-accounts.')->group(function () {
            Route::middleware('permission:chart-of-accounts.view')->get('/', [ChartOfAccountController::class, 'index'])->name('index');
            Route::middleware('permission:chart-of-accounts.view')->get('/dt', [ChartOfAccountController::class, 'dt'])->name('dt');
            Route::middleware('permission:chart-of-accounts.create')->post('/', [ChartOfAccountController::class, 'store'])->name('store');
            Route::middleware('permission:chart-of-accounts.update')->put('/{chartOfAccount}', [ChartOfAccountController::class, 'update'])->name('update');
        });

        Route::prefix('journals')->name('journals.')->group(function () {
            Route::middleware('permission:journal.view')->get('/', [JournalEntryController::class, 'index'])->name('index');
            Route::middleware('permission:journal.view')->get('/dt', [JournalEntryController::class, 'dt'])->name('dt');
            Route::middleware('permission:journal.create')->get('/create', [JournalEntryController::class, 'create'])->name('create');
            Route::middleware('permission:journal.create')->post('/', [JournalEntryController::class, 'store'])->name('store');
            Route::middleware('permission:journal.view')->get('/{journalEntry}', [JournalEntryController::class, 'show'])->name('show');
            Route::middleware('permission:journal.post')->put('/{journalEntry}/post', [JournalEntryController::class, 'post'])->name('post');
            Route::middleware('permission:journal.delete')->delete('/{journalEntry}', [JournalEntryController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('permission:profit-loss.view')->get('/profit-loss', [ProfitLossController::class, 'index'])->name('profit-loss.index');
        Route::prefix('reports/sales')->name('reports.sales.')->group(function () {
            Route::middleware('permission:sales-report.view')->get('/', [SalesReportController::class, 'index'])->name('index');
            Route::middleware('permission:sales-report.view')->get('/export', [SalesReportController::class, 'export'])->name('export');
        });
        Route::prefix('reports/pos-sales')->name('reports.pos-sales.')->group(function () {
            Route::middleware('permission:sales-report.view')->get('/', [PosSalesReportController::class, 'index'])->name('index');
            Route::middleware('permission:sales-report.view')->get('/export', [PosSalesReportController::class, 'export'])->name('export');
        });
        Route::prefix('reports/purchase')->name('reports.purchase.')->group(function () {
            Route::middleware('permission:purchase-report.view')->get('/', [PurchaseReportController::class, 'index'])->name('index');
            Route::middleware('permission:purchase-report.view')->get('/export', [PurchaseReportController::class, 'export'])->name('export');
        });
        Route::prefix('reports/inventory')->name('reports.inventory.')->group(function () {
            Route::middleware('permission:inventory-report.view')->get('/', [InventoryReportController::class, 'index'])->name('index');
            Route::middleware('permission:inventory-report.view')->get('/export', [InventoryReportController::class, 'export'])->name('export');
        });
        Route::prefix('reports/cash-bank')->name('reports.cash-bank.')->group(function () {
            Route::middleware('permission:cash-bank-report.view')->get('/', [CashBankReportController::class, 'index'])->name('index');
            Route::middleware('permission:cash-bank-report.view')->get('/export', [CashBankReportController::class, 'export'])->name('export');
        });

        Route::prefix('stocks')->name('stocks.')->group(function () {
            Route::middleware('permission:stocks.view')->get('/', [StockController::class, 'index'])->name('index');
            Route::middleware('permission:stocks.view')->get('/dt', [StockController::class, 'dt'])->name('dt');
            Route::middleware('permission:stocks.view')->get('/export', [StockController::class, 'export'])->name('export');
        });

        Route::prefix('stock-card')->name('stock-card.')->group(function () {
            Route::middleware('permission:stock-card.view')->get('/', [StockCardController::class, 'index'])->name('index');
            Route::middleware('permission:stock-card.view')->get('/products/dt', [StockCardController::class, 'productsDt'])->name('products.dt');
            Route::middleware('permission:stock-card.view')->get('/dt', [StockCardController::class, 'dt'])->name('dt');
            Route::middleware('permission:stock-card.view')->get('/summary', [StockCardController::class, 'summary'])->name('summary');
            Route::middleware('permission:stock-card.view')->get('/export', [StockCardController::class, 'export'])->name('export');
        });

        Route::prefix('stock-opnames')->name('stock-opnames.')->group(function () {
            Route::middleware('permission:stock-opnames.view')->get('/', [StockOpnameController::class, 'index'])->name('index');
            Route::middleware('permission:stock-opnames.view')->get('/dt', [StockOpnameController::class, 'dt'])->name('dt');
            Route::middleware('permission:stock-opnames.create')->get('/products/dt', [StockOpnameController::class, 'productsDt'])->name('products.dt');
            Route::middleware('permission:stock-opnames.create')->get('/create', [StockOpnameController::class, 'create'])->name('create');
            Route::middleware('permission:stock-opnames.create')->post('/', [StockOpnameController::class, 'store'])->name('store');
            Route::middleware('permission:stock-opnames.view')->get('/{stockOpname}', [StockOpnameController::class, 'show'])->name('show');
            Route::middleware('permission:stock-opnames.count')->get('/{stockOpname}/count', [StockOpnameController::class, 'count'])->name('count');
            Route::middleware('permission:stock-opnames.count')->put('/{stockOpname}/count', [StockOpnameController::class, 'saveCounts'])->name('count.save');
            Route::middleware('permission:stock-opnames.post')->put('/{stockOpname}/post', [StockOpnameController::class, 'post'])->name('post');
        });

        Route::prefix('customers')->name('customers.')->group(function () {

            // List / halaman Customer
            Route::middleware('permission:customers.view')
                ->get('/', [CustomerController::class, 'index'])
                ->name('index');

            // DataTables
            Route::middleware('permission:customers.view')
                ->get('/dt', [CustomerController::class, 'dt'])
                ->name('dt');

            // Export Excel
            Route::middleware('permission:customers.view')
                ->get('/export', [CustomerController::class, 'export'])
                ->name('export');

            Route::middleware('permission:customers.view')
                ->get('/template', [CustomerController::class, 'template'])
                ->name('template');

            // Create
            Route::middleware('permission:customers.create')
                ->post('/', [CustomerController::class, 'store'])
                ->name('store');

            Route::middleware('permission:customers.create')
                ->post('/import', [CustomerController::class, 'import'])
                ->name('import');

            // Update
            Route::middleware('permission:customers.update')
                ->put('/{customer}', [CustomerController::class, 'update'])
                ->name('update');

            // Delete
            Route::middleware('permission:customers.delete')
                ->delete('/{customer}', [CustomerController::class, 'destroy'])
                ->name('destroy');
        });



        Route::prefix('products')
            ->name('products.')
            ->group(function () {
                Route::middleware('permission:products.view')
                    ->get('/', [ProductController::class, 'index'])
                    ->name('index');

                Route::middleware('permission:products.view')
                    ->get('/dt', [ProductController::class, 'dt'])
                    ->name('dt');

                Route::middleware('permission:products.view')
                    ->get('/export', [ProductController::class, 'export'])
                    ->name('export');

                Route::middleware('permission:products.view')
                    ->get('/template', [ProductController::class, 'template'])
                    ->name('template');

                Route::middleware('permission:products.create')
                    ->post('/import', [ProductController::class, 'import'])
                    ->name('import');

                Route::middleware('permission:products.create')
                    ->post('/', [ProductController::class, 'store'])
                    ->name('store');

                Route::middleware('permission:products.update')
                    ->put('/{product}', [ProductController::class, 'update'])
                    ->name('update');

                Route::middleware('permission:products.delete')
                    ->delete('/{product}', [ProductController::class, 'destroy'])
                    ->name('destroy');
            });

        Route::prefix('pricelists')->name('pricelists.')->group(function () {
            Route::middleware('permission:pricelists.view')->get('/', [PriceListController::class, 'index'])->name('index');
            Route::middleware('permission:pricelists.view')->get('/dt', [PriceListController::class, 'dt'])->name('dt');
            Route::middleware('permission:pricelists.create')->post('/', [PriceListController::class, 'store'])->name('store');
            Route::middleware('permission:pricelists.update')->put('/{priceList}', [PriceListController::class, 'update'])->name('update');
            Route::middleware('permission:pricelists.delete')->delete('/{priceList}', [PriceListController::class, 'destroy'])->name('destroy');
        });
    });
