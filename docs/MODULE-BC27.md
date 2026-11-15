# BC IN — BC 2.7 Pemasukan

Implementasi atas permintaan pengguna dengan referensi screenshot. Menggunakan model/tabel customs_documents dan customs_document_items yang sudah ada, document_type BC 2.7, scope perusahaan aktif dan permission bc27.view/create/update/delete. Tidak membuat tabel transaksi stok baru.

## File perubahan
- app/Http/Controllers/Admin/Bc27Controller.php
- app/Domain/Customs/Actions/SaveBc27Action.php
- app/Domain/Customs/Queries/Bc27Query.php
- app/Exports/Bc27Export.php
- resources/views/super/bc27/index.blade.php dan form.blade.php
- lang/id/bc27.php dan lang/en/bc27.php
- routes/super.php dan resources/views/admin/partials/sidebar.blade.php
- database/migrations/2026_11_15_000107_add_bc27_permissions.php

## Perilaku
Daftar periode, pencarian, pagination, tambah/edit/hapus soft delete, dan XLSX sesuai filter. Nomor dan tanggal sejajar. Form supplier, jenis harga CIF/CNF/FOB, valuta, header dokumen dan banyak detail barang. Header dapat disimpan sebelum detail. Total dihitung quantity × unit_price, empat desimal. Export mempertahankan nomor berawalan nol. Perubahan penting dicatat pada audit BC27.

Ambil GRN mengikuti fondasi BC sebelumnya: penerimaan pembelian approved milik supplier pilihan dan penerimaan peralatan perusahaan aktif milik supplier tersebut. Sumber diverifikasi kembali saat simpan. Master supplier dan penerimaan pembelian masih bersama karena model asal belum mempunyai scope perusahaan. Screenshot adalah referensi UI, bukan data awal.

## Asumsi dan keputusan tertunda
Tidak melakukan posting stok atau kuota dari dokumen BC. CIF diisi manual. Tidak mengubah data BC pada GRN otomatis. NEEDS CONFIRMATION: integrasi kepabeanan/CEISA, pembatasan pemakaian GRN lintas dokumen, rumus alokasi CIF dan aturan legal BC. Tidak ditafsirkan dari screenshot.

Migration permission 000107 berhasil diterapkan. Test/build tidak dijalankan sesuai instruksi pengguna proyek. Tampilan browser belum diverifikasi.
