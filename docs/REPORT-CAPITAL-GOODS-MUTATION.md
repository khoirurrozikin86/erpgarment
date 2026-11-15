# Mutasi barang modal dan barang lain

Permintaan pengguna: laporan mengikuti screenshot, filter periode/pencarian/pagination dan export Excel, menu Laporan BC. Permission inventory-report.view. Tidak mengubah schema, saldo, atau transaksi.

## Sumber

- Mesin/peralatan: kategori MACHINE, EQUIP, EQUIPMENT, OFFICE. Transaksi aktif equipment_receipt_documents/items dan equipment_issue_documents/items, berdasarkan product_id dan company_id aktif. Tanggal receipt_date/issue_date. Saldo awal = penerimaan sebelum periode - pengeluaran sebelum periode. Saldo akhir = awal + masuk - keluar, sama dengan prinsip kartu stok mesin/peralatan. Produk tetap tampil meskipun tidak aktif atau track_stock false.
- Barang lain: produk stock/track_stock, jenis general, bukan komponen BOM dan bukan kategori RAW/FAB/ACC/PACK/FG/WIP/WASTE atau nama kategori finished goods. Memakai perhitungan mutasi/opname bersama dengan laporan bahan, dan ledger gabungan aplikasi berdasarkan created_at.
- Sumber mesin/peralatan tidak dijumlahkan dengan ledger umum untuk menghindari saldo dari dua sumber yang belum direkonsiliasi. Pencacahan, adjustment, dan selisih mesin/peralatan ditampilkan kosong/— karena belum ada sumber yang terhubung ke ledger peralatan ini.
- Export menggunakan query/filter/pemetaan sama dengan halaman dan mencakup seluruh hasil, bukan hanya halaman aktif. Angka tetap numerik.

## NEEDS CONFIRMATION

- Pemetaan kategori barang modal/barang lain di atas adalah keputusan implementasi sementara berdasarkan kategori aplikasi; klasifikasi resmi per barang perlu konfirmasi pemilik proses, terutama general yang belum dikategorikan.
- Definisi Jumlah Barang belum ditetapkan: kosong/—, sama dengan laporan mutasi lain.
- Dokumen peralatan tanpa product_id belum bisa digabung ke master hanya berdasarkan kesamaan kode/nama; belum masuk laporan berbasis produk ini.
- Saldo awal sebelum tersedianya dokumen peralatan dan rekonsiliasi dengan opname/ledger umum memerlukan sumber transaksi/keputusan proses; laporan tidak mengarang saldo pembukaan.
- Test/build tidak dijalankan sesuai AGENTS.md.
