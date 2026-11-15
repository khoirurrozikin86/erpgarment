# Laporan mutasi barang jadi (hasil produksi)

Implementasi mengikuti permintaan dan screenshot pengguna: periode, pencarian, pagination, tabel mutasi, export XLSX seluruh hasil filter. Menu berada di Laporan BC dan memakai permission inventory-report.view.

Cakupan produk: track_stock, product_type stock, dengan garment_item_type finished_good atau kategori FG/nama finished goods, mengikuti fondasi kartu stok barang jadi. Termasuk produk nonaktif untuk histori.

Perhitungan menggunakan ledger InventoryReportQuery dan perhitungan opname/adjustment yang sama dengan laporan mutasi bahan. Query bahan diperluas dengan pemilihan produk yang dapat dioverride agar logika saldo tidak diduplikasi. Tidak mengubah transaksi atau schema.

- Periode berdasarkan stock_movements.created_at; saldo awal dari saldo transaksi terakhir sebelum periode.
- Pemasukan/pengeluaran memakai mutasi positif/negatif, termasuk koreksi, dengan stock_adjustment dipisahkan dan dihitung sekali.
- Saldo akhir = saldo awal + pemasukan - pengeluaran + adjustment.
- Pencacahan dan selisih memakai nilai opname posted terakhir dalam periode posting; tidak adanya opname tetap kosong/—.
- Selisih adalah selisih opname tersimpan, bukan hasil hitung fisik dikurangi saldo akhir periode.
- Ledger saat ini global, sehingga cakupan stok gabungan mengikuti laporan inventory/kartu stok barang jadi yang ada.
- NEEDS CONFIRMATION: definisi Jumlah Barang; kolom sementara kosong/—, sama dengan laporan mutasi bahan. Tidak disamakan dengan saldo akhir hanya dari screenshot.
- Export mempertahankan angka numerik dan teks sebagai string. Query/filter halaman dan export sama.
- Test/build tidak dijalankan sesuai AGENTS.md.
