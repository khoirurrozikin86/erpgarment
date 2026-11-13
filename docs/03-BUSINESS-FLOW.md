# Analisis Alur Bisnis

## Alur tingkat tinggi yang tergambar

Diagram `image1` membagi proses ke Sales/PPIC, Gudang, dan Production/WIP. Ia menampilkan bahan masuk melalui EXIM, BOM dan PO buyer, penerbitan PO supplier, GRN, transfer ke produksi, proses/subkon/finish good, surat jalan, serta dokumen EXIM masuk/keluar dan saldo bulanan. Diagram adalah sumber proses paling eksplisit. Beberapa panah/penamaan perlu validasi sebelum dijadikan aturan sistem.

## Alur yang didukung oleh halaman

1. **Persiapan data** — pengguna dan akses, importer/exporter, supplier/customer, style, barang dan BOM disiapkan.
2. **Kebutuhan dan pengadaan** — Material Order memuat nomor MO, tanggal MO/tempo, No CBD, PO Buyer, mata uang, destination, Buyer, dan satu atau beberapa baris Finished Goods dengan jumlah, harga satuan, total harga, serta deskripsi; PO supplier dan PO lain-lain dicatat.
3. **Penerimaan** — BC masuk mencatat data kepabeanan/penerimaan yang merujuk dokumen; Catatan Penerimaan Barang/GRN menyimpan pemasok, invoice, PO/MO, item, kuantitas; GRN Approve memiliki status dan pencatat persetujuan.
4. **Stok gudang** — penerimaan dan retur produksi masuk ke gudang. Pengiriman/transfer ke produksi (MIS), retur ke supplier, barang rusak/hilang tercatat sebagai transaksi yang berpengaruh pada stok. Kartu stok menunjukkan saldo/mutasi.
   Pemakaian material non-produksi mencatat transaksi bernomor, barang, kuantitas, harga, dan total; stok/lot berkurang serta mutasi tercatat pada kartu stok.
5. **Produksi** — material diminta/dikeluarkan dari gudang; catatan hasil Cutting, Sewing dan Packing memiliki nomor dokumen, tanggal, user, serta kaitan ke MO/planning; hasil akhir tercatat sebagai Barang Jadi. Surat Jalan (GSN) mengirim hasil dan kemudian disetujui.
6. **Subkontrak** — kontrak mengandung subkon, nomor kontrak, nomor skep BC, tanggal berlaku, keterangan dan kuota. Bahan baku/WIP dikeluarkan dan penerimaan subkon serta barang setengah jadi dimonitor. Kuota dan aturan pengurangan/pemulihan **NEEDS CONFIRMATION**.
7. **Barang sisa/mesin** — scrap masuk/keluar dan aset/mesin masuk/keluar dicatat, kemudian disajikan dalam kartu stok/laporan.
8. **Dokumen keluar & laporan** — BC keluar mencatat dokumen pengeluaran, barang, customer, jumlah dan nilai. Laporan pemasukan/pengeluaran, mutasi, WIP, produksi, barang jadi dan retur dapat ditampilkan menurut periode serta diekspor.

## Relasi antarlayar yang terlihat

- Tombol `Detail` dari daftar transaksi membuka atau menampilkan baris dokumen terkait; satu screenshot penerimaan produksi menampilkan tabel daftar dan tabel detail dokumen di bawahnya.
- Approval GRN dan Surat Jalan memuat nomor dokumen, status, nama approver, dan waktu persetujuan; membuktikan keterhubungan dokumen transaksi dengan status approval, tetapi bukan seluruh aturan transisi.
- Kartu stok/laporan merangkum transaksi sebelumnya menurut barang dan periode; angka ditampilkan sebagai saldo, pemasukan, pengeluaran, adjustment, hasil pencacahan, selisih.
- Data produksi menggunakan nomor MO, nomor planning/PO Buyer, style dan kode barang; hubungan foreign-key tepatnya tidak tampak.

## Aturan proses yang belum dapat dipastikan

- Apakah persetujuan wajib untuk semua GRN/GSN, siapa approver, dan apakah transaksi bisa diedit/dibatalkan setelah disetujui: **NEEDS CONFIRMATION**.
- Makna dan versi dokumen BC, persyaratan validasi/format resmi, aturan edit dan rekonsiliasi: **NEEDS CONFIRMATION**.
- Apakah stok diperbarui saat simpan, saat approval, atau dari impor dokumen: **NEEDS CONFIRMATION**.
- Satuan, konversi unit, toleransi penerimaan, partial receipt, over/under delivery, costing, dan pembulatan: **NEEDS CONFIRMATION**.
- Apakah produksi dimodelkan sebagai alur wajib cutting → sewing → packing, kemungkinan rework/reject, dan kapan barang jadi masuk saldo: **NEEDS CONFIRMATION**.
- Aturan wajib/unik PO Buyer, No CBD, Destination, Currency, kalkulasi harga/pajak, validasi rentang tanggal, dan izin mengubah/menghapus detail yang sudah dipakai transaksi: **NEEDS CONFIRMATION**. Form menunjukkan satu baris barang disimpan setiap kali; satu MO dapat memiliki beberapa detail.
- Arti nomor PO Buyer dibanding PO Supplier; arti MO, MIS, GSN, Kaber, serta relasi satu-ke-banyak dokumen: **NEEDS CONFIRMATION**.
- Penghitungan saldo awal/akhir, stock opname, adjustment, barang hilang/rusak, retur, dan periode tutup buku: **NEEDS CONFIRMATION**.

