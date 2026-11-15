# BC OUT — BC 3.0

Diminta pengguna berdasarkan screenshot. Grup sidebar BC OUT ditempatkan setelah Subkon dan sebelum Customs Report. Hanya BC 3.0 yang diimplementasikan; jenis lain pada gambar menu belum diminta sebagai modul.

## File perubahan
- app/Http/Controllers/Admin/Bc30Controller.php
- app/Domain/Customs/Actions/SaveBc30Action.php
- app/Domain/Customs/Queries/Bc30Query.php
- app/Exports/Bc30Export.php
- app/Models/CustomsDocument.php dan CustomsDocumentItem.php
- resources/views/super/bc30/index.blade.php dan form.blade.php
- lang/id/bc30.php dan lang/en/bc30.php
- routes/super.php dan resources/views/admin/partials/sidebar.blade.php
- database/migrations/2026_11_15_000110_add_bc30_documents.php

## Implementasi
Memakai tabel customs_documents/customs_document_items bersama dengan pemisahan jenis BC 3.0 dan perusahaan aktif. Menambah foreign key customer/exporter dan goods_sent_note_item_id serta nomor/tanggal pengiriman. Kolom penerimaan menjadi nullable untuk dokumen keluar, tanpa mengganti data penerimaan sebelumnya. Referensi foreign key restrict on delete; dokumen BC dihapus secara soft delete.

Daftar periode, pencarian, pagination, tambah/edit/hapus, export XLSX sesuai filter. Form Customer/Exporter, nomor–tanggal sejajar, jenis harga CIF/CNF/FOB, valuta, detail produk/nomor kirim/tanggal/quantity/harga unit/HS. Tanpa input CIF atau negara asal sesuai screenshot BC 3.0. Header bisa disimpan sebelum detail. Total quantity × unit_price empat desimal. Nomor aju unik per perusahaan/jenis BC. Permission bc30.view/create/update/delete dan audit BC30.

Ambil GSN menggunakan GoodsSentNoteItem/GoodsSentNote yang sudah ada, berdasarkan perusahaan aktif, customer item dan approved_at terisi. Ini keputusan mengikuti fondasi approval pengiriman, bukan aturan kepabeanan dari screenshot. Sumber diverifikasi ulang saat simpan. Harga jual tidak tersedia pada GSN sehingga diisi manual; tidak mengambil biaya stok sebagai harga jual. Pengguna juga dapat memasukkan referensi pengiriman manual, tetap tersimpan pada dokumen BC.

Tidak melakukan pemotongan stok/kuota dari administrasi BC. NEEDS CONFIRMATION: batas pemakaian GSN lintas dokumen BC, batas quantity, hak ubah setelah pelaporan kepabeanan dan integrasi CEISA. Data contoh screenshot tidak di-seed.

Migration 000110 berhasil diterapkan. Test/build tidak dijalankan sesuai instruksi proyek. Tampilan browser belum diverifikasi.
