# Default filter periode

Permintaan pengguna: default filter tanggal satu bulan untuk semua modul. Interpretasi yang dipakai: tanggal 1 bulan berjalan sampai hari ini. Nilai filter yang dikirim pengguna tetap dipertahankan. Tanggal dokumen/form transaksi tidak diubah.

Default halaman yang sebelumnya hari ini, 30 hari terakhir, atau awal tahun disamakan. Fallback backend daftar/export tanpa parameter periode menggunakan awal bulan sampai hari ini; BC, dashboard dan laporan yang sudah sama tetap memakai default tersebut.

Test/build tidak dijalankan sesuai instruksi proyek. Tidak ada migration.

## File yang diubah

- app/Http/Controllers/Admin/AccountPayableController.php
- app/Http/Controllers/Admin/AuditLogController.php
- app/Http/Controllers/Admin/CashBankController.php
- app/Http/Controllers/Admin/CuttingController.php
- app/Http/Controllers/Admin/DeliveryNoteApprovalController.php
- app/Http/Controllers/Admin/EquipmentIssueController.php
- app/Http/Controllers/Admin/EquipmentReceiptController.php
- app/Http/Controllers/Admin/FinishedGoodsController.php
- app/Http/Controllers/Admin/GoodsReceiptController.php
- app/Http/Controllers/Admin/GoodsSentNoteController.php
- app/Http/Controllers/Admin/JournalEntryController.php
- app/Http/Controllers/Admin/MaterialIssueController.php
- app/Http/Controllers/Admin/MaterialRequestController.php
- app/Http/Controllers/Admin/NonProductionMaterialUsageController.php
- app/Http/Controllers/Admin/PackingController.php
- app/Http/Controllers/Admin/ProductionOrderController.php
- app/Http/Controllers/Admin/ProductionReceiptController.php
- app/Http/Controllers/Admin/ProductionReturnController.php
- app/Http/Controllers/Admin/PurchaseOrderController.php
- app/Http/Controllers/Admin/PurchaseRequestController.php
- app/Http/Controllers/Admin/PurchaseReturnController.php
- app/Http/Controllers/Admin/RawMaterialOrderController.php
- app/Http/Controllers/Admin/ScrapIssueController.php
- app/Http/Controllers/Admin/ScrapReceiptController.php
- app/Http/Controllers/Admin/SewingController.php
- app/Http/Controllers/Admin/StockCardController.php
- app/Http/Controllers/Admin/StockOpnameController.php
- app/Http/Controllers/Admin/SubcontractContractMonitoringController.php
- app/Http/Controllers/Admin/SubcontractController.php
- app/Http/Controllers/Admin/SubcontractSemiFinishedController.php
- app/Http/Controllers/Admin/SupportMaterialOrderController.php
- app/Http/Controllers/Admin/WipIssueController.php
- resources/views/super/audit-logs/index.blade.php
- resources/views/super/non-production-material-usages/index.blade.php
- resources/views/super/production-returns/index.blade.php
- resources/views/super/purchasing/date-filter.blade.php
- resources/views/super/scrap-issues/index.blade.php
- resources/views/super/scrap-receipts/index.blade.php
- resources/views/super/support-material-orders/index.blade.php
- resources/views/super/warehouse-production-receipts/index.blade.php
- resources/views/super/wip-issues/index.blade.php
