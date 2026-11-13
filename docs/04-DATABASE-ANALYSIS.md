# Analisis Kebutuhan Database

Ini adalah model konseptual dari layar, bukan skema final. Nama tabel, kardinalitas, kunci unik dan aturan saldo harus dikonfirmasi.

## Entitas kandidat

| Kelompok | Entitas/tabel kandidat | Hubungan yang terlihat atau mungkin |
|---|---|---|
| Akses | users, roles/permissions, departments, positions | User memiliki jabatan/departemen/atasan; memiliki hak akses; transaksi menyimpan pembuat/pengubah |
| Referensi | importers, exporters, suppliers, customers, styles, units, item_categories, items | Importer/exporter/supplier/customer dirujuk dokumen; barang memiliki kategori/satuan/style (style tampak opsional pada beberapa jenis) |
| BOM & pembelian | bom_headers, bom_items, production_orders (dipakai sebagai baris Material Order), production_order_materials, purchase_orders, purchase_order_items | Setiap baris MO menunjuk satu item Finished Goods dan snapshot BOM; nomor MO mengelompokkan beberapa baris dengan header sama |
| Penerimaan | goods_receipts (GRN), goods_receipt_items, receipt_approvals | Header punya nomor/tanggal, PO/MO, supplier, invoice; detail punya item dan kuantitas; approval merujuk dokumen dan user |
| BC masuk/keluar | customs_documents, customs_document_items (atau tipe dokumen terpisah) | Dokumen berisi tipe BC, No Aju, No/Tgl daftar, referensi receipt/delivery, mitra, HS code, item, kuantitas, nilai, mata uang/faktur |
| Gudang | inventory_transactions, inventory_transaction_items, stock_balances atau ledger-derived balance, stock_counts, stock_count_items | Transaksi masuk/keluar/transfer/retur/adjustment; item mengacu barang, lokasi/gudang dan satuan; hasil pencacahan dibanding saldo buku |
| Produksi | production_plans, production_orders, production_stage_entries, material_issues, finished_goods_receipts, delivery_notes (GSN), delivery_items, delivery_approvals | Stage entry bertipe cutting/sewing/packing; dokumen terkait buyer/style/MO/nomor planning/item; GSN berisi tujuan dan tanggal kirim |
| Subkontrak | subcontractors, subcontract_contracts, subcontract_material_issues, subcontract_receipts, subcontract_items | Kontrak memiliki periode, kuota dan referensi BC; issue/receipt item terkait kontrak dan monitoring |
| Scrap & mesin | scrap_transactions/items, equipment_transactions/items | Jenis masuk/keluar, tanggal, kode/nama, unit, kuantitas, referensi sumber dan keterangan |
| Audit/laporan | audit_logs | Aktivitas berisi user, waktu dan deskripsi; laporan kemungkinan query dari dokumen/transaksi, bukan tabel saldo duplikat |

## Prinsip pemodelan yang disarankan

- Pisahkan header dokumen dari baris detail; simpan nomor dokumen, tanggal bisnis, status, pihak terkait, pembuat dan metadata persetujuan pada header.
- Setiap baris transaksi menyimpan FK barang, kuantitas presisi desimal, satuan, serta referensi sumber/HS/nilai bila relevan. Jangan simpan kode/nama bebas sebagai satu-satunya identitas barang.
- Untuk saldo dapat diaudit, pertimbangkan ledger mutasi append-only yang menunjuk sumber dokumen, arah/jumlah, waktu posting, dan pembatalan/reversal. Jangan menganggap saldo berjalan sebagai angka yang bebas diedit.
- Persetujuan sebaiknya entitas terpisah jika mendukung lebih dari satu tahap/approver; screenshot hanya membuktikan status dan approver/timestamp per baris.
- Kode barang/nomor dokumen bisnis bukan selalu PK; identitas internal stabil tetap dibutuhkan. Kode HS bisa berbeda per barang/dokumen dan perlu klarifikasi sumber kebenaran.
- Simpan tanggal dan angka dengan tipe yang sesuai, bukan string tampilan; gunakan precision yang mencukupi untuk kain, berat, jumlah dan nilai uang. Format locale hanya di presentasi.

