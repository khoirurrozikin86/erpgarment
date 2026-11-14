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

## Scrap Masuk Gudang — implementasi awal

`scrap_receipt_documents` menyimpan company, nomor/tanggal, produk scrap (`products`, harus kategori `WASTE`), produk perca sumber, No. Produksi teks opsional, jumlah, keterangan, pencatat, dan soft delete. `stock_movements.scrap_receipt_document_id` menghubungkan mutasi stok ke dokumen. Lot dibuat oleh allocator stok yang sudah ada; biaya mutasi bernilai nol sampai aturan valuasi ditetapkan. Nomor `scrap_receipt` memakai prefix SCI dan reset bulanan.

Pilihan barang scrap dan sumber memakai `products` yang aktif dan terlacak stok. **NEEDS CONFIRMATION:** apakah sumber harus dibatasi kategori tertentu, apakah No. Produksi harus FK ke MO/tahapan produksi, valuasi scrap dan jurnal, multi-line, serta apakah mesin berbagi struktur master barang atau memerlukan aset tersendiri.

### GSN yang diterapkan

Header `goods_sent_notes` menyimpan nomor, tanggal kirim, nomor/tanggal kontrak, rute, gudang (teks karena master gudang belum tersedia), kendaraan, driver, seal, receiver/container, perusahaan, pembuat, approver, dan waktu approval. `goods_sent_note_items` menyimpan MO, barang jadi keluaran MO, customer, kuantitas, serta karton. `stock_movements` merujuk header/baris GSN sehingga pengiriman dan reversal dapat ditelusuri di kartu stok; dokumen/baris memakai soft delete agar FK ledger tidak terputus. Sesuai perilaku GSN yang sudah ada, stok diposting saat simpan; layar approval baru mencatat status/user/waktu.

Kartu Stok Barang Jadi tidak memerlukan tabel baru: daftar mengambil `products` dengan `track_stock` dan kategori `FG`/“Finished Goods”; kartu detail membaca `stock_movements` berdasarkan `product_id`, menggunakan `quantity_after` sebagai saldo historis dan relasi dokumen sumber/pengguna untuk jejak mutasi.

**NEEDS CONFIRMATION:** apakah titik posting harus dipindahkan ke approval dan apakah cancel approval harus membalik stok; master gudang, daftar jalur kirim/receiver, satu customer per GSN atau per baris, makna nomor kontrak vs tanggal pesanan, serta aturan edit/hapus setelah dicetak. Migration approval menandai GSN lama yang sudah memiliki stock movement sebagai approved.

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

## Pertanyaan schema — NEEDS CONFIRMATION

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

# Damaged Items

- `damaged_item_documents`: nomor berita acara unik, tanggal, pembuat.
- `damaged_item_lines`: barang, kuantitas, keterangan; detail terhubung ke header.
- `stock_movements` menyimpan FK opsional ke header dan baris. Pengeluaran memakai tipe `damaged_item`; koreksi yang mengembalikan kuantitas memakai `damaged_item_reversal`.
- Relasi item dan dokumen dibatasi penghapusan (`restrictOnDelete`) agar sumber histori tidak hilang.
- Detail memakai `deleted_at` untuk pembatalan yang menjaga tautan mutasi stok.
- Document type `damaged_item` menggunakan pengaturan penomoran per company.
- NEEDS CONFIRMATION: apakah perlu lot/area reject terpisah dan persetujuan sebelum pengurangan stok.

# Lost Items

- `lost_item_documents` dan `lost_item_lines` menyimpan berita acara dan detail; detail memakai soft delete.
- `stock_movements` memiliki relasi opsional ke header/baris kehilangan. Mutasi `lost_item` mengurangi kuantitas, `lost_item_reversal` membalikkan koreksi atau pembatalan.
- Document type `lost_item` memiliki konfigurasi penomoran tersendiri per company.

## Order Bahan Baku

- `raw_material_order_documents`: header per company, nomor dari Document Numbering, tanggal, nomor MO, dan pembuat.
- `raw_material_order_items`: satu baris per `production_order_material_id`, berisi nomor OBB unik per company, produk, snapshot style, jumlah order, dan jumlah yang sudah dikirim. FK sumber MO dan produk memakai restrict agar sumber historis tidak terhapus selama masih direferensikan.
- Satu nomor MO dapat terdiri dari beberapa baris `production_orders`; pemuatan material menggabungkan detail dari semua baris bernomor sama.
- Tidak ada relasi ke ledger stok karena dokumen ini hanya mencatat order.

## Kirim ke Produksi (MIS)

- `material_issue_documents`: header per company dengan relasi nullable ke Material Request lama atau header Order Bahan Baku.
- `material_issue_items`: setiap baris mereferensikan baris OBB atau baris Material Request lama, produk, dan kuantitas pengiriman.
- `stock_movements` memiliki FK ke header/baris MIS. Pengiriman OBB mengurangi stok dan lot, serta memperbarui `raw_material_order_items.issued_quantity` dan `production_order_materials.issued_quantity`.
- `DocumentNumbering` menggunakan tipe `material_issue` dengan prefix awal MIS.

## Penerimaan Produksi dari Gudang

