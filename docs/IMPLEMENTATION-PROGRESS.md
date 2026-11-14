# Progres Implementasi

Pengerjaan dilakukan satu modul per instruksi. Tidak ada coding modul berikutnya sebelum pengguna memberikan arahan.

## 2026-11-14 — Scrap Masuk Gudang dan grup sidebar

- **Status:** implementasi kode Scrap Masuk Gudang selesai; migration perlu dijalankan pada environment aplikasi. Grup sidebar Bahan Sisa berisi Damage, Lost, dan Scrap Masuk Gudang. Grup Mesin tampil sebagai placeholder, tanpa operasi mesin.
- **Referensi UI:** tanggal/nomor penerimaan, scrap code/name, unit, qty; modal juga mempertahankan kode/nama perca, No. Produksi, dan keterangan. DataTable pemilih barang; filter tanggal dan ekspor Excel.
- **Stok:** produk scrap dipilih dari kategori `WASTE`; penerimaan menambah ProductStock dan lot melalui StockMovement. Edit/hapus membuat mutasi koreksi dan dicegah jika terdapat transaksi lebih baru untuk SKU yang sama. Baris dapat dirujuk dari Kartu Stok.
- **Izin/nomor:** permission `scrap-receipts.view/create/update/delete`; nomor dokumen `scrap_receipt` prefix SCI. Migration memasang hak untuk role saat ini dan menambah relasi mutasi stok.
- **NEEDS CONFIRMATION:** apakah sumber perca harus kategori tertentu, arti dan relasi No. Produksi, aturan valuasi/jurnal, dan apakah satu penerimaan dapat memuat beberapa item. Sampai dikonfirmasi, nomor produksi teks opsional dan nilai biaya scrap nol.
- **Verifikasi:** `php artisan test --compact tests/Feature/Inventory/ScrapReceiptTest.php` lulus (2 test, 21 assertions); `php artisan view:cache`, `php artisan view:clear`, `php artisan route:list --path=scrap-receipts`, PHP lint untuk controller/action/test, dan `git diff --check` berhasil.
- **Environment:** jalankan `php artisan migrate` untuk menambah tabel, permission, penomoran, dan FK transaksi stok.

## 2026-11-13 — Goods Receipt (GRN)

- **Status:** penyesuaian daftar, form, dan ekspor selesai.
- Daftar menampilkan satu baris per GRN, berisi nomor/tanggal, supplier, invoice/tanggal, Material Request, PO supplier, dan keterangan. Kode item sampai subtotal ada di dialog Detail. Aksi detail/PDF/unduh PDF berupa ikon kecil.
- Filter rentang tanggal diterapkan pada daftar dan Excel. Setiap kode item diekspor pada baris tersendiri.
- Form menyimpan nomor/tanggal invoice dan mata uang, serta menampilkan PO, MO, style, supplier dan item dari PO terpilih. Data kepabeanan dan pencatatan lot/stok tetap dipertahankan.
- Migration `2026_11_13_000016_add_invoice_fields_to_goods_receipts.php` menambah kolom invoice/currency; migration `2026_11_13_000017_add_goods_receipt_approval.php` menambah status approval, approver/waktu, serta snapshot nomor lot; keduanya perlu dijalankan pada environment aplikasi.
- **Test:** `GoodsReceiptsExportTest` lulus, 2 test / 8 assertion. File PHP terkait lulus `php -l`.
- **NEEDS CONFIRMATION:** sumber daftar mata uang belum tersedia; nilai awal diambil dari MO bila ada, selain itu USD. Currency dapat dikoreksi saat mencatat GRN.
- MO pada form penerimaan otomatis terisi dari rantai PO Supplier → Purchase Request → Material Request → Material Order saat PO dipilih.
- Aksi hapus GRN memerlukan permission `goods-receipts.delete`. Penghapusan membalik stok/lot, kuantitas PO, serta jurnal; ditolak bila sudah ada retur/pembayaran atau mutasi stok lanjutan.
- GRN baru menunggu approval. Stok, lot, dan jurnal hanya diposting lewat aksi Approve (`goods-receipts.approve`). GRN historis dimigrasikan menjadi approved untuk mencegah posting ganda; data approver/waktu historis tidak tersedia.

## 2026-11-13 — Material Order (MO)

- **Status:** implementasi CRUD selesai; migration perlu dijalankan pada environment aplikasi.
- **Referensi lanjutan:** form MO kini mengikuti field No MO manual, tanggal MO, Due Date, No CBD, PO Buyer, Currency, Destination, Buyer; detail Finished Good: Item Code/Search, Item Name, Unit, Style, Qty, Price Per Unit, Total Price, Description. Picker item memakai DataTable.
- **Perubahan:** layar/menu Production Order diganti menjadi Material Order dan dipindahkan ke PPIC. Satu baris menyimpan satu Finished Good; beberapa baris berbagi nomor MO dan header. Total dihitung dari qty × harga satuan. BOM aktif menghasilkan snapshot kebutuhan Material Request.
- **Perlindungan relasi:** edit/hapus ditolak setelah ada Material Request atau catatan tahap. Migration menghapus baris Production Order/material/stage lama, melepaskan referensi Material Request lama, dan melepas referensi stage dari stock movements sebelum membersihkan stage; request dan mutasi stok historis tidak dihapus.
- **Test:** `MaterialOrderTest` — **lulus**, 1 test / 16 assertion; mencakup form, beberapa baris bernomor MO sama, sinkronisasi header, CRUD, total harga, dan audit log.
- **NEEDS CONFIRMATION:** aturan wajib/unik PO Buyer, CBD, currency/destination, harga dan pajak. Tanggal MO dan tempo wajib pada implementasi.
- **Jalankan:** `php artisan migrate` untuk membersihkan data Production Order lama (migration 000014), lalu menambah field MO dan mengizinkan nomor MO berulang (migration 000015).

