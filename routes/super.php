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
    StyleController,
    UnitController,
    SupplierController,
    ImporterController,
    ExporterController,
    CustomerController,
    ProductController,
    BillOfMaterialController,
    ProductionOrderController,
    MaterialRequestController,
    PurchaseRequestController,
    PurchaseOrderController,
    GoodsReceiptController,
    PurchaseReturnController,
    CashBankController,
    AccountPayableController,
    ChartOfAccountController,
    JournalEntryController,
    ProfitLossController,
    PurchaseReportController,
    InventoryReportController,
    CashBankReportController,
    StockController,
    StockCardController,
    StockOpnameController,
    DamagedItemController,
    LostItemController,
    RawMaterialOrderController,
    MaterialIssueController,
    ProductionReceiptController,
    NonProductionMaterialUsageController,
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


        Route::prefix('styles')->name('styles.')->group(function () {
            Route::middleware('permission:styles.view')->get('/', [StyleController::class, 'index'])->name('index');
            Route::middleware('permission:styles.view')->get('/dt', [StyleController::class, 'dt'])->name('dt');
            Route::middleware('permission:styles.create')->post('/', [StyleController::class, 'store'])->name('store');
            Route::middleware('permission:styles.update')->put('/{style}', [StyleController::class, 'update'])->name('update');
            Route::middleware('permission:styles.delete')->delete('/{style}', [StyleController::class, 'destroy'])->name('destroy');
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

        Route::prefix('importers')->name('importers.')->group(function () {
            Route::middleware('permission:importers.view')->get('/', [ImporterController::class, 'index'])->name('index');
            Route::middleware('permission:importers.view')->get('/dt', [ImporterController::class, 'dt'])->name('dt');
            Route::middleware('permission:importers.create')->post('/', [ImporterController::class, 'store'])->name('store');
            Route::middleware('permission:importers.update')->put('/{importer}', [ImporterController::class, 'update'])->name('update');
            Route::middleware('permission:importers.delete')->delete('/{importer}', [ImporterController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('exporters')->name('exporters.')->group(function () {
            Route::middleware('permission:exporters.view')->get('/', [ExporterController::class, 'index'])->name('index');
            Route::middleware('permission:exporters.view')->get('/dt', [ExporterController::class, 'dt'])->name('dt');
            Route::middleware('permission:exporters.create')->post('/', [ExporterController::class, 'store'])->name('store');
            Route::middleware('permission:exporters.update')->put('/{exporter}', [ExporterController::class, 'update'])->name('update');
            Route::middleware('permission:exporters.delete')->delete('/{exporter}', [ExporterController::class, 'destroy'])->name('destroy');
        });


        Route::prefix('material-requests')->name('material-requests.')->group(function () {
            Route::middleware('permission:material-requests.view')->get('/', [MaterialRequestController::class, 'index'])->name('index');
            Route::middleware('permission:material-requests.view')->get('/dt', [MaterialRequestController::class, 'dt'])->name('dt');
            Route::middleware('permission:material-requests.view')->get('/export', [MaterialRequestController::class, 'export'])->name('export');
            Route::middleware('permission:material-requests.create')->get('/bom-requirements', [MaterialRequestController::class, 'bomRequirements'])->name('bom-requirements');
            Route::middleware('permission:material-requests.create')->get('/material-orders/{productionOrder}/requirements', [MaterialRequestController::class, 'productionOrderRequirements'])->name('material-order-requirements');
            Route::middleware('permission:material-requests.view')->get('/{materialRequest}', [MaterialRequestController::class, 'show'])->name('show');
            Route::middleware('permission:material-requests.view')->get('/{materialRequest}/pdf', [MaterialRequestController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:material-requests.view')->get('/{materialRequest}/pdf/download', [MaterialRequestController::class, 'downloadPdf'])->name('pdf.download');
            Route::middleware('permission:material-requests.create')->post('/', [MaterialRequestController::class, 'store'])->name('store');
            Route::middleware('permission:material-requests.approve')->put('/{materialRequest}/approve', [MaterialRequestController::class, 'approve'])->name('approve');
            Route::middleware('permission:material-requests.approve')->put('/{materialRequest}/reject', [MaterialRequestController::class, 'reject'])->name('reject');
            Route::middleware('permission:material-requests.issue')->put('/{materialRequest}/issue', [MaterialRequestController::class, 'issue'])->name('issue');
        });

        Route::prefix('material-orders')->name('material-orders.')->group(function () {
            Route::middleware('permission:production-orders.view')->get('/', [ProductionOrderController::class, 'index'])->name('index');
            Route::middleware('permission:production-orders.view')->get('/export', [ProductionOrderController::class, 'export'])->name('export');
            Route::middleware('permission:production-orders.view')->get('/{productionOrder}', [ProductionOrderController::class, 'show'])->name('show');
            Route::middleware('permission:production-orders.update')->post('/{productionOrder}/stages', [ProductionOrderController::class, 'recordStage'])->name('stages.store');
            Route::middleware('permission:production-orders.create')->post('/', [ProductionOrderController::class, 'store'])->name('store');
            Route::middleware('permission:production-orders.update')->put('/{productionOrder}', [ProductionOrderController::class, 'update'])->name('update');
            Route::middleware('permission:production-orders.delete')->delete('/{productionOrder}', [ProductionOrderController::class, 'destroy'])->name('destroy');
            Route::middleware('permission:production-orders.release')->put('/{productionOrder}/transition', [ProductionOrderController::class, 'transition'])->name('transition');
        });

        Route::prefix('purchase-requests')->name('purchase-requests.')->group(function () {
            Route::middleware('permission:purchase-requests.view')->get('/', [PurchaseRequestController::class, 'index'])->name('index');
            Route::middleware('permission:purchase-requests.view')->get('/dt', [PurchaseRequestController::class, 'dt'])->name('dt');
            Route::middleware('permission:purchase-requests.view')->get('/export', [PurchaseRequestController::class, 'export'])->name('export');
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
            Route::middleware('permission:purchase-orders.view')->get('/export', [PurchaseOrderController::class, 'export'])->name('export');
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
            Route::middleware('permission:goods-receipts.view')->get('/export', [GoodsReceiptController::class, 'export'])->name('export');
            Route::middleware('permission:goods-receipts.create')->get('/purchase-orders/eligible', [GoodsReceiptController::class, 'eligible'])->name('eligible');
            Route::middleware('permission:goods-receipts.create')->get('/purchase-orders/eligible/{purchaseOrderId}', [GoodsReceiptController::class, 'source'])->whereNumber('purchaseOrderId')->name('source');
            Route::middleware('permission:goods-receipts.create')->get('/create', [GoodsReceiptController::class, 'create'])->name('create');
            Route::middleware('permission:goods-receipts.create')->post('/', [GoodsReceiptController::class, 'store'])->name('store');
            Route::middleware('permission:goods-receipts.approve')->put('/{goodsReceipt}/approve', [GoodsReceiptController::class, 'approve'])->name('approve');
            Route::middleware('permission:goods-receipts.delete')->delete('/{goodsReceipt}', [GoodsReceiptController::class, 'destroy'])->name('destroy');
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
            Route::middleware('permission:purchase-returns.delete')->delete('/{purchaseReturn}', [PurchaseReturnController::class, 'destroy'])->name('destroy');
            Route::middleware('permission:purchase-returns.view')->get('/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('show');
            Route::middleware('permission:purchase-returns.view')->get('/{purchaseReturn}/pdf', [PurchaseReturnController::class, 'pdf'])->name('pdf');
            Route::middleware('permission:purchase-returns.view')->get('/{purchaseReturn}/pdf/download', [PurchaseReturnController::class, 'downloadPdf'])->name('pdf.download');
        });

        Route::prefix('account-payable')->name('account-payable.')->group(function () {
            Route::middleware('permission:account-payable.view')->get('/', [AccountPayableController::class, 'index'])->name('index');
            Route::middleware('permission:account-payable.view')->get('/dt', [AccountPayableController::class, 'dt'])->name('dt');
            Route::middleware('permission:account-payable.pay')->post('/receipts/{goodsReceipt}/payments', [AccountPayableController::class, 'pay'])->name('payments.store');
            Route::middleware('permission:account-payable.view')->get('/receipts/{goodsReceipt}/payments', [AccountPayableController::class, 'history'])->name('payments.history');
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
            Route::middleware('permission:stocks.view')->get('/lots/{product}', [StockController::class, 'lots'])->name('lots');
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

        Route::prefix('damaged-items')->name('damaged-items.')->group(function () {
            Route::middleware('permission:damaged-items.view')->get('/', [DamagedItemController::class, 'index'])->name('index');
            Route::middleware('permission:damaged-items.view')->get('/dt', [DamagedItemController::class, 'dt'])->name('dt');
            Route::middleware('permission:damaged-items.create')->get('/products/dt', [DamagedItemController::class, 'productsDt'])->name('products.dt');
            Route::middleware('permission:damaged-items.create')->get('/number-preview', [DamagedItemController::class, 'numberPreview'])->name('number-preview');
            Route::middleware('permission:damaged-items.create')->post('/', [DamagedItemController::class, 'store'])->name('store');
            Route::middleware('permission:damaged-items.update')->put('/{damagedItemLine}', [DamagedItemController::class, 'update'])->name('update');
            Route::middleware('permission:damaged-items.delete')->delete('/{damagedItemLine}', [DamagedItemController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('lost-items')->name('lost-items.')->group(function () {
            Route::middleware('permission:lost-items.view')->get('/', [LostItemController::class, 'index'])->name('index');
            Route::middleware('permission:lost-items.view')->get('/dt', [LostItemController::class, 'dt'])->name('dt');
            Route::middleware('permission:lost-items.view')->get('/export', [LostItemController::class, 'export'])->name('export');
            Route::middleware('permission:lost-items.create')->get('/products/dt', [LostItemController::class, 'productsDt'])->name('products.dt');
            Route::middleware('permission:lost-items.create')->get('/number-preview', [LostItemController::class, 'numberPreview'])->name('number-preview');
            Route::middleware('permission:lost-items.create')->post('/', [LostItemController::class, 'store'])->name('store');
            Route::middleware('permission:lost-items.update')->put('/{lostItemLine}', [LostItemController::class, 'update'])->name('update');
            Route::middleware('permission:lost-items.delete')->delete('/{lostItemLine}', [LostItemController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('non-production-material-usages')->name('non-production-material-usages.')->group(function () {
            Route::middleware('permission:non-production-material-usages.view')->get('/', [NonProductionMaterialUsageController::class, 'index'])->name('index');
            Route::middleware('permission:non-production-material-usages.view')->get('/dt', [NonProductionMaterialUsageController::class, 'dt'])->name('dt');
            Route::middleware('permission:non-production-material-usages.view')->get('/products/dt', [NonProductionMaterialUsageController::class, 'productsDt'])->name('products.dt');
            Route::middleware('permission:non-production-material-usages.create')->get('/number-preview', [NonProductionMaterialUsageController::class, 'numberPreview'])->name('number-preview');
            Route::middleware('permission:non-production-material-usages.create')->post('/', [NonProductionMaterialUsageController::class, 'store'])->name('store');
            Route::middleware('permission:non-production-material-usages.update')->put('/{usageItem}', [NonProductionMaterialUsageController::class, 'update'])->name('update');
            Route::middleware('permission:non-production-material-usages.delete')->delete('/{usageItem}', [NonProductionMaterialUsageController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('raw-material-orders')->name('raw-material-orders.')->group(function () {
            Route::middleware('permission:raw-material-orders.view')->get('/', [RawMaterialOrderController::class, 'index'])->name('index');
            Route::middleware('permission:raw-material-orders.view')->get('/dt', [RawMaterialOrderController::class, 'dt'])->name('dt');
            Route::middleware('permission:raw-material-orders.view')->get('/export', [RawMaterialOrderController::class, 'export'])->name('export');
            Route::middleware('permission:raw-material-orders.create')->get('/create', [RawMaterialOrderController::class, 'create'])->name('create');
            Route::middleware('permission:raw-material-orders.create')->get('/mo/dt', [RawMaterialOrderController::class, 'mosDt'])->name('mos.dt');
            Route::middleware('permission:raw-material-orders.create')->get('/mo/materials', [RawMaterialOrderController::class, 'moMaterials'])->name('materials');
            Route::middleware('permission:raw-material-orders.create')->post('/', [RawMaterialOrderController::class, 'store'])->name('store');
            Route::middleware('permission:raw-material-orders.update')->get('/{document}/edit', [RawMaterialOrderController::class, 'edit'])->name('edit');
            Route::middleware('permission:raw-material-orders.update')->put('/{document}', [RawMaterialOrderController::class, 'update'])->name('update');
            Route::middleware('permission:raw-material-orders.delete')->delete('/{document}', [RawMaterialOrderController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('material-issues')->name('material-issues.')->group(function () {
            Route::middleware('permission:material-issues.view')->get('/', [MaterialIssueController::class, 'index'])->name('index');
            Route::middleware('permission:material-issues.view')->get('/requests/dt', [MaterialIssueController::class, 'requestsDt'])->name('requests.dt');
            Route::middleware('permission:material-issues.view')->get('/history/dt', [MaterialIssueController::class, 'historyDt'])->name('history.dt');
            Route::middleware('permission:material-issues.create')->post('/bulk', [MaterialIssueController::class, 'issueBulk'])->name('bulk.store');
            Route::middleware('permission:material-issues.view')->get('/requests/{rawMaterialOrderDocument}', [MaterialIssueController::class, 'requestDetails'])->name('requests.show');
            Route::middleware('permission:material-issues.update')->put('/items/{materialIssueItem}', [MaterialIssueController::class, 'updateItem'])->name('items.update');
            Route::middleware('permission:material-issues.delete')->delete('/items/{materialIssueItem}', [MaterialIssueController::class, 'destroyItem'])->name('items.destroy');
            Route::middleware('permission:material-issues.view')->get('/documents/{document}/pdf', [MaterialIssueController::class, 'pdf'])->name('documents.pdf');
            Route::middleware('permission:material-issues.view')->get('/documents/{document}/pdf/download', [MaterialIssueController::class, 'downloadPdf'])->name('documents.pdf.download');
            Route::middleware('permission:material-issues.view')->get('/documents/{document}', [MaterialIssueController::class, 'documentDetails'])->name('documents.show');
            Route::middleware('permission:material-issues.view')->get('/export', [MaterialIssueController::class, 'export'])->name('export');
            Route::middleware('permission:material-issues.create')->post('/requests/{rawMaterialOrderItem}', [MaterialIssueController::class, 'issue'])->name('store');
        });

        Route::prefix('production-receipts')->name('production-receipts.')->group(function () {
            Route::middleware('permission:production-receipts.view')->get('/', [ProductionReceiptController::class, 'index'])->name('index');
            Route::middleware('permission:production-receipts.view')->get('/dt', [ProductionReceiptController::class, 'datatable'])->name('dt');
            Route::middleware('permission:production-receipts.view')->get('/documents/{document}', [ProductionReceiptController::class, 'details'])->name('documents.show');
            Route::middleware('permission:production-receipts.approve')->put('/documents/{document}/approve', [ProductionReceiptController::class, 'approve'])->name('approve');
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
                    ->get('/style-options', [ProductController::class, 'styleOptions'])
                    ->name('style-options');

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

        Route::prefix('boms')->name('boms.')->group(function () {
            Route::middleware('permission:boms.view')->get('/', [BillOfMaterialController::class, 'index'])->name('index');
            Route::middleware('permission:boms.view')->get('/export', [BillOfMaterialController::class, 'export'])->name('export');
            Route::middleware('permission:boms.create')->get('/create', [BillOfMaterialController::class, 'create'])->name('create');
            Route::middleware('permission:boms.create')->post('/', [BillOfMaterialController::class, 'store'])->name('store');
            Route::middleware('permission:boms.view')->get('/products/{product}/detail', [BillOfMaterialController::class, 'detail'])->name('detail');
            Route::middleware('permission:boms.create')->get('/products/{product}/input', [BillOfMaterialController::class, 'input'])->name('input');
            Route::middleware('permission:boms.update')->get('/{bom}/edit', [BillOfMaterialController::class, 'edit'])->name('edit');
            Route::middleware('permission:boms.update')->put('/{bom}', [BillOfMaterialController::class, 'update'])->name('update');
            Route::middleware('permission:boms.delete')->delete('/{bom}', [BillOfMaterialController::class, 'destroy'])->name('destroy');
            Route::middleware('permission:boms.delete')->delete('/{bom}/items/{item}', [BillOfMaterialController::class, 'destroyItem'])->name('items.destroy');
        });

    });
