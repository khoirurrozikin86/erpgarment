# BC OUT — BC 4.1 Out

Permintaan pengguna menyebut “BC IN” sekaligus “out”; ditempatkan pada BC OUT setelah BC 2.7 Out sesuai konteks urutan menu sebelumnya dan referensi pengeluaran. Layout aplikasi tetap sidebar.

## File perubahan
- app/Http/Controllers/Admin/Bc41Controller.php
- app/Domain/Customs/Actions/SaveBc41Action.php
- app/Domain/Customs/Queries/Bc41Query.php
- app/Exports/Bc41Export.php
- resources/views/super/bc41/index.blade.php dan form.blade.php
- lang/id/bc41.php dan lang/en/bc41.php
- routes/super.php dan resources/views/admin/partials/sidebar.blade.php
- database/migrations/2026_11_15_000113_add_bc41_permissions.php

## Perilaku dan asumsi
Menggunakan tabel/model BC yang sudah tersedia. document_type BC 4.1, perusahaan aktif, nomor aju unik per perusahaan/jenis, permission bc41.*, audit BC41, hapus soft delete. Tidak membuat tabel atau saldo persediaan baru.

Daftar periode/pencarian/pagination/tambah/edit/hapus dan export XLSX. Form Customer/Exporter, nomor–tanggal sejajar, jenis harga CIF/CNF/FOB, valuta, detail produk/nomor kirim/tanggal kirim/quantity/harga unit/HS dan faktur opsional per barang. Faktur detail terpisah dari invoice header. Total quantity × harga unit empat desimal, tanpa CIF detail sesuai referensi.

Ambil GSN mengikuti sumber perusahaan aktif, Customer item dan approved_at terisi. Ambil GRN mengambil penerimaan pembelian approved (data bersama mengikuti fondasi asal) serta penerimaan peralatan perusahaan aktif. Customer tidak dipetakan otomatis ke Supplier. GRN hanya menjadi referensi penerimaan; nomor/tanggal kirim tetap diisi manual. Harga GRN menjadi nilai awal yang dapat diubah. Detail menyimpan sumber dan sumber diperiksa kembali saat simpan; satu detail hanya boleh mengambil satu sumber (GSN, GRN pembelian atau peralatan).

Tidak memotong stok/kuota dari dokumen BC. NEEDS CONFIRMATION: pemetaan Customer/Supplier, hubungan retur/pengeluaran mesin ke referensi GRN, batas quantity dan penggunaan sumber lintas BC, aturan faktur serta integrasi kepabeanan. Data contoh tidak di-seed.

Migration 000113 berhasil diterapkan. Test/build tidak dijalankan sesuai instruksi proyek. Tampilan browser belum diverifikasi.