## 2026-11-13 — Master Importir

- **Status:** selesai untuk scope CRUD master.
- **Lingkup:** migration/model, DTO, query DataTable, create/update/delete actions dan service, request validation, controller/routes, permission, navigasi, form daftar, dan audit log.
- **Field:** NPWP, nama, alamat, APIP, status aktif/nonaktif. Ini mengikuti field yang terlihat pada referensi; aturan bisnis yang tidak terlihat tidak diasumsikan.
- **Test:** `php artisan test --compact tests/Feature/Masters/ImporterTest.php` — **lulus**, 2 test / 13 assertion. `php artisan route:list --path=super/importers` menampilkan 5 route; file PHP yang disentuh lolos `php -l`.
- **Suite keseluruhan:** `php artisan test --compact` — 21 lulus, 11 gagal. Kegagalan yang terlihat berasal dari area lain: route bernama `dashboard` tidak terdefinisi pada test auth, syntax error pada partial profile, dan fixture user yang tidak ditemukan pada `CustomerReturnTest`. Tidak ada stack trace kegagalan yang menunjuk ke modul Importir.
- **Perbaikan CRUD lanjutan:** skrip modal sebelumnya berada di dalam section konten sehingga dirender sebelum jQuery/Bootstrap dari layout; dipindahkan ke `@push('scripts')`. Tombol edit/hapus kini memakai payload dan action partial DataTable yang sama dengan master kategori/satuan, termasuk validasi AJAX dan konfirmasi hapus. Test modul dijalankan ulang setelah perbaikan.
- **Sidebar:** sesuai arahan lanjutan, User/Role/Permission berada di grup Access; Category/Unit/Importer/Companies/General/Email/Document Numbering berada di Setting; master lain dan menu pengadaan berada di PPIC. Gudang, Produksi, Sales, Akuntansi, dan Laporan tetap dikelompokkan tersendiri. Grup tanpa halaman/route tidak ditampilkan sebagai menu kosong.
- **Navigasi inventory:** tautan Price Lists dihapus dari sidebar atas permintaan pengguna karena tidak masuk konteks IT Inventory; kode modul, route, permission, dan datanya tetap dipertahankan.
- **Price Lists:** penghapusan tabel/data dan alur Sales terkait dilaksanakan pada bagian progres berikutnya setelah keputusan pengguna; catatan implementasi lama di bawah diperbarui oleh migration forward.
- **Master Barang:** kolom daftar dan form mengikuti kategori, kode, nama, satuan, style, warna, ukuran, dan status. Pada progres terbaru, field tambahan yang tidak dipakai dihapus dari model, validasi, dan database; field operasional yang masih dipakai alur inventory tetap disimpan secara internal.
- **Verifikasi:** `ProductReferenceTest` dan test Importir lulus, 3 test / 55 assertion; Blade view cache berhasil dibuat dan dibersihkan.
- **NEEDS CONFIRMATION:** format dan keunikan NPWP, makna/format APIP, lingkup multi-company, dan aturan delete ketika sudah direferensikan dokumen BC.
- **Belum dikerjakan:** Exportir dan relasi Importir ke dokumen BC; keduanya menunggu modul/instruksi berikutnya serta keputusan cardinality.


## 2026-11-13 — Penghapusan modul penjualan dan Price Lists

- **Status:** implementasi selesai; migrasi database perlu dijalankan pada environment aplikasi.
- **Keputusan pengguna:** Sales Quotation, Price List, Sales Order, dan alur turunannya ikut dihapus bersama datanya: Delivery, Sales Invoice, Customer Return, dan Piutang.
- **Dihapus:** route, menu, permission, controller, view, domain action/service/query/DTO, model, tabel transaksi, tabel Price Lists, serta konfigurasi nomor dokumen yang khusus untuk alur tersebut. Laporan POS dan permission terkait turut dihapus pada pembersihan POS lanjutan.
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

- **Status:** CRUD sesuai image17; modal tambah/edit berisi Kode Style/Nama Style, tabel berisi No/Aksi/Kode Style/Nama Style. Modal/action dropdown mengikuti pola master project.
- **Lingkup:** migration, model, DTO, query DataTable, create/update/delete actions dan service, request validation, controller/routes, audit log, sidebar PPIC, serta permission seeder.
- **Permission:** `UsersAndPermissionsSeeder` memberi akses view kepada role user dan CRUD penuh kepada admin; migration grant menambahkan permission untuk role yang sudah ada supaya menu tampil setelah `php artisan migrate`.
- **Relasi barang:** kode Style dipertahankan sebagai referensi teks pada barang yang sudah ada. Style yang sedang dipakai barang tidak dapat dihapus atau diganti kodenya.
- **Verifikasi:** test feature CRUD, permission, sidebar, audit, dan perlindungan Style terpakai. Jalankan `php artisan migrate` pada environment aplikasi.


