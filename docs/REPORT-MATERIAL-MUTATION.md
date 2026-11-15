# Implementasi laporan mutasi bahan

Permintaan pengguna: laporan mutasi bahan baku dan bahan penolong mengikuti screenshot, dengan export Excel. Implementasi memakai ledger stok dan permission `inventory-report.view` yang sudah ada, tanpa perubahan schema/saldo.

- Cakupan bahan: produk stok dengan `track_stock`, berjenis fabric/accessory/packaging, berkategori RAW/FAB/ACC/PACK, atau jenis general yang tercatat sebagai komponen BOM. Produk tidak dibatasi status aktif agar histori tetap tampil.
- Cakupan stok gabungan mengikuti InventoryReportQuery yang sudah ada; ledger stok saat ini belum mempunyai company_id. Jangan menafsirkan laporan ini sebagai stok terpisah per perusahaan.
- Periode memakai waktu pencatatan `stock_movements.created_at`, sama dengan laporan inventory dan kartu stok saat ini.
- Saldo awal memakai saldo setelah transaksi terakhir sebelum periode. Saldo akhir meneruskan rumus laporan inventory; stock_adjustment dipisahkan dari kolom masuk/keluar sehingga hanya dihitung sekali.
- Pencacahan dan selisih memakai nilai tersimpan pada opname posted terakhir menurut posted_at dalam periode, lalu ID dokumen/detail sebagai tie-breaker. Selisih adalah selisih opname tersimpan, bukan perbandingan pencacahan dengan saldo akhir periode yang mungkin berbeda waktu. Tidak ada opname ditampilkan kosong/—, bukan nol.
- NEEDS CONFIRMATION: definisi Jumlah Barang pada screenshot. Kolom disediakan tanpa angka sampai aturan disepakati; tidak otomatis disamakan dengan stok saat ini atau saldo akhir.
- Export XLSX memakai query/filter dan pemetaan perhitungan yang sama dengan halaman, mencakup seluruh hasil pencarian, bukan hanya halaman aktif. Nilai angka tetap numerik dengan tampilan dua desimal.
- Test/build tidak dijalankan, mengikuti AGENTS.md.
