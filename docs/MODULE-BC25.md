# BC OUT — BC 2.5 Out

Diminta pengguna berdasarkan screenshot. Menu BC 2.5 Out ditempatkan setelah BC 4.1 Out, sesuai urutan BC OUT yang sudah diberikan. Layout sidebar tetap dipertahankan.

## File perubahan
- app/Http/Controllers/Admin/Bc25Controller.php
- app/Domain/Customs/Actions/SaveBc25Action.php
- app/Domain/Customs/Queries/Bc25Query.php
- app/Exports/Bc25Export.php
- app/Models/CustomsDocumentItem.php
- resources/views/super/bc25/index.blade.php dan form.blade.php
- lang/id/bc25.php dan lang/en/bc25.php
- routes/super.php dan resources/views/admin/partials/sidebar.blade.php
- database/migrations/2026_11_15_000114_add_bc25_documents.php

## Perilaku dan asumsi
Menggunakan model/tabel BC yang sudah ada dengan document_type BC 2.5, perusahaan aktif, nomor aju unik per perusahaan/jenis, permission bc25.*, audit BC25, dan hapus soft delete. Tidak membuat tabel transaksi persediaan baru.

Daftar periode, pencarian, pagination, tambah/edit/hapus dan export XLSX sesuai filter. Form Customer/Exporter, nomor–tanggal sejajar, jenis harga CIF/CNF/FOB, valuta, detail barang/nomor kirim/tanggal kirim/quantity/harga unit/HS dan Item Per Unit. Header dapat disimpan sebelum detail. Total quantity × harga unit empat desimal. Valuta awal IDR dapat diganti.

Item Per Unit pada screenshot adalah fakta visual; makna dan rumusnya belum ditetapkan. Implementasi menyimpan angka bulat nonnegatif opsional, tanpa pengaruh otomatis ke quantity/harga/stok. NEEDS CONFIRMATION: makna Item Per Unit, apakah perlu pecahan dan rumus bisnisnya.

Ambil GSN memakai sumber yang sudah disetujui sesuai perusahaan aktif dan Customer item. Sumber diperiksa kembali saat simpan. Nomor/tanggal kirim dapat diisi manual dan tetap tersimpan dalam dokumen BC. Harga diisi manual, tidak diturunkan dari costing persediaan.

Tidak memotong stok/kuota dari administrasi BC. NEEDS CONFIRMATION: batas quantity/pemakaian GSN lintas BC dan integrasi kepabeanan. Data screenshot tidak di-seed.

Migration 000114 berhasil diterapkan. Test/build tidak dijalankan sesuai instruksi proyek. Tampilan browser belum diverifikasi.