## 2026-11-13 — Pengelompokan menu Master

- Supplier, Customer, Style, Items and Materials, dan BOM dipindahkan ke grup sidebar Master.
- PPIC kini berisi Stock Opname, Material Request, Purchase Request, dan Purchase Order sesuai permission yang dimiliki.


## 2026-11-13 — Stock Count ke Warehouse

- Menu Stock Opname/Stock Count dipindahkan dari grup PPIC ke Warehouse & Inventory; label grup gudang juga tampil saat pengguna hanya memiliki permission `stock-opnames.view`.


## 2026-11-13 — Export, Import, dan Modal Item

- Export dan template import diselaraskan dengan field daftar: Kategori, Kode Barang, Nama Barang, Satuan, Kode Style, Warna, Ukuran, Available.
- Import mencocokkan kategori/satuan dan Style ke master, memvalidasi kode unik, menerima status Available/Unavailable, memberi default pada atribut internal, dan membuat produk melalui `ProductService` agar audit tercatat.
- Modal tambah/edit diubah dari lebar penuh (`modal-xl`) menjadi modal Bootstrap `modal-lg` yang terpusat dan scrollable.
- **Verifikasi:** `ProductReferenceTest` mencakup headings template/export, import CSV, default internal, dan status.

## 2026-11-13 — Pilihan Style dan Ketersediaan Item

- Input Style pada Item kini mencari dan memilih kode/nama dari database `styles` lewat endpoint pencarian; kode yang disimpan harus ada pada master Style.
- Field **Available** ditambahkan sebagai `products.is_available`, terpisah dari `is_active` yang tetap dipakai workflow. Daftar menampilkan `Masih Ada` atau `Tidak Ada`; item lama default tersedia.
- Export/import/template menambahkan kolom Available dan menggunakan referensi Style. Migration: `2026_11_13_000010_add_is_available_to_products_table`.
- **Verifikasi:** ProductReferenceTest dan StyleTest lulus, **5 test / 76 assertion**; view cache, lint PHP, dan `git diff --check` berhasil.
- Terapkan perubahan schema dengan `php artisan migrate`.

## 2026-11-13 — Seeder Kategori dan Satuan Referensi

- `CategorySeeder` kini melakukan upsert delapan kategori dari daftar Item pada referensi.
- `UnitSeeder` kini melakukan upsert 47 kode satuan yang diberikan. ` DRUM` dinormalisasi menjadi `DRUM`; nama unit mengikuti kode sumber dan symbol dibiarkan kosong agar dropdown menampilkan kode tanpa duplikasi.
- Kedua seeder sudah dipanggil oleh `DatabaseSeeder`; pemanggilan ulang tidak membuat duplikat. Baris master lama yang tidak ada dalam daftar baru tidak dihapus oleh seeder untuk menjaga referensi data yang mungkin sudah dipakai.
- Verifikasi: `ReferenceDataSeederTest` lulus.

## 2026-11-13 — Pembersihan Field Item dan Migration POS

- Field item yang tidak digunakan modul aktif dan tidak ada pada referensi form dihapus dari model, DTO, validasi, payload edit, impor/seeder dan skema: barcode, SKU, nama singkat, HS code, spesifikasi kain, negara asal, harga jual, pajak, flag diskon/purchase/sales, dan deskripsi.
- Field untuk identitas item, kategori/unit/style, atribut warna/ukuran, jenis item untuk BOM, harga beli/kendali stok internal, serta ketersediaan tetap digunakan.
- Migration `2026_11_13_000011_remove_unused_product_fields` menghapus kolom lama dan index terkait. Penghapusan schema membuang nilai lama pada kolom tersebut; backup database sebelum migrate diperlukan jika data lama perlu disimpan.
- Migration khusus Outlet, Ticket QR, User Outlet, Scan Record, dan POS dihapus dari source tree. Migration forward `2026_11_13_000005_remove_pos_ticket_outlet_modules` tetap dipertahankan agar database yang sebelumnya sudah menjalankan migration lama dibersihkan ketika `php artisan migrate` dijalankan.
- **Verifikasi:** ditambahkan pemeriksaan test untuk schema item dan tampilan referensi; hasil test dicatat setelah dijalankan.

## 2026-11-13 — Audit Log Terpusat

- Middleware `AuditMutationRequests` mencatat request berhasil untuk semua route web dengan metode POST/PUT/PATCH/DELETE, ditambah aksi ekspor/unduh/cetak/kirim email yang memakai GET.
- Log mencatat pengguna, modul, aksi, route, target model saat tersedia, metadata request, dan input baru yang telah disanitasi. Field kata sandi, token, secret, credential, dan API/private key disamarkan; unggahan file tidak disimpan ke payload audit.
- Aksi yang sudah menulis audit rinci melalui `AuditLogService` menandai request agar middleware tidak membuat catatan duplikat. Akses baca biasa seperti halaman dan DataTable tidak dicatat.
- **Verifikasi:** `AuditMutationRequestsTest` memastikan route tanpa audit khusus tetap tercatat dan nilai sensitif disamarkan; `ImporterTest` memastikan CRUD yang sudah tercatat eksplisit tidak menghasilkan duplikasi.

