# BC IN — BC 4.0

Implementasi diminta pengguna dengan screenshot sebagai referensi. Menggunakan customs_documents/customs_document_items yang sudah ada, document_type BC 4.0, perusahaan aktif, permission bc40.view/create/update/delete, dan audit BC40. Tidak membuat model transaksi duplikat.

## File perubahan
- app/Http/Controllers/Admin/Bc40Controller.php
- app/Domain/Customs/Actions/SaveBc40Action.php
- app/Domain/Customs/Queries/Bc40Query.php
- app/Exports/Bc40Export.php
- app/Models/CustomsDocumentItem.php
- resources/views/super/bc40/index.blade.php dan form.blade.php
- lang/id/bc40.php dan lang/en/bc40.php
- routes/super.php dan resources/views/admin/partials/sidebar.blade.php
- database/migrations/2026_11_15_000108_add_bc40_documents.php

## Perilaku dan asumsi
Daftar periode, pencarian, pagination, tambah/edit/hapus soft delete, export XLSX mengikuti filter. Nomor–tanggal sejajar. Supplier, jenis harga CIF/CNF/FOB dan valuta mengikuti pola BC sebelumnya. Detail barang tanpa input CIF; No Faktur/Tanggal Faktur berada per detail dan terpisah dari invoice pada header, mengikuti perbedaan visual screenshot. Kolom faktur opsional, tidak diasumsikan sebagai aturan pajak.

Ambil GRN memakai penerimaan pembelian approved supplier terpilih dan penerimaan peralatan perusahaan aktif/supplier terpilih. Referensi sumber diverifikasi kembali saat simpan. Master supplier dan penerimaan pembelian masih bersama mengikuti fondasi model asal. Nomor aju unik per perusahaan dan jenis BC. Data contoh screenshot tidak dimasukkan sebagai seed.

Tidak ada perubahan stok/kuota dari pencatatan BC. Tidak memperbarui data BC pada GRN otomatis. NEEDS CONFIRMATION: integrasi kepabeanan/CEISA, batas pemakaian GRN lintas dokumen, dan aturan pajak/faktur. Tidak disimpulkan dari screenshot.

Migration 000108 berhasil diterapkan. Test/build tidak dijalankan sesuai instruksi proyek; tampilan browser belum diverifikasi.
