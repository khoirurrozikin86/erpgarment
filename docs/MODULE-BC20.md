# BC IN — BC 2.0

Implementasi diminta pengguna berdasarkan screenshot. Memakai model/tabel customs_documents dan customs_document_items yang sudah ada, dengan document_type BC 2.0, perusahaan aktif, permission bc20.view/create/update/delete, serta audit BC20. Tidak membuat tabel transaksi persediaan baru.

## File perubahan
- app/Http/Controllers/Admin/Bc20Controller.php
- app/Domain/Customs/Actions/SaveBc20Action.php
- app/Domain/Customs/Queries/Bc20Query.php
- app/Exports/Bc20Export.php
- resources/views/super/bc20/index.blade.php dan form.blade.php
- lang/id/bc20.php dan lang/en/bc20.php
- routes/super.php dan resources/views/admin/partials/sidebar.blade.php
- database/migrations/2026_11_15_000109_add_bc20_permissions.php

## Perilaku
Daftar periode, pencarian, pagination, tambah/edit/hapus soft delete, dan export XLSX sesuai filter. Form Supplier/Importir, nomor dan tanggal BL-AWB, dokumen BC dan invoice sejajar, jenis harga CIF/CNF/FOB, valuta, detail barang dengan CIF manual dan Ambil GRN. Header dapat disimpan sebelum detail. Nomor aju unik per perusahaan/jenis BC; export menjaga nomor berawalan nol.

Referensi daftar menampilkan No Faktur dan Tanggal Faktur, sedangkan screenshot form tidak menampilkan inputnya. Keputusan implementasi: memakai kolom faktur opsional per detail yang sudah tersedia dari BC 4.0, terpisah dari invoice header, agar kolom daftar dapat diisi. Bukan penafsiran aturan pajak.

Ambil GRN mengikuti fondasi BC sebelumnya: penerimaan pembelian approved sesuai supplier, serta penerimaan peralatan perusahaan aktif sesuai supplier. Sumber diperiksa kembali saat simpan. Model supplier dan penerimaan pembelian masih bersama karena fondasi asal belum memiliki company_id. Data contoh screenshot tidak di-seed.

Tidak ada perubahan stok/kuota atau sinkronisasi data BC pada GRN otomatis. NEEDS CONFIRMATION: batas penggunaan GRN lintas dokumen BC, integrasi CEISA, alokasi CIF dan aturan legal/pajak. Tidak disimpulkan dari screenshot.

Migration permission 000109 berhasil diterapkan. Test/build tidak dijalankan sesuai instruksi proyek. Tampilan browser belum diverifikasi.