## 2026-11-13 — Audit Log Master Filter

- CRUD actions Unit, Category, Style, Supplier, dan Customer sudah menulis log `CREATE` melalui `AuditLogService`; test HTTP kini menguji kelimanya.
- Halaman Audit Log sebelumnya hanya menyediakan opsi filter modul AUTH/USER/ROLE/PERMISSION/SETTING dan rentang tanggal default hari ini. Ini membuat log master yang bermodul UNIT/CATEGORY/STYLE/SUPPLIER/CUSTOMER sulit ditemukan atau tersembunyi.
- Opsi modul dan aksi sekarang diambil dari nilai yang tersedia di audit log, serta rentang tanggal awalnya kosong agar semua histori terlihat sampai filter dipilih.
- **Verifikasi:** seluruh test pada `tests/Feature/Audit` dan `ImporterTest` lulus.

## 2026-11-13 — Penyesuaian BOM berdasarkan Finished Goods dan Style

- Daftar BOM kini mengambil Item aktif dengan kategori berkode `FG` atau bernama `Finished Goods`, yang sudah memiliki `style_code`; kolom menampilkan Style, Item Code, Finished Goods, dan Unit sesuai referensi.
- Tombol Input membuka BOM untuk Item tersebut, sedangkan Detail menampilkan daftar material/support, unit, consumption per PCS, scrap (%), dan pembuat. Item Finished Goods tidak dapat dipilih sebagai material BOM.
- Field Span ditambahkan pada detail material lewat migration `2026_11_13_000012_add_span_to_bill_of_material_items`; nilai data lama default 0.
- **Verifikasi:** Audit feature tests lulus, Blade view cache berhasil, dan file PHP terkait lolos lint. Suite penuh masih memiliki kegagalan lama di route `dashboard` dan partial profile; selain itu satu assertion test filter Audit diperbarui mengikuti default tanggal hari ini.

## 2026-11-13 — Form Input BOM dan Modal Material

- Form Input BOM disederhanakan: field version, nama BOM, status, output quantity, tanggal efektif, dan catatan tidak lagi ditampilkan. Backend mengisi default internal, sementara status/versi BOM lama tetap dipertahankan ketika material diperbarui.
- Material dipilih melalui modal pencarian yang memuat semua Item stok aktif; item terpilih tampil sebagai tabel berisi kode/nama, unit, consumption per PCS, span, dan scrap. Komponen lama tetap mempertahankan metadata internal operation/notes saat diedit.
- Verifikasi: Blade cache dan lint berhasil; test Audit terkait tetap lulus.

## 2026-11-13 — DataTable Modal dan Aksi Edit/Hapus BOM

- Pemilih item pada modal BOM memakai DataTable dengan pencarian, pagination, dan pilihan 5/10/25/50 baris; item yang sudah dipilih dinonaktifkan dan item finished-good saat ini tidak dapat dipilih sebagai komponennya sendiri.
- Daftar BOM kini menyediakan Detail, Edit, dan Hapus untuk BOM yang sudah ada. Halaman Detail menyediakan Edit BOM dan aksi Hapus untuk setiap rincian material.
- BOM hanya dapat dihapus jika belum direferensikan Production Order atau Material Request; penghapusan BOM yang diizinkan menghapus rincian melalui relasi cascade.
- Verifikasi: Blade cache, PHP lint, route list, dan `git diff --check`.

## 2026-11-13 — Penyelarasan UI DataTable BOM

- Daftar Finished Goods BOM dan rincian material memakai DataTable dengan pencarian, pagination, jumlah baris, nomor urut per halaman, dan label Bahasa Indonesia.
- Card, header tabel, batas baris, filter, dan tombol diselaraskan ke gaya soft: card putih dengan shadow ringan, header table muted, serta tombol aksi kecil ber-outline.
- Modal pemilih material tetap memakai DataTable dengan pagination agar daftar panjang tidak memenuhi modal.
- Verifikasi Blade cache dan lint berhasil. Suite penuh: 38 lulus, 6 gagal di test lama terkait route `dashboard` dan syntax error halaman profile.

## 2026-11-13 — Export BOM

- Tombol Export Excel ditambahkan pada daftar BOM, memakai permission `boms.view`.
- Isi ekspor satu baris per material BOM produk Finished Goods, dengan kolom NO, STYLE, KODE PRODUK, NAMA PRODUK, SATUAN PRODUK, KODE BAHAN, NAMA BAHAN, SATUAN BAHAN, KONSUMSI, SPAN, SCRAP, USER INPUT.
- Verifikasi route export, Blade cache, PHP lint, dan `git diff --check` berhasil.

## 2026-11-13 — Bahasa dan Pagination DataTable BOM

- DataTable daftar BOM, rincian, dan modal material sekarang mewarisi bahasa DataTable global dari layout (Indonesia/Inggris) alih-alih label hardcoded Indonesia.
- Opsi jumlah baris disamakan menjadi 5, 10, 50, dan 100 agar kontrol Show/page konsisten di ketiga tabel BOM.
- Verifikasi: Blade cache dan `git diff --check` berhasil.

