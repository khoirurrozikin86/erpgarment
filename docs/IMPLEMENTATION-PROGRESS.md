# Progres Implementasi

Pengerjaan dilakukan satu modul per instruksi. Tidak ada coding modul berikutnya sebelum pengguna memberikan arahan.

## 2026-11-13 — Master Importir

- **Status:** selesai untuk scope CRUD master.
- **Lingkup:** migration/model, DTO, query DataTable, create/update/delete actions dan service, request validation, controller/routes, permission, navigasi, form daftar, dan audit log.
- **Field:** NPWP, nama, alamat, APIP, status aktif/nonaktif. Ini mengikuti field yang terlihat pada referensi; aturan bisnis yang tidak terlihat tidak diasumsikan.
- **Test:** `php artisan test --compact tests/Feature/Masters/ImporterTest.php` — **lulus**, 2 test / 13 assertion. `php artisan route:list --path=super/importers` menampilkan 5 route; file PHP yang disentuh lolos `php -l`.
- **Suite keseluruhan:** `php artisan test --compact` — 21 lulus, 11 gagal. Kegagalan yang terlihat berasal dari area lain: route bernama `dashboard` tidak terdefinisi pada test auth, syntax error pada partial profile, dan fixture user yang tidak ditemukan pada `CustomerReturnTest`. Tidak ada stack trace kegagalan yang menunjuk ke modul Importir.
- **Perbaikan CRUD lanjutan:** skrip modal sebelumnya berada di dalam section konten sehingga dirender sebelum jQuery/Bootstrap dari layout; dipindahkan ke `@push('scripts')`. Tombol edit/hapus kini memakai payload dan action partial DataTable yang sama dengan master kategori/satuan, termasuk validasi AJAX dan konfirmasi hapus. Test modul dijalankan ulang setelah perbaikan.
- **Sidebar:** sesuai arahan lanjutan, User/Role/Permission berada di grup Access; Category/Unit/Importer/Companies/General/Email/Document Numbering berada di Setting; master lain dan menu pengadaan berada di PPIC. Gudang, Produksi, Sales, Akuntansi, dan Laporan tetap dikelompokkan tersendiri. Grup tanpa halaman/route tidak ditampilkan sebagai menu kosong.
- **Navigasi inventory:** tautan Price Lists dihapus dari sidebar atas permintaan pengguna karena tidak masuk konteks IT Inventory; kode modul, route, permission, dan datanya tetap dipertahankan.
- **Audit Price Lists:** fitur masih dipakai `SalesQuotationTableQuery` untuk memilih harga penawaran per customer (fallback tersedia ke `products.sales_price`). Penghapusan tabel/data belum dilakukan sambil menunggu keputusan pengguna atas perubahan perilaku Sales Quotation. Data aktual DB tidak dapat diperiksa dari lingkungan kerja ini.
- **Master Barang:** kolom daftar dan field yang tampil pada form disederhanakan ke kategori, kode, nama, satuan, style, warna, ukuran, dan status sesuai referensi. Field tambahan yang dipakai workflow existing tetap dikirim sebagai hidden attributes agar nilai saat edit tidak hilang; tidak ada kolom database yang dihapus. Upload/edit gambar tidak lagi tampil pada form referensi.
- **Verifikasi:** `ProductReferenceTest` dan test Importir lulus, 3 test / 55 assertion; Blade view cache berhasil dibuat dan dibersihkan.
- **NEEDS CONFIRMATION:** format dan keunikan NPWP, makna/format APIP, lingkup multi-company, dan aturan delete ketika sudah direferensikan dokumen BC.
- **Belum dikerjakan:** Exportir dan relasi Importir ke dokumen BC; keduanya menunggu modul/instruksi berikutnya serta keputusan cardinality.


## 2026-11-13 — Penghapusan modul penjualan dan Price Lists

- **Status:** implementasi selesai; migrasi database perlu dijalankan pada environment aplikasi.
- **Keputusan pengguna:** Sales Quotation, Price List, Sales Order, dan alur turunannya ikut dihapus bersama datanya: Delivery, Sales Invoice, Customer Return, dan Piutang.
- **Dihapus:** route, menu, permission, controller, view, domain action/service/query/DTO, model, tabel transaksi, tabel Price Lists, serta konfigurasi nomor dokumen yang khusus untuk alur tersebut. Laporan POS tetap tersedia karena alur POS terpisah.
- **Ledger dipertahankan:** baris `stock_movements`, `cash_bank_transactions`, dan jurnal yang sudah tercatat tetap ada untuk menjaga saldo dan histori. FK ke dokumen penjualan dihapus; referensi dokumen lama pada baris kas/stok tidak lagi dapat dibuka. Migrasi menghapus data dokumen dan tidak dapat di-rollback; pulihkan dari backup bila perlu.
- **Test:** `SalesModuleRemovalTest` memastikan tabel/route modul tidak tersedia dan ledger tetap ada. Test terkait removal + produk + importir: **4 lulus / 86 assertion**. Suite penuh: **23 lulus, 10 gagal**; kegagalan yang dilaporkan berasal dari route `dashboard` yang tidak tersedia dan syntax error view profile yang sudah ada, bukan dari test penghapusan.
- **Langkah environment:** jalankan `php artisan migrate` untuk menerapkan penghapusan data dan schema.


## 2026-11-13 — Master Exportir