## Pertanyaan schema — NEEDS CONFIRMATION

### Importir yang telah diimplementasikan

Tabel `importers` memiliki `id`, `tax_number` (NPWP), `name`, `address`, `apip`, `is_active`, dan timestamps. Kolom NPWP dan APIP nullable; nama wajib; status aktif default true. Relasi ke dokumen BC masuk belum dibuat karena modul BC belum diimplementasikan dan kardinalitasnya perlu dikonfirmasi.

**NEEDS CONFIRMATION:** format/keunikan NPWP, makna dan format APIP, apakah satu perusahaan atau lebih dapat memakai master bersama, dan aturan penghapusan setelah terpakai.

1. Apakah satu perusahaan/gudang saja, atau multi-company/multi-location? Apakah saldo dipisah lokasi, lot, warna/ukuran?
2. Apakah item berupa kombinasi master `barang` + `style/color/size` atau setiap SKU adalah barang tersendiri? Screenshot master memuat kategori, kode, nama, satuan, style, warna, ukuran.
3. Apakah MO/PO/GRN/BC memiliki header dan detail; berapa pola relasi (partial receipt, beberapa BC per GRN, satu BC untuk banyak GRN)?
4. Apakah lot/batch, tanggal produksi/kedaluwarsa, lokasi bin, dan serial mesin diperlukan? Tidak terlihat konsisten pada screenshot.
5. Apakah kuantitas BC, penerimaan, issue, hasil produksi dan satuan dapat berbeda; aturan konversi/precision apa?
6. Apakah dokumen disimpan sebagai snapshot data saat approval? Bagaimana versioning, revisi, cancel, dan audit?
7. Bagaimana costing persediaan dan transaksi mata uang asing diperlakukan?

## Hubungan minimum

`item` → detail penerimaan/pengeluaran/BC/produksi/retur → `inventory_transaction` → laporan saldo. `supplier/customer` dan `user` menghubungkan header dokumen; `BOM` dan `style` mendukung perencanaan; `subcontract_contract` mengikat issue dan receipt subkon. Kardinalitas final tetap perlu dikonfirmasi.

### Material Order

Tabel `production_orders` dipakai sebagai baris Material Order untuk menjaga kompatibilitas Material Request/tahap produksi. Header per baris: `number` (diisi dari form; tidak unik karena satu MO memuat beberapa item), `po_date`, `due_date`, `buyer_po_number`, `no_cbd`, `currency`, `destination`, dan `customer_id`. Detail per baris: `output_product_id`, `planned_quantity`, `price_per_unit`, `total_price` (Qty × harga satuan), dan `description`. Style/kode/nama/satuan dibaca dari master item. BOM aktif memasok snapshot material per baris MO untuk Material Request. Migration `2026_11_13_000015_add_material_order_reference_fields` menambahkan field form dan mengubah indeks nomor MO agar bisa berulang.

Migration `2026_11_13_000014_convert_production_orders_to_material_orders` menghapus data Production Order lama, melepaskan foreign key dari Material Request lama, dan menghapus catatan tahap lama setelah melepas tautan stage dari stock movements. Material Request dan ledger mutasi stok tetap dipertahankan. Form edit/hapus baris ditolak bila baris itu telah dipakai Material Request atau tahap produksi; edit header diselaraskan ke baris lain bernomor MO sama.


### Exportir yang telah diimplementasikan

Tabel `exporters` menyimpan `id`, `tax_number`, `name`, `address`, `is_active`, dan timestamps. CRUD mengikuti pola master Importir, dengan permission terpisah (`exporters.*`) dan audit log. APIP tidak disertakan karena dokumentasi yang tersedia hanya menunjukkan field tersebut pada Importir. **NEEDS CONFIRMATION:** apakah Exportir memang memakai NPWP dan alamat yang sama, serta apakah ada identitas/atribut khusus (misalnya NIB) pada referensi sumber. Relasi ke BC keluar menunggu implementasi dokumen BC dan keputusan cardinality.


