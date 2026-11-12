# Analisis Kebutuhan Database

Ini adalah model konseptual dari layar, bukan skema final. Nama tabel, kardinalitas, kunci unik dan aturan saldo harus dikonfirmasi.

## Entitas kandidat

| Kelompok | Entitas/tabel kandidat | Hubungan yang terlihat atau mungkin |
|---|---|---|
| Akses | users, roles/permissions, departments, positions | User memiliki jabatan/departemen/atasan; memiliki hak akses; transaksi menyimpan pembuat/pengubah |
| Referensi | importers, exporters, suppliers, customers, styles, units, item_categories, items | Importer/exporter/supplier/customer dirujuk dokumen; barang memiliki kategori/satuan/style (style tampak opsional pada beberapa jenis) |
| BOM & pembelian | bom_headers, bom_items, material_orders, material_order_items, purchase_orders, purchase_order_items | BOM berisi komponen barang dan pemakaian; MO/PO berisi item/barang, jumlah, tanggal dan pihak/nomor buyer |
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

1. Apakah satu perusahaan/gudang saja, atau multi-company/multi-location? Apakah saldo dipisah lokasi, lot, warna/ukuran?
2. Apakah item berupa kombinasi master `barang` + `style/color/size` atau setiap SKU adalah barang tersendiri? Screenshot master memuat kategori, kode, nama, satuan, style, warna, ukuran.
3. Apakah MO/PO/GRN/BC memiliki header dan detail; berapa pola relasi (partial receipt, beberapa BC per GRN, satu BC untuk banyak GRN)?
4. Apakah lot/batch, tanggal produksi/kedaluwarsa, lokasi bin, dan serial mesin diperlukan? Tidak terlihat konsisten pada screenshot.
5. Apakah kuantitas BC, penerimaan, issue, hasil produksi dan satuan dapat berbeda; aturan konversi/precision apa?
6. Apakah dokumen disimpan sebagai snapshot data saat approval? Bagaimana versioning, revisi, cancel, dan audit?
7. Bagaimana costing persediaan dan transaksi mata uang asing diperlakukan?

## Hubungan minimum

`item` → detail penerimaan/pengeluaran/BC/produksi/retur → `inventory_transaction` → laporan saldo. `supplier/customer` dan `user` menghubungkan header dokumen; `BOM` dan `style` mendukung perencanaan; `subcontract_contract` mengikat issue dan receipt subkon. Kardinalitas final tetap perlu dikonfirmasi.
