# Peta Modul IT Inventory

## Cakupan sumber

Referensi: `IT INVENTORY.docx`, berisi 192 objek gambar berurutan `image1.png`–`image192.png`. Gambar 1 adalah diagram alur; sisanya screenshot halaman/menu. Pengelompokan di bawah mengikuti judul bagian dan menu dalam dokumen, bukan asumsi bahwa semua subhalaman telah memiliki proses lengkap. Screenshot yang menampilkan menu dipakai untuk mengonfirmasi navigasi, bukan dihitung sebagai modul transaksi tambahan.

## Modul dan halaman yang terlihat

| Modul | Halaman/fitur yang diberi judul atau tampak di menu | Relasi utama |
|---|---|---|
| Pengaturan & akses | Pengguna aplikasi, hak akses, Importir, Exportir, stock opname (administrasi data), PPIC, pemasok, pelanggan, style, barang, BOM | Pengguna/role; master pemasok/pelanggan; barang, satuan, style (CRUD), BOM; pembatasan akses |
| PPIC & pengadaan | Material Order (MO), Material Request, Order Bahan Baku, Pesanan Pembelian Supplier, Pesanan Pembelian lain-lain, Cek Pesanan Pembelian | Style/barang/BOM → Material Request → Order Bahan Baku / PO → penerimaan gudang |
| BC Masuk / dokumen pabean masuk | BC 2.3, BC 2.6.2, BC 2.7, BC 4.0, BC 2.0 | Data importer, dokumen penerimaan/GRN, pemasok, barang, HS code, jumlah/nilai/mata uang/faktur |
| Gudang & persediaan | Catatan Penerimaan Barang, Terima Pengembalian dari Produksi, GRN Approve, Kirim ke Produksi (MIS), Pemakaian Material Non-Produksi, Kembali ke Supplier, Barang Rusak, Barang Hilang, Kartu Stok Bahan Baku | PO/BC → penerimaan → saldo; Material Request disetujui → MIS → pengurangan stok/lot → produksi; pemakaian/retur/penyesuaian → mutasi dan kartu stok |
| Produksi | Penerimaan Produksi dari Gudang | Daftar transfer MIS, detail item yang diterima produksi, dan konfirmasi penerimaan |
| Produksi | Order Bahan Baku, Order Bahan Penolong, Penerimaan Bahan dari Gudang, Pemakaian Bahan Non Produksi, Retur ke Gudang, Hasil Cutting, Hasil Sewing, Hasil Packing, Barang Jadi, Surat Jalan (GSN), Surat Jalan Approve, Pengeluaran WIP, Kartu Stok Barang Jadi | MO/BOM → material issue → hasil per tahap → barang jadi → pengiriman/approval |
| Scrap & mesin/peralatan | Scrap Masuk Gudang, Scrap Keluar Gudang, Kartu Stok Scrap, Mesin/Peralatan Masuk Kaber, Mesin/Peralatan Keluar Kaber, Kartu Stok Mesin/Peralatan | Transaksi masuk/keluar aset atau scrap → saldo dan laporan mutasi |
| Subkontrak | Kontrak Subkon, Hasil Barang Setengah Jadi (Input Barang Setengah Jadi), Pengeluaran Bahan Baku, Pengeluaran Barang Setengah Jadi, Penerimaan Subkon, Monitoring Kontrak, Monitoring Barang Subkon | Kontrak + kuota → bahan/WIP keluar → barang/hasil kembali → monitoring kuota dan saldo |
| BC Keluar / dokumen pabean keluar | BC 3.0, BC 2.6.1, BC 2.7, BC 4.1, BC 2.5 | Barang/dokumen pengeluaran atau pengiriman, customer/penerima, barang, nilai, HS code |
| Laporan BC & operasional | Pemasukan Barang, Pengeluaran Barang, Mutasi Bahan Baku dan Bahan Penolong, Posisi Barang dalam Proses (WIP), Mutasi Barang Jadi, Mutasi Barang Modal dan Barang Lain, Mutasi Reject/Scrap/Waste, Log Histori User, Rencana Produksi, Status Produksi Harian, Barang Jadi, Retur ke Supplier, Retur ke Gudang, Retur Barang Jadi | Agregasi master dan transaksi; rentang tanggal/periode; ekspor Excel; jejak aktivitas |

## Peta navigasi tampak