## 2026-11-13 — Penyelarasan Tampilan Material Order

- Filter tanggal MO dipisahkan di atas card tabel dan memakai komponen filter yang sama dengan Material Request; default tanggal dan aksi Hari Ini mengikuti komponen tersebut.
- Header card DataTable MO dihilangkan agar tabel lebih ringkas. Aksi edit/hapus dipindahkan ke dropdown tiga titik sesuai pola Unit.
- Verifikasi: Blade view cache berhasil.

## 2026-11-13 — Penyelarasan Purchase Request

- Daftar Purchase Request kini menampilkan PO Buyer serta Style / Qty produksi dari MO/MR terkait; kode material PR tidak dipakai sebagai identitas barang produksi. Nilai kuantitas menghilangkan nol desimal yang tidak diperlukan.
- Aksi detail, PDF, unduh, approve/reject, dan buat PO memakai ikon ringkas tanpa border, rata kanan, mengikuti Material Request.
- Ekspor Excel mencakup kolom yang sama dan mengikuti filter tanggal aktif pada daftar.
- Verifikasi: PHP lint, Blade view cache, test ekspor, dan `git diff --check`.

## 2026-11-13 — Ekspor Material Order dan Material Request

- Tombol ekspor Excel ditambahkan di halaman MO dan Material Request, membawa rentang tanggal filter saat ini.
- Ekspor MO memakai Tanggal PO dan company context aktif; ekspor Material Request memakai tanggal pengajuan. Rentang terbalik ditolak.
- Verifikasi: query ekspor menguji filter tanggal, PHP lint, Blade view cache, dan `git diff --check`.

## 2026-11-13 — Penyempurnaan Purchase Order

- Aksi PO diseragamkan ke ikon kecil tanpa border, rata kanan, mengikuti Material Request.
- Tabel PO menampilkan Style / Qty produksi dan rincian kode material serta kuantitas dalam badge ringkas dengan tinggi terbatas.
- Ekspor Excel PO memuat informasi order, Style / Qty produksi, nilai dan status; setiap material menjadi baris sendiri dengan kolom kode, nama, qty, dan unit terpisah. Ekspor mengikuti filter tanggal PO.
- Verifikasi: PHP lint, Blade view cache, test ekspor PO dan pemeriksaan diff.

## 2026-11-13 — Required Items untuk Material Request dan Purchase Request

- Daftar MR dan PR menampilkan Required Items dalam badge kompak berisi kode dan kuantitas item.
- Ekspor MR dan PR kini menghasilkan satu baris per item; kode, nama, kuantitas, dan satuan berada pada kolom terpisah, sementara informasi dokumen diulang pada tiap baris.
- Verifikasi: test ekspor untuk MR/PR, PHP lint, Blade view cache, dan pemeriksaan diff.

## 2026-11-13 — Pemilih Item Material Request

- Pemilihan item manual pada form MR menggunakan popup DataTable dengan pencarian, pagination, kolom kode/nama/unit, dan aksi Pilih. Item terpilih masuk ke baris item request; isi dari BOM/MO tetap dapat dimuat seperti sebelumnya.
- Verifikasi: Blade view cache dan pemeriksaan diff.
# Progress — Goods Receipt

- Daftar GRN memakai satu baris per item dan menampilkan relasi supplier, invoice, style, MO, PO supplier, item, kuantitas, harga, currency, total, dan keterangan.
- Filter tanggal dipakai bersama tombol ekspor Excel; ekspor menghasilkan satu baris untuk setiap item.
- Aksi detail, PDF, dan unduh PDF dibuat ringkas dengan ikon.
- Form penerimaan menambahkan data invoice/currency dan menampilkan PO/MO/style yang dipilih.
- Alur transaksi yang sudah ada tetap menangani batas kuantitas PO, lot, stok, dan jurnal. Approval GRN tetap di luar scope karena alur persetujuannya belum selesai/terkonfirmasi.
- NEEDS CONFIRMATION: referensi menunjukkan mata uang, tetapi daftar mata uang sumber belum terlihat; form memakai kode currency teks singkat berisi nilai bawaan MO bila tersedia, atau USD.

# Damaged Items (13 November 2026)

- Implemented document header/detail CRUD with modal entry/edit and server-side DataTables item picker/list.
- Added `damaged-items.view/create/update/delete` permissions and Warehouse & Inventory navigation entry.
- Meeting Minutes number is generated from company Document Numbering (`damaged_item`, default prefix `BRSK`) with a preview in the add modal; existing configurations receive it through migration `2026_11_13_000019_add_damaged_item_document_numbering.php`.
- Add form includes the session CSRF token. Delete is a soft cancellation that reverses stock and keeps the movement/audit history; migration `2026_11_13_000020_add_soft_deletes_to_damaged_item_lines.php` adds the soft-delete column.
- Posting a detail reduces product and lot stock transactionally, records a linked stock movement, journals the inventory loss, and writes audit history. Editing posts compensating movements.
- Added migrations `2026_11_13_000018_create_damaged_item_tables.php`, `000019_add_damaged_item_document_numbering.php`, and `000020_add_soft_deletes_to_damaged_item_lines.php`.
- Verified with `DamagedItemStockTest` (1 test, 20 assertions), `GoodsReceiptApprovalTest` (1 test, 12 assertions), route listing, PHP lint, and `git diff --check`.
- Apply pending migrations to the application database before using the module.
- NEEDS CONFIRMATION: approval requirement and rejected-goods quarantine handling.