## Contoh alur minimum untuk konfirmasi pemilik proses

Pilih satu dokumen penerimaan dan satu order produksi nyata. Telusuri nomor dokumen sumber → detail barang/kuantitas → approval → perubahan stok → transaksi produksi/retur → laporan. Tetapkan titik posting stok dan pihak yang dapat mengubah/membatalkan dokumen di tiap langkah sebelum implementasi transaksi.

# Damaged Items

- Catat nomor dan tanggal berita acara, barang, kuantitas, dan keterangan.
- Saat disimpan, kuantitas mengurangi saldo stok dan lot melalui transaksi persediaan; mutasi menyimpan referensi ke berita acara dan baris barang.
- Edit memakai mutasi kompensasi agar histori stok dan audit tidak dihapus.
- Nomor berita acara dihasilkan otomatis dari konfigurasi Document Numbering `damaged_item`.
- NEEDS CONFIRMATION: alur approval serta apakah barang reject harus dipisahkan ke lokasi/area karantina.

# Lost Items

- Barang hilang dicatat dengan nomor berita acara otomatis, tanggal, item, kuantitas, dan keterangan; simpan mengurangi stok dan tercatat di kartu stok.
- Edit menulis mutasi koreksi. Hapus membatalkan baris secara soft delete dan memulihkan stok dengan mutasi balik.
- NEEDS CONFIRMATION: alur approval dan aturan pencatatan kehilangan per lokasi/gudang.

## Order Bahan Baku

Order Bahan Baku dibuat dengan memilih nomor MO. Sistem menggabungkan kebutuhan material dari seluruh baris MO yang memakai nomor tersebut, lalu mengisi kode/nama barang, satuan, style, dan jumlah kebutuhan. Pengguna dapat menyesuaikan jumlah pada dokumen order. Setiap rincian mendapat nomor OBB tersendiri dan menyimpan referensi baris kebutuhan MO. Pembuatan order tidak mengubah stok.

**NEEDS CONFIRMATION:** apakah satu MO boleh dibuatkan beberapa Order Bahan Baku, apakah jumlah order dibatasi sisa kebutuhan, dan apakah dokumen ini harus dilanjutkan ke permintaan pembelian/pemasok.

## Kirim ke Produksi (MIS)

Daftar MIS menampilkan satu baris per Order Bahan Baku (OBB) dari produksi. Detail sebuah OBB memuat seluruh itemnya; gudang dapat memilih satu atau beberapa item untuk dikirim dalam satu nomor MIS. Pada setiap item, sistem menampilkan jumlah yang diminta, terkirim sebelumnya, jumlah kirim saat ini, dan total terkirim. Pengiriman parsial diizinkan; jumlah saat ini tidak boleh melebihi sisa OBB, sisa kebutuhan MO, atau stok/lot tersedia. Simpan membuat nomor MIS, merujuk ke rincian OBB, mengurangi stok dan lot, memperbarui jumlah terkirim OBB dan issued pada kebutuhan MO, serta membuat mutasi stok dengan referensi MIS. Status OBB berwarna biru selama masih ada item yang belum terkirim seluruhnya dan merah setelah semua item terkirim penuh. Aksi issue lama dari Material Request tetap merupakan jalur lama yang terpisah dan juga tercatat ke MIS.

**NEEDS CONFIRMATION:** referensi tidak menjelaskan approval MIS atau aturan pembatalan/edit setelah stok diposting. Karena itu dokumen MIS saat ini menjadi jejak pengeluaran langsung saat dikirim; tidak tersedia edit/hapus.

## Penerimaan Produksi dari Gudang

Modul Produksi menampilkan dokumen transfer MIS yang bersumber dari Order Bahan Baku. Detail menampilkan seluruh item MIS dan kuantitas yang dikirim/diterima. Aksi konfirmasi mencatat status diterima, pengguna, waktu, dan audit; tidak memutasi stok lagi karena stok gudang sudah dikurangi saat MIS disimpan. Kolom PO Supplier pada referensi belum memiliki relasi sumber pada alur OBB→MIS saat ini, sehingga belum diisi.

**NEEDS CONFIRMATION:** apakah penerimaan produksi hanya berupa konfirmasi seluruh dokumen MIS (seperti tombol Approve pada referensi), atau perlu menerima sebagian item/kuantitas; sumber relasi PO Supplier untuk detail MIS.
