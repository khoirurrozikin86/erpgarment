# BC IN — BC 2.6.2

Implementasi diminta pengguna berdasarkan screenshot. Memakai customs_documents/customs_document_items bersama BC 2.3 dengan pemisahan document_type, perusahaan, permission bc262.*, dan audit BC262.

File: Bc262Controller, SaveBc262Action, Bc262Query, Bc262Export, views super/bc262, lang id/en/bc262, routes/super.php, sidebar, model CustomsDocument/Item, migration 000106.

Daftar mendukung periode, pencarian, pagination, tambah/edit/hapus, export XLSX. Form nomor dan tanggal sejajar, supplier/subkon, CIF/CNF/FOB, valuta, detail item, Ambil GRN. Header dapat disimpan sebelum detail. Hapus menggunakan soft delete; sumber penerimaan tidak dihapus.

Pilihan supplier berasal master supplier bersama; subkon dari perusahaan aktif. GRN supplier mengikuti fondasi BC 2.3 (pembelian approved dan penerimaan peralatan perusahaan aktif). Penerimaan subkon hanya status confirmed, perusahaan dan subkon sesuai. Harga penerimaan subkon diisi manual, tidak diturunkan dari costing stok. Angka contoh screenshot tidak dijadikan seed data.

Tidak ada posting stok, kuota, pajak, atau integrasi CEISA. CIF manual. NEEDS CONFIRMATION: aturan kepabeanan, alokasi CIF, batas kuantitas lintas dokumen BC, dan integrasi eksternal. Test/build tidak dijalankan sesuai instruksi proyek. Migration 000106 telah diterapkan.