# Lost Items (13 November 2026)

- Added Lost Items with a DataTable, item picker modal, add/edit modal, compact action dropdown, and Excel export.
- Stock reductions, edit corrections, and cancellations use separate `lost_item`/`lost_item_reversal` movements linked to the lost-item document and are included in Stock Card and audit logs.
- Meeting Minutes numbering uses company Document Numbering type `lost_item`, default prefix `BHLNG`.
- Added migrations `2026_11_13_000021_create_lost_item_tables.php` and `000022_add_lost_item_document_numbering.php`.
- Verified with `LostItemStockTest` (1 test, 23 assertions), including export, stock/lot balance, and action dropdown.
- NEEDS CONFIRMATION: approval and location-based loss tracking.

## 2026-11-13 — Order Bahan Baku

- **Status:** CRUD selesai sesuai referensi; migration 000023–000025 perlu dijalankan pada environment aplikasi.
- Pemilihan MO memakai DataTable. Tombol Ambil MO menggabungkan kebutuhan material dari semua baris bernomor MO sama dan mengisi kode/nama, style, unit, dan jumlah.
- Daftar memakai filter tanggal, dropdown aksi ringkas, dan ekspor Excel dengan satu item per baris. Form mendukung edit tanggal/jumlah dan penghapusan.
- Nomor dokumen menggunakan Document Numbering `raw_material_order` (prefix awal OBB). Permission dan menu ditambahkan pada grup Production; migration grant memberi role user akses view dan admin akses CRUD.
- Order hanya menyimpan dokumen serta audit; tidak memutasi stok.
- **Test:** `php artisan test --compact tests/Feature/Manufacturing/RawMaterialOrderTest.php` lulus, 1 test / 16 assertion. `route:list` menampilkan 10 route modul.
- **NEEDS CONFIRMATION:** batas jumlah order terhadap sisa kebutuhan dan apakah MO boleh memiliki beberapa Order Bahan Baku.

## 2026-11-13 — Kirim ke Produksi (MIS)

- **Status:** modul MIS tersambung ke rincian Order Bahan Baku; migration 000026–000028 perlu dijalankan.
- Nomor OBB kini unik per item. Daftar MIS menampilkan satu baris per dokumen Order Bahan Baku dari produksi beserta No MO, No OBB, MIS terbaru, dan tanggal. Tombol Detail biru berarti masih ada item yang belum terkirim penuh; tombol merah berarti seluruh item sudah terkirim.
- Aksi kirim memperlihatkan jumlah request, kirim sebelumnya, kirim kini, dan total. Pengiriman parsial tidak boleh melebihi sisa OBB, kebutuhan MO, atau stok/lot.
- Detail OBB menampilkan seluruh item; pengguna dapat memilih satu atau beberapa item yang masih tersisa untuk dikirim. Satu nomor MIS menjadi header dengan detail terpisah per item OBB. Mutasi stok/lot dan issued quantity tetap dicatat per baris.
- PDF MIS tersedia untuk ditampilkan dan diunduh; detail PDF memisahkan nomor item OBB, kode/nama, style, satuan, dan jumlah.
- Jalur issue lama dari halaman Material Request tetap berjalan dengan sumber lama.
- Aksi edit/hapus tersedia pada riwayat MIS yang bersumber dari OBB. Koreksi membuat mutasi pembalik positif, memulihkan lot/stok serta issued quantity OBB/MO, dan mempertahankan mutasi serta jejak audit lama. Edit quantity ditolak jika jumlahnya sudah tercatat sebagai konsumsi produksi.
- Jalur MIS lama yang hanya bersumber dari Material Request tetap hanya dapat dilihat karena alokasi issue ke material MO tidak tersimpan per baris.
- Permission `material-issues.update/delete` diberikan ke admin dan super admin melalui migration `2026_11_13_000030_grant_material_issue_edit_delete_permissions.php`.
- **Test:** `MaterialIssueTest` lulus, 1 test / 49 assertion; mencakup daftar satu baris per OBB, status biru/merah untuk progres/penuh, satu MIS dengan beberapa item, pengiriman parsial, edit, hapus, PDF, mutasi koreksi, stok/lot, issued quantity, dan audit.
- **NEEDS CONFIRMATION:** approval MIS dan lokasi gudang tujuan.

## 2026-11-13 — Penerimaan Produksi dari Gudang

- Menambahkan menu Produksi dengan filter tanggal, DataTable dokumen MIS, status penerimaan, detail item, dan aksi konfirmasi.
- Konfirmasi menyimpan status, pengguna, waktu, dan audit pada dokumen MIS. Tidak ada mutasi stok tambahan; stok gudang telah berkurang ketika MIS dikirim.
- Permission `production-receipts.view/approve` disediakan lewat seeder dan migration `2026_11_13_000032_grant_production_receipt_permissions.php`; metadata penerimaan ditambahkan oleh migration `2026_11_13_000031_add_production_receipt_confirmation_to_mis.php`.
- Kolom PO Supplier pada detail saat ini kosong karena hubungan OBB/MIS ke PO supplier tidak tersedia.
- **Test:** `MaterialIssueTest` lulus, 1 test / 61 assertion; meliputi daftar, detail multi-item, konfirmasi, pencatatan audit, dan memastikan saldo tidak berubah lagi.
- **NEEDS CONFIRMATION:** penerimaan parsial dan pemetaan PO Supplier.