### Master Supplier — field sesuai referensi

Tabel `suppliers` menyimpan kode, nama, alamat, contact person, telepon, fax, email, negara, dan `is_active`. Delapan field pertama mengikuti daftar/form pada image13–14. `is_active` dipertahankan sebagai atribut internal karena dipakai validasi/seleksi supplier pada alur pembelian yang telah ada; status tidak ditampilkan pada form master. Field supplier lama (jenis, website, NPWP, kota/provinsi/kode pos, termin, bank, catatan) dihapus melalui migration forward. **Catatan:** nilai data pada kolom yang dihapus tidak dapat dipulihkan kecuali dari backup.


Daftar negara pada form diambil dari `config/countries.php` (264 kode negara/wilayah), sehingga tidak perlu dibuat satu-satu di database. Nilai negara yang sudah tersimpan tetapi tidak ada pada daftar tetap ditambahkan sebagai pilihan agar data lama dapat diedit.


### Master Customer — field sesuai referensi

Tabel `customers` menyimpan kode, nama, alamat, contact person, telepon, fax, email, negara, dan `is_active`. Delapan field pertama mengikuti image15–16; `is_active` dipertahankan sebagai status internal dan tidak ditampilkan dalam form master. Kolom lama seperti tipe, website, NPWP, alamat terpisah, termin, batas kredit, dan catatan dihapus lewat migration forward. Penghapusan nilai kolom tidak dapat dipulihkan kecuali dari backup. Dropdown negara menggunakan daftar pada `config/countries.php` dan menyertakan nilai lama yang tersimpan.


### Master Style — implementasi sesuai image17

Tabel `styles` menyimpan `id`, `code` (unik), `name`, timestamps. Form referensi hanya menunjukkan kode dan nama. Barang existing menyimpan `style_code` sebagai teks; penghapusan Style ditolak bila kode dipakai barang, dan perubahan kode ditolak untuk menjaga kaitan tersebut. Migrasi relasional ke `style_id` belum dilakukan karena berada di luar scope CRUD referensi.

### Ketersediaan Item

Kolom `products.is_available` adalah status ketersediaan yang terlihat pada referensi sebagai `Masih Ada`; kolom ini terpisah dari `products.is_active` yang masih dipakai untuk mengaktifkan item pada workflow aplikasi. Item lama diberi default tersedia melalui migration `2026_11_13_000010_add_is_available_to_products_table`. Pilihan Style pada Item mencari kode/nama dari tabel `styles`; penyimpanan tetap memakai `style_code` untuk menjaga kompatibilitas schema dan transaksi yang ada.

### Field Item yang Dipertahankan dan Dihapus

Field master Item mengikuti form referensi: `code`, `name`, `category_id`, `unit_id`, `style_code`, `color`, `size`, dan `is_available`. `garment_item_type` serta `product_type` dipertahankan sebagai klasifikasi internal yang dipakai BOM/produksi dan pencatatan stok. `purchase_price`, `min_stock`, `max_stock`, `reorder_point`, `track_stock`, dan `is_active` tetap dipakai proses inventory/purchasing. Kolom lama yang tidak dipakai kode aktif dan tidak ditampilkan pada referensi dihapus lewat migration forward `2026_11_13_000011_remove_unused_product_fields`; migrasi ini menghapus nilai lama pada kolom-kolom tersebut.

### Migration POS dan Outlet

Source migration khusus Outlet, Ticket QR, User Outlet, Scan Record, dan POS dihapus setelah cakupannya dinyatakan di luar inventory. Migration `2026_11_13_000005_remove_pos_ticket_outlet_modules` dipertahankan sebagai migration forward untuk menghapus tabel dan foreign key dari database yang telah menjalankan migration lama. Data ledger inventory tetap disimpan; hanya relasi POS yang dilepas.
