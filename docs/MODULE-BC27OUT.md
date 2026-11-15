# BC OUT — BC 2.7 Out

Diminta pengguna dengan referensi pengeluaran. Penempatan pada BC OUT setelah BC 2.6.1 Out mengikuti kata “out”, screenshot, dan urutan menu pengeluaran sebelumnya. Frasa BC IN ditafsirkan sebagai salah ketik; layout sidebar tetap dipertahankan.

## File perubahan
- app/Http/Controllers/Admin/Bc27OutController.php
- app/Domain/Customs/Actions/SaveBc27OutAction.php
- app/Domain/Customs/Queries/Bc27OutQuery.php
- app/Exports/Bc27OutExport.php
- resources/views/super/bc27out/index.blade.php dan form.blade.php
- lang/id/bc27out.php dan lang/en/bc27out.php
- routes/super.php dan resources/views/admin/partials/sidebar.blade.php
- database/migrations/2026_11_15_000112_add_bc27out_permissions.php

## Perilaku dan asumsi
Memakai tabel/model BC yang sudah ada. document_type BC 2.7 OUT membedakan dokumen keluar dari BC 2.7 pemasukan. Scope perusahaan aktif, nomor aju unik per perusahaan/jenis, permission bc27out.*, audit BC27OUT. Hapus soft delete.

Daftar periode, pencarian, pagination, tambah/edit/hapus, export Excel sesuai filter. Form Customer/Exporter, nomor–tanggal sejajar, jenis harga CIF/CNF/FOB, valuta, detail barang, nomor/tanggal kirim, qty, harga unit, total otomatis, HS dan negara opsional sesuai kolom detail pada screenshot. Tidak memakai CIF pada detail. Valuta awal IDR dapat diganti; bukan pembatasan mata uang. Harga unit manual, tidak mengambil costing sebagai harga transaksi.

Ambil GSN mengikuti BC OUT sebelumnya: GSN approved sesuai perusahaan aktif dan Customer. Sumber divalidasi ulang saat simpan. Nomor/tanggal pengiriman juga dapat diisi manual dan tersimpan sebagai jejak dokumen. Contoh RETS/MCHO pada gambar tidak disimpulkan sebagai persetujuan integrasi otomatis retur supplier atau pengeluaran mesin; master Customer tidak dipetakan otomatis ke Supplier.

Tidak mengubah stok/kuota. NEEDS CONFIRMATION: pemetaan Supplier/Customer, pengambilan sumber retur supplier/mesin, batas quantity/pemakaian GSN lintas BC dan integrasi kepabeanan. Data contoh screenshot tidak di-seed.

Migration 000112 berhasil diterapkan. Test/build tidak dijalankan sesuai instruksi proyek. Tampilan browser belum diverifikasi.