- Modul memakai `material_issue_documents` dan `material_issue_items` sebagai sumber transfer; tidak membuat ulang baris stok atau transaksi pengeluaran.
- Migration `2026_11_13_000031_add_production_receipt_confirmation_to_mis.php` menambahkan `production_receipt_status`, `production_received_by`, dan `production_received_at` pada header MIS.
- Relasi penerima ke `users` bersifat nullable dan `nullOnDelete`; persetujuan mencatat audit, tidak mem-posting perubahan saldo stok.
- **NEEDS CONFIRMATION:** referensi PO Supplier pada detail tidak dapat diturunkan dari skema OBB/MIS yang tersedia; saat ini ditampilkan sebagai kosong.

## Pemakaian Material Non-Produksi

- Header `non_production_material_usage_documents` menyimpan company, nomor transaksi, tanggal, pembuat, dan soft delete; detail menyimpan produk, qty, harga, dan total harga.
- `stock_movements` merujuk header dan detail. Pengeluaran mengonsumsi lot FIFO; edit dan pembatalan membuat mutasi kompensasi dan audit.
- Nomor memakai konfigurasi `DocumentNumbering` bertipe `non_production_material_usage`, prefix awal PBN; company baru dan yang sudah ada menerima konfigurasi.
- **Asumsi:** harga input menjadi nilai transaksi/cost amount untuk jurnal beban akun 6100; konfirmasi akun biaya yang tepat tetap diperlukan.
# Return to Warehouse

- `production_return_documents`: company, unique number per company, return date, optional `raw_material_order_document_id`, supplier PO reference, status, creator/approver/canceller and timestamps.
- `production_return_items`: document, product, positive quantity, description; soft deletes preserve correction history.
- `stock_movements.production_return_document_id` and `production_return_item_id` provide source traceability. Approval creates positive `production_return`; cancellation creates negative `production_return_reversal`. Both pass through existing stock/lot allocator.
- Number type `return_to_warehouse`, default prefix `RETG`, configurable per company; existing companies are provisioned by migration and new companies by company creation defaults.
- ProductStock and inventory lot are updated in a DB transaction. Source cost and return-to-original-MIS/lot allocation remain **NEEDS CONFIRMATION**.

## Order Support Material

- `support_material_order_documents`: company, nomor unik per company, tanggal order, creator, timestamps/soft delete.
- `support_material_order_items`: dokumen, product, qty positif, style dan No. PO Other opsional; relasi dokumen/product memakai FK bernama ringkas.
- Number type `support_material_order`, prefix default `OBP`; tersedia untuk company lama dari migration dan company baru dari defaults.
- Tidak ada relasi ke `stock_movements` karena referensi hanya menunjukkan order, bukan pengeluaran/penerimaan.

## Cutting

Memperluas `production_order_stage_entries` untuk data cutting dengan nomor dokumen, produk material, dan kuantitas waste material. Record tetap terhubung ke Production Order, pengguna, dan Product output. Nomor diatur di Document Numbering tipe `cutting` (prefix awal `CUT`). Waste belum menjadi mutasi inventory; konfirmasi diperlukan untuk penyesuaian stok bahan.

## Sewing

Memperluas `production_order_stage_entries` dengan `sewing_number` dan indeks; relasi MO, pengguna, produk output, serta bahan menggunakan relasi stage entry yang telah ada. Document Numbering bertipe `sewing` (prefix awal `SEW`). Sewing tidak membuat mutasi stok; `input_quantity`, `good_quantity`, dan `rejected_quantity` menjadi jejak kuantitas WIP, sedangkan `material_waste_quantity` hanya catatan.

## Hasil Packing

- Menggunakan `production_order_stage_entries` dengan `stage=packing`, `packing_number`, material terpilih dan `material_waste_quantity`; soft delete menjaga relasi ledger saat dokumen dihapus.
- `stock_movements.production_order_stage_entry_id` menghubungkan mutasi stok barang jadi ke hasil Packing. Mutasi positif membuat lot dan alokasi; koreksi/penghapusan dicatat sebagai reversal.
- Satu MO memiliki banyak stage entry dan bahan MO; output stok terhubung ke produk barang jadi MO.

## Packing / Barang Jadi

- Memakai `production_order_stage_entries` dengan `stage=finishing`, `finished_goods_number`, kuantitas terima (`input_quantity`), hasil (`good_quantity`), tanggal, MO, dan user.
- Relasi ke item/style/unit melalui output product MO; sisa sumber dihitung dari total output baik stage `packing` dikurangi total penerimaan stage `finishing`.
- Tidak membuat mutasi stok karena output Packing telah diposting ke stok oleh tahap sebelumnya.

## Pengeluaran WIP (implementasi awal)
- `wip_issue_documents`: company, nomor dokumen unik per company, tanggal, pembuat, soft delete.
- `wip_issue_lines`: dokumen, item WIP, jumlah positif, keterangan, soft delete. Satu item per dokumen saat ini; kebutuhan multi-item **NEEDS CONFIRMATION**.
- `stock_movements` menyimpan referensi dokumen/baris WIP dan movement `wip_issue` negatif. Edit/hapus membuat reversal positif dan tidak menghapus histori mutasi. Stok/lot berubah di dalam transaksi DB; pengeluaran ditolak bila saldo menjadi negatif.
- Biaya pergerakan memakai average cost stok yang tersedia. Akun beban, gudang/lokasi, tautan MO/permintaan, tujuan/subkontrak, approval, batas kuantitas, serta perlakuan reversal **NEEDS CONFIRMATION**.
