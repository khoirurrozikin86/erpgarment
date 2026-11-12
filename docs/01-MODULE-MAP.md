# Peta Modul IT Inventory

## Cakupan sumber

Referensi: `IT INVENTORY.docx`, berisi 192 objek gambar berurutan `image1.png`–`image192.png`. Gambar 1 adalah diagram alur; sisanya screenshot halaman/menu. Pengelompokan di bawah mengikuti judul bagian dan menu dalam dokumen, bukan asumsi bahwa semua subhalaman telah memiliki proses lengkap. Screenshot yang menampilkan menu dipakai untuk mengonfirmasi navigasi, bukan dihitung sebagai modul transaksi tambahan.

## Modul dan halaman yang terlihat

| Modul | Halaman/fitur yang diberi judul atau tampak di menu | Relasi utama |
|---|---|---|
| Pengaturan & akses | Pengguna aplikasi, hak akses, Importir, Exportir, stock opname (administrasi data), PPIC, pemasok, pelanggan, style, barang, BOM | Pengguna/role; master pemasok/pelanggan; barang, satuan, style (CRUD), BOM; pembatasan akses |
| PPIC & pengadaan | Material Order (MO), Pesanan Pembelian Supplier, Pesanan Pembelian lain-lain, Cek Pesanan Pembelian | Style/barang/BOM → permintaan material → PO → penerimaan gudang |
| BC Masuk / dokumen pabean masuk | BC 2.3, BC 2.6.2, BC 2.7, BC 4.0, BC 2.0 | Data importer, dokumen penerimaan/GRN, pemasok, barang, HS code, jumlah/nilai/mata uang/faktur |
| Gudang & persediaan | Catatan Penerimaan Barang, Terima Pengembalian dari Produksi, GRN Approve, Kirim ke Produksi (MIS), Kembali ke Supplier, Barang Rusak, Barang Hilang, Kartu Stok Bahan Baku | PO/BC → penerimaan → saldo; pengeluaran/retur/penyesuaian → mutasi dan kartu stok |
| Produksi | Order Bahan Baku, Order Bahan Penolong, Penerimaan Bahan dari Gudang, Pemakaian Bahan Non Produksi, Retur ke Gudang, Hasil Cutting, Hasil Sewing, Hasil Packing, Barang Jadi, Surat Jalan (GSN), Surat Jalan Approve, Pengeluaran WIP, Kartu Stok Barang Jadi | MO/BOM → material issue → hasil per tahap → barang jadi → pengiriman/approval |
| Scrap & mesin/peralatan | Scrap Masuk Gudang, Scrap Keluar Gudang, Kartu Stok Scrap, Mesin/Peralatan Masuk Kaber, Mesin/Peralatan Keluar Kaber, Kartu Stok Mesin/Peralatan | Transaksi masuk/keluar aset atau scrap → saldo dan laporan mutasi |
| Subkontrak | Kontrak Subkon, Hasil Barang Setengah Jadi (Input Barang Setengah Jadi), Pengeluaran Bahan Baku, Pengeluaran Barang Setengah Jadi, Penerimaan Subkon, Monitoring Kontrak, Monitoring Barang Subkon | Kontrak + kuota → bahan/WIP keluar → barang/hasil kembali → monitoring kuota dan saldo |
| BC Keluar / dokumen pabean keluar | BC 3.0, BC 2.6.1, BC 2.7, BC 4.1, BC 2.5 | Barang/dokumen pengeluaran atau pengiriman, customer/penerima, barang, nilai, HS code |
| Laporan BC & operasional | Pemasukan Barang, Pengeluaran Barang, Mutasi Bahan Baku dan Bahan Penolong, Posisi Barang dalam Proses (WIP), Mutasi Barang Jadi, Mutasi Barang Modal dan Barang Lain, Mutasi Reject/Scrap/Waste, Log Histori User, Rencana Produksi, Status Produksi Harian, Barang Jadi, Retur ke Supplier, Retur ke Gudang, Retur Barang Jadi | Agregasi master dan transaksi; rentang tanggal/periode; ekspor Excel; jejak aktivitas |

## Peta navigasi tampak

Menu utama pada screenshot memperlihatkan Pengaturan, PPIC, BC Masuk, Gudang, Produksi, Bahan Sisa & Mesin, Subkon, BC Keluar, dan Laporan BC. Master Importir dan Exportir telah memiliki CRUD dasar; relasi keduanya ke dokumen BC masih menunggu implementasi dan konfirmasi cardinality. Tiap menu menurunkan halaman-halaman yang tercantum di atas. Screenshot memperlihatkan perpindahan melalui menu dan tombol `Detail`; beberapa transaksi memiliki form administrasi terpisah. Detail relasi route atau URL tidak tersedia.

## Batas cakupan

- Tidak semua halaman disebut dalam teks; sebagian diidentifikasi dari judul layar pada screenshot.
- `Pengeluaran WIP`, `Mesin/Peralatan Masuk/Keluar Kaber`, `PPIC`, dan singkatan `MO`, `MIS`, `Kaber`, `GSN` perlu definisi proses.
- Penjualan, kas/bank, akuntansi, outlet/POS yang sudah ada dalam project bukan bagian yang terbukti diminta oleh screenshot ini. Jangan menganggap screenshot mengubah kebutuhan modul tersebut.