## 2026-11-13 — Referensi MIS pada Kartu Stok

- Mutasi keluar MIS tetap tercatat sebagai stok keluar gudang; nomor dokumen MIS kini ditampilkan pada kolom referensi kartu stok dan ekspor Excel.
- Konfirmasi penerimaan oleh produksi tidak dicatat sebagai stok masuk gudang karena akan membatalkan pengurangan saat MIS dikirim. Jika saldo stok produksi perlu dilacak, diperlukan ledger/lokasi produksi terpisah.
- **Test:** `MaterialIssueTest` lulus, 1 test / 64 assertion, termasuk nomor MIS dan kuantitas keluar pada histori kartu stok.

## 2026-11-13 — Pemakaian Material Non-Produksi

- Menambahkan halaman Gudang dengan filter tanggal, DataTable, form modal, pemilih item DataTable, serta aksi edit/hapus dalam dropdown ringkas.
- No. Transaksi memakai Document Numbering `non_production_material_usage` (prefix awal PBN), baik untuk company yang sudah ada maupun company baru.
- Transaksi menyimpan tanggal, item, qty, harga, dan total; mengurangi stok/lot, mencatat mutasi dan jurnal, dan muncul pada referensi/tipe kartu stok. Edit dan hapus mencatat mutasi kompensasi serta audit.
- **Test:** `NonProductionMaterialUsageTest` lulus, 1 test / 26 assertion; mencakup preview nomor, CRUD, koreksi stok/lot, nomor referensi kartu stok, dan format detail.
- **NEEDS CONFIRMATION:** akun beban 6100 serta satu nomor per item (referensi menampilkan satu item per form).
- Migration 000033 memakai nama FK pendek dan memeriksa tabel/kolom/index/FK yang sudah ada supaya aman dilanjutkan setelah migrasi sebelumnya gagal di tengah proses.
- Label filter tanggal memakai `ui.common.show_data`; ekspor Excel tersedia dan menerapkan rentang tanggal yang sama dengan tabel.

## 2026-11-13 — Penghapusan Retur Pembelian

- Menambahkan aksi hapus dengan konfirmasi dan permission `purchase-returns.delete` untuk admin/super admin (migration `2026_11_13_000029_grant_purchase_return_delete_permission.php`).
- Penghapusan mengurangi kembali kuantitas retur pada GRN, memulihkan saldo stok dan lot, membalik jurnal otomatis, lalu menghapus dokumen/detail/mutasi retur dalam satu transaksi dan mencatat audit.
- Untuk menjaga urutan kartu stok, retur tidak dapat dihapus jika ada mutasi stok yang lebih baru untuk barang yang sama; pesan validasi menjelaskan penyebabnya.
- **Test:** `PurchaseReturnDeletionTest` lulus, 2 test / 12 assertion (pemulihan stok/lot dan perlindungan dari mutasi berikutnya).
# Return to Warehouse / Receipt from Production — 2026-11-13

- Implemented production return entry with company document numbering (`RETG` default), item DataTable selection, pending edit/delete, date filtering and Excel export.
- Implemented separate warehouse receipt approval/detail/cancel page, with date-filtered Excel export and permission/menu entries.
- Approval posts a positive stock movement and inventory lot; cancellation records a negative movement and checks current available stock. Stock Card references and labels include both.
- Verified focused feature test: `ProductionReturnTest` passed (1 test, 11 assertions), including the Warehouse Receipt DataTable response (`mo` field).
- **NEEDS CONFIRMATION:** supplier PO canonical relation, partial/multi-line return documents, maximum return quantity vs MIS issue, and cost/original lot allocation. The current reference has one item entry per save, optional MO, and only an editable PO supplier text reference.

## 2026-11-13 — Sewing

- Sewing dicatat sebagai stage `sewing` pada `production_order_stage_entries`; migration `000042` menambahkan nomor Sewing dan `000043` menambahkan Document Numbering prefix `SEW` untuk company lama. Company baru dan permission seeder ikut diperbarui; migration `000044` memberi permission view/create/update/delete.
- Form CRUD modal mengambil MO aktif dengan output Cutting tersedia dan bahan dari daftar material MO. Qty Sewing dibatasi sisa hasil baik Cutting; kirim ke Packing + reject harus sama dengan qty Sewing.
- Daftar memiliki filter tanggal, DataTable ringkas dengan rincian terbuka melalui Detail, edit/hapus, export Excel, audit trail, serta menu Produksi.
- Waste material hanya dicatat dan tidak memutasi stok. Edit/hapus diblokir bila stage finishing/packing sudah ada.
- **Test:** `SewingTest` lulus, 1 test / 19 assertion. `route:list --name=super.sewing`, Blade view cache, dan `git diff --check` juga berhasil.
- **NEEDS CONFIRMATION:** apakah waste Sewing harus mengurangi saldo/cost material; istilah Cutting Reception pada referensi dipetakan sebagai total hasil baik Cutting MO.