- **Status:** CRUD master selesai.
- **Lingkup:** migration/model, DTO, query DataTable, actions/service, request validation, controller/routes, permission, sidebar Setting, form modal, dan audit log.
- **Field sementara:** NPWP, nama, alamat, status aktif/nonaktif. APIP tidak disertakan karena merupakan field yang terdokumentasi untuk Importir.
- **Test:** `ExporterTest` memeriksa CRUD, permission, validasi, UI, serta audit log.
- **NEEDS CONFIRMATION:** keharusan/format NPWP, alamat, identitas khusus seperti NIB, dan hubungan Exportir dengan dokumen BC keluar.
- **Catatan sidebar:** permission Exportir ditambahkan untuk role bawaan lewat migration `2026_11_13_000004_grant_exporter_permissions`, agar menu muncul setelah `php artisan migrate` tanpa menjalankan ulang seeder.


## 2026-11-13 — Perbaikan kompatibilitas migrasi penghapusan penjualan

- **Masalah:** deployment MySQL tidak memiliki index dengan nama Laravel yang diasumsikan oleh `dropUnique`, sehingga migrasi berhenti sebelum menghapus tabel.
- **Perbaikan:** migrasi kini membaca foreign key dan index aktual dari schema, melepas foreign key lebih dulu, lalu menghapus index yang benar-benar ada sebelum kolom referensi. Nama custom dan index yang memang tidak ada ditangani.
- **Verifikasi:** migrasi fresh SQLite dan `SalesModuleRemovalTest` lulus; jalankan ulang `php artisan migrate` di MySQL setelah mengambil perubahan ini. Karena migrasi gagal sebelum selesai, Laravel belum mencatatnya sebagai selesai sehingga perintah dapat dijalankan ulang.


## 2026-11-13 — Penghapusan POS, Ticket QR Code, dan Outlet

- **Status:** kode modul dilepas; migration penghapusan schema/data ditambahkan dan perlu dijalankan dengan `php artisan migrate`.
- **Dihapus:** route, controller, domain, model, view, report, permission, tabel POS/sesi kasir, Ticket QR Code, Outlet, user-outlet, dan scan record. Filter audit log terkait modul tersebut juga dihapus.
- **Ledger dipertahankan:** transaksi kas/bank, mutasi stok, serta jurnal dipertahankan. FK/kolom sumber POS dilepas; catatan ledger historis tidak dihapus atau dihitung ulang.
- **Migration history:** file migration lama dipertahankan sebagai riwayat schema; migration forward baru menghapus tabel pada database yang sudah berjalan maupun instalasi baru.
- **Verifikasi:** `PosOutletRemovalTest` memastikan schema, routes, permissions, dan ledger sesuai scope penghapusan. Suite penuh: **27 lulus, 6 gagal**; kegagalan yang tampak tetap pada route `dashboard` dan syntax error partial profile yang sudah ada.


## 2026-11-13 — Penyederhanaan Master Supplier

- **Status:** CRUD diselaraskan dengan image13–14.
- **Field referensi:** kode, nama, alamat, contact person, telepon, fax, email, negara. Field yang tak tampak pada referensi dihapus dari form, tabel, DTO/model, impor/ekspor, seeder, dan database.
- **Status internal:** `is_active` tetap tersimpan untuk pemakaian pada alur pembelian; tidak ditampilkan pada form. Supplier baru aktif secara default.
- **Migration:** `2026_11_13_000006_simplify_supplier_fields` menambah fax/negara dan menghapus kolom supplier lama. Penghapusan nilai kolom bersifat destruktif dan tidak dapat di-rollback; backup diperlukan jika nilainya masih dibutuhkan.
- **Verifikasi:** ditambahkan test feature CRUD/validasi status dan tampilan. Jalankan `php artisan migrate` pada environment aplikasi untuk menerapkan perubahan schema.


## 2026-11-13 — Daftar Negara Supplier

- Label opsi kosong pada form Supplier diperbaiki dengan menambahkan `ui.common.select` untuk Bahasa Indonesia dan Inggris.
- Dropdown Negara kini memakai daftar statis `config/countries.php` berisi 264 kode negara/wilayah dan tetap menyertakan nilai negara lama yang tersimpan pada Supplier.
- Test Supplier memverifikasi label pilihan dan jumlah opsi negara.


## 2026-11-13 — Penyederhanaan Master Customer

- **Status:** CRUD diselaraskan dengan image15–16.
- **Field:** kode, nama, alamat, contact person, telepon, fax, email, negara; daftar/impor/ekspor/template memakai field yang sama.
- **Status internal:** `is_active` tetap disimpan untuk kelanjutan kebutuhan aplikasi, tetapi tidak ditampilkan pada form. Customer baru aktif secara default.
- **Migration:** `2026_11_13_000007_simplify_customer_fields` menambah fax/negara dan menghapus field Customer lama. Penghapusan data kolom bersifat destruktif dan memerlukan backup untuk pemulihan.
- **Test:** ditambahkan test feature CRUD, tampilan, daftar negara dan headings ekspor. Jalankan `php artisan migrate` pada environment aplikasi.


## 2026-11-13 — Master Style

- **Status:** CRUD sesuai image17; form inline berisi Kode Style/Nama Style, tabel berisi No/Aksi/Kode Style/Nama Style.
- **Lingkup:** migration, model, DTO, query DataTable, create/update/delete actions dan service, request validation, controller/routes, audit log, sidebar PPIC, serta permission seeder.
- **Permission:** `UsersAndPermissionsSeeder` memberi akses view kepada role user dan CRUD penuh kepada admin; migration grant menambahkan permission untuk role yang sudah ada supaya menu tampil setelah `php artisan migrate`.
- **Relasi barang:** kode Style dipertahankan sebagai referensi teks pada barang yang sudah ada. Style yang sedang dipakai barang tidak dapat dihapus atau diganti kodenya.
- **Verifikasi:** test feature CRUD, permission, sidebar, audit, dan perlindungan Style terpakai. Jalankan `php artisan migrate` pada environment aplikasi.
