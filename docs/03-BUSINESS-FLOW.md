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

- GRN dan GSN memiliki aksi approval/cancel pada referensi. Untuk GSN, approval menyimpan user/waktu dan menjadi titik posting keluar stok; cancel membuat reversal jika belum ada mutasi produk yang lebih baru. Hak approval memakai permission khusus. Aturan approval GRN tetap sebagaimana dokumentasi penerimaan.
- Makna dan versi dokumen BC, persyaratan validasi/format resmi, aturan edit dan rekonsiliasi: **NEEDS CONFIRMATION**.
- Surat Jalan/GSN memiliki approval terpisah dan menyimpan user/waktu. Implementasi sebelumnya mengurangi stok saat GSN disimpan; perilaku tersebut dipertahankan. Apakah approval/cancel harus memindahkan atau membalik stok tetap **NEEDS CONFIRMATION**.
- Satuan, konversi unit, toleransi penerimaan, partial receipt, over/under delivery, costing, dan pembulatan: **NEEDS CONFIRMATION**.
- Apakah produksi dimodelkan sebagai alur wajib cutting → sewing → packing, kemungkinan rework/reject, dan kapan barang jadi masuk saldo: **NEEDS CONFIRMATION**.
- Aturan wajib/unik PO Buyer, No CBD, Destination, Currency, kalkulasi harga/pajak, validasi rentang tanggal, dan izin mengubah/menghapus detail yang sudah dipakai transaksi: **NEEDS CONFIRMATION**. Form menunjukkan satu baris barang disimpan setiap kali; satu MO dapat memiliki beberapa detail.
- Arti nomor PO Buyer dibanding PO Supplier; arti MO, MIS, GSN, Kaber, serta relasi satu-ke-banyak dokumen: **NEEDS CONFIRMATION**.
- Penghitungan saldo awal/akhir, stock opname, adjustment, barang hilang/rusak, retur, dan periode tutup buku: **NEEDS CONFIRMATION**.

### Scrap Masuk Gudang — implementasi awal

Pencatatan menerima satu barang scrap kategori `WASTE` dan menambahkan jumlah ke stok melalui mutasi `scrap_receipt`; mutasi memuat nomor dokumen sumber dan membuat lot stok generik. Edit/hapus ditolak jika sudah ada mutasi stok yang lebih baru untuk SKU scrap yang sama; koreksi dilakukan dengan mutasi pembalik agar riwayat tetap utuh. **NEEDS CONFIRMATION:** kebijakan biaya/valuasi dan jurnal, apakah No. Produksi wajib terhubung ke MO/produksi, item perca mana yang boleh menjadi sumber, dan kebutuhan dokumen multi-item.

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
# Return to Warehouse → Receipt from Production

Produksi mencatat satu item return per dokumen, memilih barang, kuantitas, dan opsional MO/PO Supplier. Dokumen berstatus pending dan tidak mengubah stok. Gudang melihat daftar return, membuka rincian, lalu approve. Approval menaikkan saldo ProductStock dan membuat StockMovement bertipe `production_return` yang membuat lot baru dan tampil di kartu stok. Cancel dari dokumen approved membuat mutasi balik negatif dan hanya berhasil bila saldo kini cukup; dokumen dan mutasi lama tetap terlacak.

**NEEDS CONFIRMATION:** apakah PO Supplier harus merupakan relasi PO asli (saat ini referensi teks), apakah satu return harus dapat memuat lebih dari satu item (form visual menunjukkan satu item per save), aturan kuantitas maksimum terhadap MIS/OBB yang pernah dikeluarkan, dan costing/lot asal retur. Sampai sumber MIS/lot tersedia, penerimaan tidak mengklaim lot asal dan mutasi tidak diberi nilai biaya.

## Order Support Material

Pengguna Produksi membuat order bernomor dan menambahkan satu atau beberapa rincian item. Tiap rincian menyimpan item, qty, style, dan referensi No. PO Other. Edit/hapus merujuk ke rincian; header dihapus lunak ketika rincian terakhir dihapus. Proses ini belum memutasi stok.

**NEEDS CONFIRMATION:** tujuan lanjutan order (pembelian atau transfer gudang), apakah style harus terkait master Style, serta apakah No. PO Other perlu menjadi relasi purchase order.

## Cutting

Cutting dicatat terhadap MO berstatus `in_progress` setelah material MO diterbitkan. Qty cutting mengurangi sisa kuantitas MO pada tahap cutting; qty kirim ke sewing dan reject harus menjumlahkan qty cutting. Waste dicatat terpisah dalam satuan bahan yang dipilih dari material MO. Hasil baik/reject ditulis sebagai stage entry `cutting` agar progres WIP yang sudah ada tetap terbaca. Edit/hapus diblokir setelah ada stage lanjutan.

**NEEDS CONFIRMATION:** kebijakan pengukuran waste bahan, unit/konversi bahan, dan apakah stok bahan di produksi perlu dikurangi kembali ketika ada waste. Modul mencatat waste saja; tidak membuat mutasi stok tambahan.

## Sewing

Sewing memakai MO aktif dan mengikuti hasil baik Cutting yang belum diproses Sewing. Qty kirim ke Packing + reject harus sama dengan qty Sewing; hasil baik disimpan sebagai stage entry `sewing` agar tahap berikutnya dapat menggunakannya. Edit/hapus diblokir jika tahap finishing atau packing sudah dicatat. Waste bahan dicatat dari material MO tanpa mutasi stok.

**NEEDS CONFIRMATION:** apakah waste bahan Sewing harus mengurangi saldo/cost bahan dan apakah istilah Cutting Reception pada referensi berarti total hasil baik Cutting atau jumlah penerimaan per dokumen.

## Hasil Packing

MO aktif hanya dapat dipacking sebesar hasil baik Sewing yang belum diproses. Qty barang jadi + reject wajib sama dengan qty Packing. Hasil baik menambah stok barang jadi, lot, dan kartu stok. Edit/hapus memakai mutasi kompensasi agar histori tersimpan; waste material hanya dicatat.

**NEEDS CONFIRMATION:** referensi menyebut No Planning; implementasi saat ini menampilkan No CBD MO. Status MO tidak otomatis diselesaikan setelah Packing.

## Penerimaan Packing ke Barang Jadi

Penerimaan memilih MO yang memiliki hasil baik Packing belum tercatat di tahap Barang Jadi. Qty terima dibatasi sisa output Packing; qty hasil Barang Jadi tidak boleh lebih besar daripada qty terima. Sistem menyimpan tahap `finishing` pada MO untuk menutup perubahan tahap sebelumnya.

Saldo stok tidak ditambah lagi di tahap ini: modul Hasil Packing yang sudah berjalan telah mem-posting hasil baik ke stok barang jadi. Pencatatan baru ini menjaga jejak penerimaan dan mencegah stok ganda.