## 2026-11-13 — Hasil Packing

- Menu Produksi berisi daftar ringkas, filter tanggal, detail baris, ekspor Excel, serta modal tambah/edit dengan picker MO dan material berbasis DataTable.
- Nomor PKG dikelola Document Numbering. Permission view/create/update/delete ada di seeder dan migration `2026_11_13_000047`.
- Qty Packing dibatasi hasil baik Sewing tersisa; hasil baik + reject wajib sama dengan qty Packing. Waste material hanya dicatat.
- Hasil baik menambah saldo barang jadi, inventory lot, dan kartu stok. Edit/hapus menghasilkan mutasi kompensasi; data stage dihapus secara soft delete untuk menjaga jejak relasi.
- Endpoint stage generik tidak lagi menerima Packing agar pencatatan melewati modul bernomor ini.
- **Test:** `php artisan test tests/Feature/Manufacturing/PackingTest.php` lulus, 2 test / 26 assertion, termasuk penomoran, daftar/export, batas kuantitas Sewing, saldo/lot, dan reversal saat hapus.
- **NEEDS CONFIRMATION:** field No Planning pada referensi saat ini dipetakan ke No CBD MO. Status MO tidak otomatis menjadi selesai setelah Packing.

## 2026-11-13 — Packing / Barang Jadi

- Menambahkan layar terpisah sesuai referensi terbaru: No Barang Jadi prefix `FIN`, tanggal, style/item, terima dari Packing, hasil Barang Jadi, MO, dan PO Buyer.
- MO dipilih melalui DataTable dan hanya ditawarkan saat masih ada output baik Packing yang belum tercatat. Tambah/edit via modal, edit/hapus ikon kecil, filter tanggal, export Excel, audit, dan permission.
- Data memakai stage `finishing` pada `production_order_stage_entries`. Pencatatan tahap ini tidak menambah stok lagi karena Hasil Packing sudah membuat mutasi stok barang jadi; hal ini mencegah saldo ganda. Pengubahan/penghapusan Hasil Packing diblokir bila tahap ini sudah ada.
- **NEEDS CONFIRMATION:** Screenshot menunjukkan angka "Terima dari Packing" dan "Hasil Barang Jadi" yang tidak selalu sama; aturan validasi hanya membatasi hasil agar tidak melebihi qty terima. Konfirmasi bila ada rumus hasil lain.
- **Test regresi:** `php artisan test tests/Feature/Manufacturing/PackingTest.php tests/Feature/Manufacturing/FinishedGoodsTest.php` lulus, 4 test / 48 assertion; route terdaftar, Blade berhasil dikompilasi, dan `git diff --check` bersih.
# Good Sent Notes (GSN) and Approval Surat Jalan

- GSN uses multi-item header/detail, company numbering, permission grants, DataTable MO selection, date-filtered list and Excel export with one row per item.
- Added a separate approval screen per reference: date filter, approve/cancel actions, status, approver/time, customer and PO Buyer, plus a detail panel for the selected GSN.
- Existing stock behavior is preserved: saving GSN posts finished-goods stock out with ledger and lot allocations. Approval records user/time; cancelling approval clears the approval metadata and does not alter the stock ledger.
- Migration `2026_11_14_000053_add_goods_sent_note_approval` adds approval metadata and permissions; old GSNs with an existing outbound ledger are marked approved.
- Added feature coverage for approval list/detail, stock posting, reversal and pending/approved CRUD rules.
- **NEEDS CONFIRMATION:** whether GSN stock should instead post at approval and reverse on approval cancellation; warehouse and delivery-route masters, customer/contract relationships, and edit/delete behavior after printing. Run `php artisan migrate` to apply the additive schema/permission migration.
## 2026-11-14 — Kartu Stok Barang Jadi
- Menambahkan daftar barang kategori Finished Goods dan detail ledger stok dengan saldo berjalan, nomor/uraian dokumen, arah mutasi, dan pengguna; ekspor Excel tersedia pada kedua tampilan.
- Modul memakai `stock-card.view` dan sumber data `stock_movements`; tidak ada tabel saldo atau mutasi baru.
- **Test:** `FinishedGoodsStockCardTest` lulus, 1 test / 20 assertion.

## 2026-11-14 — Pengeluaran WIP
- Menambahkan dokumen/baris pengeluaran WIP, nomor dokumen configurable (`wip_issue`), permission CRUD, sidebar Production, modal pemilih DataTable, filter tanggal dan ekspor Excel.
- Setiap simpan mengurangi stok dan lot dalam transaksi serta menambah StockMovement dan audit. Koreksi edit/hapus membalik mutasi; diblokir jika ada transaksi stok setelah sumber pada barang yang sama.
- Kriteria item WIP: kategori berkode `WIP`/bernama Barang Setengah Jadi atau garment item type `work_in_process`, dan stok dilacak.
- Pengujian terfokus: `php artisan test tests/Feature/Inventory/WipIssueTest.php` — lulus (1 test, 17 assertions). PHP lint untuk controller/action/migration/locale juga lulus.
- **NEEDS CONFIRMATION:** tautan MO/permintaan dan tujuan/subkontrak, dokumen multi-item, approval, lokasi gudang, valuasi/akun biaya.
