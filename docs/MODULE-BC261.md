# BC OUT — BC 2.6.1 Out

Diminta pengguna lalu dikonfirmasi dengan “lanjut”. Penempatan di BC OUT setelah BC 3.0 mengikuti kata “out”, konteks menu sebelumnya, dan referensi pengeluaran; frasa “BC IN” pada permintaan ditafsirkan sebagai salah ketik. Tata letak aplikasi tetap sidebar.

## File perubahan
- app/Http/Controllers/Admin/Bc261Controller.php
- app/Domain/Customs/Actions/SaveBc261Action.php
- app/Domain/Customs/Queries/Bc261Query.php
- app/Exports/Bc261Export.php
- resources/views/super/bc261/index.blade.php dan form.blade.php
- lang/id/bc261.php dan lang/en/bc261.php
- routes/super.php dan resources/views/admin/partials/sidebar.blade.php
- database/migrations/2026_11_15_000111_add_bc261_permissions.php

## Implementasi dan asumsi
Menggunakan model/tabel dokumen BC dan detail yang sudah ada, document_type BC 2.6.1, perusahaan aktif, permission bc261.view/create/update/delete, audit BC261. Nomor aju unik per perusahaan/jenis dokumen. Tidak membuat model atau saldo stok baru.

Daftar periode, pencarian, pagination, tambah/edit/hapus soft delete dan export XLSX sesuai filter. Form Customer/Exporter, nomor dan tanggal sejajar, jenis harga CIF/CNF/FOB, valuta, detail produk/nomor kirim/tanggal kirim/quantity/harga unit/HS. Tanpa kolom CIF detail atau negara asal sesuai referensi. Header dapat disimpan sebelum detail. Harga unit diisi manual; total quantity × harga unit empat desimal.

Ambil GSN mengikuti fondasi BC 3.0: item GSN sesuai Customer, perusahaan aktif dan pengiriman approved_at terisi. Sumber diverifikasi ulang saat simpan. Pilihan Customer menggunakan master Customer; tidak menganggap master Subkon sebagai Customer otomatis walaupun contoh nama perusahaan muncul pada screenshot. Referensi nomor/tanggal pengiriman juga bisa diisi manual dan terlacak dalam dokumen BC.

Tidak memotong stok atau kuota dari administrasi BC. NEEDS CONFIRMATION: kaitan pengeluaran subkon/kontrak dengan GSN, pemetaan Customer ke Subkon, batas quantity/pemakaian GSN lintas dokumen BC, dan integrasi kepabeanan. Tidak disimpulkan dari gambar. Data contoh tidak di-seed.

Migration 000111 berhasil diterapkan. Test/build tidak dijalankan sesuai instruksi proyek. Tampilan browser belum diverifikasi.