Menu utama pada screenshot memperlihatkan Pengaturan, PPIC, BC Masuk, Gudang, Produksi, Bahan Sisa & Mesin, Subkon, BC Keluar, dan Laporan BC. Master Importir dan Exportir telah memiliki CRUD dasar; relasi keduanya ke dokumen BC masih menunggu implementasi dan konfirmasi cardinality. Tiap menu menurunkan halaman-halaman yang tercantum di atas. Screenshot memperlihatkan perpindahan melalui menu dan tombol `Detail`; beberapa transaksi memiliki form administrasi terpisah. Detail relasi route atau URL tidak tersedia.

## Status pengembangan: Scrap Masuk Gudang

Sidebar memisahkan grup **Bahan Sisa** dari Gudang dan menempatkan Scrap Masuk Gudang bersama pencatatan barang rusak/hilang. Grup **Mesin** sudah disediakan, tetapi saat ini hanya menandai bahwa modul mesin belum tersedia; tidak ada aksi mesin yang dibuat.

Scrap Masuk Gudang memakai master barang kategori `WASTE` sebagai barang scrap yang menambah stok. Form mencatat tanggal, nomor otomatis, kode/nama scrap, satuan, jumlah, kode/nama item perca sumber, No. Produksi, dan keterangan. Daftar berfilter tanggal, mendukung CRUD modal dan Excel; setiap mutasi masuk/koreksi tercatat pada kartu stok.

**NEEDS CONFIRMATION:** arti operasional `No. Produksi` dan relasinya ke item perca, sumber/tujuan proses scrap, valuasi scrap dan dampak akuntansi, serta apakah satu dokumen penerimaan seharusnya memuat banyak baris.

## Batas cakupan

- Tidak semua halaman disebut dalam teks; sebagian diidentifikasi dari judul layar pada screenshot.
- `Pengeluaran WIP`, `Mesin/Peralatan Masuk/Keluar Kaber`, `PPIC`, dan singkatan `MO`, `MIS`, `Kaber`, `GSN` perlu definisi proses.
- Penjualan, kas/bank, akuntansi, outlet/POS yang sudah ada dalam project bukan bagian yang terbukti diminta oleh screenshot ini. Jangan menganggap screenshot mengubah kebutuhan modul tersebut.
# Return Production ↔ Warehouse

- **Produksi — Return to Warehouse:** mencatat nomor/tanggal return, item, qty, MO opsional, PO Supplier, dan keterangan. Nomor memakai Document Numbering `return_to_warehouse`.
- **Gudang — Receipt from Production:** detail dokumen return, status pending/disetujui/dibatalkan, approve dan cancel. Persetujuan menambah stok serta kartu stok; export Excel mengikuti filter tanggal.
- Berbeda dari **Penerimaan Produksi dari Gudang**, yang mengonfirmasi MIS dan tidak mengubah stok kembali.

## Order Support Material (implementasi berjalan)

Modul Produksi untuk mencatat order item support material. Daftar menampilkan nomor/tanggal order, item, qty, style dan No. PO Other; nomor dikelola lewat Document Numbering (`support_material_order`, prefix awal `OBP`).

## Cutting

Modul Produksi untuk mencatat hasil cutting terhadap MO aktif: qty cutting, qty dikirim ke sewing, reject, bahan MO yang dipakai, dan waste bahan. Nomor cutting memakai Document Numbering (`cutting`, prefix awal `CUT`); data terhubung ke riwayat tahap produksi yang sudah ada.

## Sewing (implementasi berjalan)

Tahap Produksi setelah Cutting. Catat hasil sewing per MO; daftar ringkas menampilkan No Sewing, tanggal, dan MO. Rincian memuat style/produk, penerimaan Cutting, qty Sewing, reject, kirim ke Packing, serta bahan/waste yang tercatat.

## Hasil Packing — implementasi berjalan

Modul Produksi setelah Sewing. Hasil baik Packing menjadi stok barang jadi; reject dicatat. MO, material MO, kartu stok, serta nomor dokumen saling terhubung. Implementasi detail dan statusnya ada di `IMPLEMENTATION-PROGRESS.md`.

## Packing / Barang Jadi — implementasi berjalan

Tahap terpisah setelah Hasil Packing. Mengambil sisa kuantitas Packing per MO, mencatat No Barang Jadi/tanggal/hasil dan menampilkan item, style, MO, serta PO Buyer. Nomor dokumen memakai `FIN`.
