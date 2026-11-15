# PANDUAN PENGGUNAAN APLIKASI ERP GARMENT
Panduan operasional • Versi 1.0 • 15 November 2026
Untuk: [Nama perusahaan pengguna]
Disiapkan oleh: [Nama penyedia / tim implementasi]

## Tentang panduan
Panduan ini menjelaskan alur aplikasi yang ada di project: PPIC, pengadaan, gudang, produksi, subkon, BC IN/OUT, persediaan, laporan dan akuntansi. Nama tombol dapat mengikuti bahasa yang dipilih. Menu yang terlihat bergantung pada hak akses akun.
Dokumen ini adalah draft pelatihan berdasarkan kode dan dokumentasi project, belum merupakan berita acara UAT atau pernyataan seluruh fitur telah terverifikasi di lingkungan produksi. Urutan dan aturan yang masih membutuhkan keputusan dicatat pada bagian akhir.

## 1. Mengenal alur aplikasi
Alur utama: master barang dan BOM → MO → Material Request → Purchase Request → Purchase Order → GRN dan persetujuan penerimaan → pengeluaran gudang ke produksi → cutting → sewing → packing / hasil produksi → GSN dan persetujuan pengiriman → administrasi BC OUT.
BC IN melengkapi administrasi dokumen barang masuk. Subkon, retur, scrap dan mesin merupakan cabang sesuai kebutuhan, bukan tahap wajib setiap order. Dokumen BC tidak otomatis menjadi transaksi penambah/pengurang stok.

| Bagian kerja | Tanggung jawab utama | Hasil yang diperiksa |
| --- | --- | --- |
| Administrator | Perusahaan, pengguna, role, hak akses, penomoran | Akses dan master siap |
| PPIC / Purchasing | Barang, BOM, MO, MR, PR, PO | Kebutuhan dan pesanan tertelusur |
| Gudang | GRN, persetujuan, kirim produksi, retur | Dokumen sumber dan kartu stok |
| Produksi | Terima bahan, cutting, sewing, packing, hasil produksi | Catatan tahap sesuai MO |
| Subkon | Kontrak, pengeluaran, penerimaan, monitoring | Realisasi dan sisa kontrak |
| EXIM | BC IN, BC OUT, laporan dan penelusuran | Kesesuaian dokumen dan referensi |
| Akuntansi | Jurnal, kas/bank dan laporan sesuai akses | Rekonsiliasi transaksi |

## 2. Masuk, bahasa dan navigasi
1. Buka alamat aplikasi yang diberikan administrator dan masuk menggunakan akun Anda.
2. Pastikan perusahaan aktif yang ditampilkan sesuai dengan perusahaan tempat bekerja.
3. Pilih Bahasa Indonesia atau English melalui pilihan bahasa pada header.
4. Gunakan kotak Cari menu untuk menemukan menu; ketik sebagian nama, misalnya “BC 2.5”, “subkon”, atau “retur”.
5. Klik modul dari sidebar. Tombol hamburger digunakan untuk merapatkan atau membuka sidebar.
6. Setelah selesai, keluar melalui menu akun, terutama pada komputer bersama.
Jangan memakai akun persetujuan milik orang lain. Minta administrator menyesuaikan role apabila menu atau tombol tidak tersedia.

## 3. Dashboard dan filter periode
Dashboard menampilkan peta alur yang dapat diklik, jumlah dokumen/catatan proses dalam periode, antrean tindakan, ringkasan BC IN/OUT, rencana produksi terbaru dan tautan laporan.
1. Pilih tanggal awal dan akhir, kemudian klik Tampilkan Data.
2. Default periode adalah tanggal 1 bulan berjalan sampai hari ini. Contoh tanggal akses 15 November: 1–15 November, bukan otomatis 30 hari mundur.
3. Klik kartu aktivitas atau tahap alur untuk masuk ke modul. Pada modul tujuan, periksa lagi periode yang berlaku.
4. Antrean saat ini dan jumlah kode master tidak mengikuti filter periode dashboard. Keterangan data bersama menunjukkan sumber yang belum dipisahkan per perusahaan pada fondasi aplikasi.
Jumlah dokumen bukan jumlah barang atau saldo stok. Jangan menjumlahkan MTR, PCS, KG dan UNIT menjadi satu nilai persediaan.

## 4. Persiapan administrator dan master
Sebelum transaksi pertama, siapkan perusahaan, akun pengguna, role, satuan, kategori, pemasok, pelanggan, style, barang, importir, eksportir dan subkon sesuai modul yang digunakan.
1. Masuk ke Pengaturan / Access menggunakan akun berwenang.
2. Tambahkan pengguna dan tetapkan role. Pisahkan operator input, pemeriksa dan pemberi persetujuan sesuai SOP perusahaan.
3. Periksa pengaturan penomoran dokumen. Nomor manual dan nomor otomatis mengikuti masing-masing modul; jangan mengganti pola tanpa koordinasi.
4. Di PPIC, masukkan master barang: kode, nama, satuan, jenis barang dan pengaturan relevan.
5. Pastikan kode bahan baku, bahan penolong, WIP, barang jadi, scrap serta mesin tidak tertukar.
6. Buat BOM untuk barang jadi sebelum membuat kebutuhan produksi.
Master yang sudah dipakai transaksi dapat dibatasi penghapusannya. Nonaktifkan master bila tersedia dan lebih sesuai daripada menghapus riwayat.

## 5. PPIC: BOM dan Material Order
### 5.1 BOM
1. Buka PPIC → BOM, pilih tambah/administrasi data.
2. Pilih barang hasil dan lengkapi bahan penyusun beserta kebutuhan konsumsinya.
3. Periksa satuan dan versi/status BOM yang dipakai, lalu simpan.
4. Periksa kembali komposisi sebelum MO/MR menggunakannya. Perubahan master tidak boleh dianggap otomatis mengganti snapshot transaksi lama.
### 5.2 Material Order (MO)
1. Buka PPIC → Material Order.
2. Isi nomor MO, tanggal, due date, Buyer, PO Buyer, CBD dan data lain yang diperlukan.
3. Cari barang jadi, isi style, quantity, harga satuan dan deskripsi bila digunakan.
4. Simpan dan periksa kembali total serta detail order.
5. Lanjutkan pembuatan kebutuhan bahan melalui Material Request sesuai MO/BOM.
MO dapat memiliki beberapa rincian dengan nomor header yang sama. Edit/hapus dapat ditolak bila sudah dirujuk transaksi atau tahap produksi berikutnya.

## 6. Pengadaan: MR → PR → PO
1. Material Request: pilih kebutuhan/MO, periksa item, jumlah dan tanggal kebutuhan, lalu simpan/ajukan sesuai tombol yang tersedia.
2. Pemeriksa meninjau dan menyetujui MR sesuai hak akses.
3. Purchase Request: buat permintaan pembelian berdasarkan kebutuhan yang diterima; periksa referensi dan jumlah.
4. Purchase Order: pilih pemasok, item, quantity, harga, termin dan tanggal terkait; simpan lalu terbitkan sesuai proses modul.
5. Gunakan status PO dan penerimaan untuk memantau jumlah yang telah diterima.
6. Simpan nomor MR, PR dan PO sebagai referensi saat rekonsiliasi.
Membuat permintaan atau PO tidak berarti barang sudah diterima dan tidak otomatis membuktikan stok tersedia.

## 7. Gudang: penerimaan GRN
1. Buka Gudang → Penerimaan Barang / Goods Receipt.
2. Buat GRN dan pilih PO yang sesuai. Periksa pemasok, referensi MO dan barang dari PO.
3. Isi nomor/tanggal invoice, tanggal penerimaan, quantity aktual, lot dan keterangan yang diminta.
4. Simpan dokumen. Periksa rincian sebelum meminta persetujuan.
5. Petugas berhak melakukan Approve penerimaan.
6. Setelah approved, periksa stok/lot dan kartu stok. Jangan membuat GRN baru hanya karena tampilan belum diperbarui; periksa status dan muat ulang dahulu.
Pada fondasi saat ini, GRN baru menunggu approval; posting stok dilakukan melalui aksi Approve. Penghapusan/koreksi dapat dibatasi jika sudah ada retur, pembayaran atau mutasi lanjutan.

## 8. Gudang ke produksi dan pengembalian
### 8.1 Pengeluaran bahan
1. Buka Kirim ke Produksi / Material Issue.
2. Pilih kebutuhan dan referensi order yang sesuai, lalu periksa barang, jumlah dan sumber stok/lot.
3. Simpan atau konfirmasikan sesuai tindakan yang tersedia pada modul.
4. Periksa dokumen pengeluaran dan kartu stok sebelum produksi mencatat penerimaan.
5. Produksi membuka Penerimaan Bahan dari Gudang, memeriksa MIS dan mengonfirmasi barang diterima.
Konfirmasi penerimaan produksi bukan pengeluaran gudang kedua. Jangan menggandakan transaksi untuk satu transfer.
### 8.2 Retur ke gudang
1. Produksi mencatat Return to Warehouse: barang, quantity, MO bila diperlukan, dan keterangan.
2. Gudang membuka Receipt from Production dan mencocokkan fisik dengan dokumen.
3. Gudang menyetujui penerimaan retur sesuai hak akses.
4. Periksa penambahan stok dan jejak kartu stok setelah persetujuan.

## 9. Produksi: cutting, sewing, packing dan hasil produksi
1. Pilih MO yang benar sebelum mencatat hasil tahap.
2. Cutting: masukkan tanggal, quantity cutting, kirim ke sewing, reject, bahan yang digunakan dan waste bila diminta.
3. Sewing: periksa penerimaan dari cutting, hasil sewing, reject dan jumlah yang dikirim ke packing.
4. Packing: isi penerimaan/input, hasil baik, reject serta penggunaan bahan sesuai form.
5. Hasil Produksi / Finished Goods: catat hasil tahap akhir dengan referensi MO dan tanggalnya.
6. Cocokkan catatan antar tahap melalui Laporan Status Produksi Harian dan Laporan Hasil Produksi.
7. Periksa kartu stok barang jadi sebelum menyusun pengiriman.
Input catatan tahap dan posting stok mengikuti aksi modul terkait. Jangan memakai dashboard sebagai pengganti pemeriksaan stok. Koreksi tahap sebelumnya dapat dibatasi setelah ada proses lanjutan.

## 10. Pengiriman: GSN dan persetujuan
1. Buka Goods Sent Notes / Surat Jalan dan buat dokumen pengiriman.
2. Isi tanggal, tujuan/pelanggan, kendaraan, pengemudi dan data pengiriman yang diperlukan.
3. Pilih MO/barang serta quantity, kemudian simpan.
4. Pemeriksa membuka Surat Jalan Approve, mengecek rincian lalu menyetujui sesuai hak akses.
5. Gunakan GSN approved sebagai referensi Ambil GSN pada modul BC OUT.
6. Simpan salinan dokumen dan cocokkan dengan kartu stok serta administrasi BC terkait.
Harga transaksi BC tidak otomatis sama dengan biaya stok. Isilah harga sesuai dokumen transaksi perusahaan.

## 11. Subkon
### 11.1 Master dan kontrak
1. Buat master perusahaan Subkon dan informasi yang diperlukan.
2. Catat kontrak: nomor, periode berlaku, rincian barang, quantity/kuota dan deskripsi.
3. Periksa kontrak sebelum membuat dokumen pengeluaran.
### 11.2 Pengeluaran bahan baku / WIP
1. Pilih Pengeluaran Bahan Baku Subkon atau Pengeluaran Barang Setengah Jadi Subkon.
2. Isi header nomor/tanggal surat jalan, penerima/Subkon, kontrak dan kendaraan sesuai form.
3. Cari item, isi quantity, style dan keterangan; gunakan Ambil Kontrak bila sesuai.
4. Simpan sebagai draft, periksa rincian, lalu petugas berhak melakukan Konfirmasi Gudang.
5. Periksa status dan kartu stok setelah konfirmasi; jangan mengulang konfirmasi untuk menambah realisasi.
### 11.3 Penerimaan subkon
1. Buka Penerimaan Subkon dan isi nomor/tanggal terima serta invoice.
2. Nomor kontrak dapat diisi manual. Pilih Subkon sebagai PT/perusahaan lawan transaksi yang sebenarnya.
3. Pilih item, quantity dan style, lalu simpan draft.
4. Setelah fisik cocok, lakukan Konfirmasi Gudang oleh petugas yang berhak.
5. Stok bertambah saat konfirmasi, bukan saat Simpan draft.
### 11.4 Monitoring
Gunakan Monitoring Kontrak untuk memeriksa periode dan data kontrak. Pada Monitoring Barang Subkon, pilih Detail Penerimaan atau Detail Pengeluaran untuk melihat rincian. Makna realisasi/sisa mengikuti referensi dokumen dan aturan kontrak yang telah ditetapkan; hindari menyamakan seluruh jenis kuota tanpa konfirmasi proses.

## 12. BC IN
Menu BC IN berisi BC 2.3, BC 2.6.2, BC 2.7 Pemasukan, BC 4.0 dan BC 2.0.
1. Pilih jenis BC yang benar, atur periode, lalu klik Administrasi Data.
2. Isi No Pengajuan serta nomor/tanggal BL-AWB, dokumen BC dan invoice.
3. Pilih Supplier; BC 2.6.2 menyediakan Supplier/Subkon. Pilih Importir pada form yang menyediakannya.
4. Isi Jenis Harga (CIF/CNF/FOB) dan valuta, lalu Simpan Dok BC untuk menyimpan header.
5. Gunakan Cari Barang atau Ambil GRN; periksa kode, satuan, referensi penerimaan dan quantity.
6. Isi harga unit, CIF bila tersedia, negara asal dan HS sesuai dokumen sumber.
7. Isi faktur per barang pada modul yang menyediakan kolom tersebut. Faktur detail terpisah dari invoice header.
8. Klik Simpan Semua dan periksa daftar detail sebelum export Excel.
BC 2.6.2 dapat mengambil penerimaan subkon yang telah dikonfirmasi. BC 4.0 tidak memakai input CIF pada detail. BC 2.7 masuk dan BC 2.7 keluar adalah modul/data terpisah.

## 13. BC OUT
Menu BC OUT berada setelah Subkon: BC 3.0, BC 2.6.1 Out, BC 2.7 Out, BC 4.1 Out dan BC 2.5 Out.
1. Pilih jenis BC, isi header dokumen, Customer, Exporter, jenis harga dan valuta.
2. Simpan Dok BC, lalu tambahkan rincian melalui Cari Barang / Ambil GSN.
3. Ambil GSN mengikuti perusahaan aktif, Customer dan pengiriman yang telah disetujui.
4. Periksa No Kirim, Tanggal Kirim, quantity, harga unit dan HS. Harga diisi sesuai dokumen transaksi.
5. BC 4.1 menyediakan Ambil GRN serta faktur per detail. Referensi penerimaan GRN bukan otomatis nomor pengeluaran; isi nomor/tanggal kirim secara terpisah.
6. BC 2.7 Out menyediakan negara pada detail. BC 2.5 menyediakan Item Per Unit sebagai angka manual opsional; belum ada rumus konversi otomatis.
7. Simpan Semua, periksa rincian dan export Excel sesuai periode/pencarian.
Pencatatan BC merupakan administrasi dokumen; tidak memotong stok lagi. Integrasi atau pengajuan otomatis ke CEISA belum menjadi fitur yang dinyatakan dalam panduan ini.

## 14. Scrap, mesin, retur dan inventory
| Area | Cara menggunakan | Pemeriksaan |
| --- | --- | --- |
| Scrap | Catat Scrap Masuk atau Scrap Keluar dengan item/satuan/quantity yang benar | Cocokkan dokumen dan kartu stok scrap |
| Mesin/peralatan | Catat masuk/keluar, kategori kepemilikan, supplier/customer dan rincian | Jangan anggap transaksi otomatis membentuk aset/depresiasi |
| Retur supplier | Pilih referensi dan item yang diretur, isi quantity serta keterangan | Cocokkan stok, pemasok dan laporan retur |
| Retur barang jadi | Gunakan dokumen retur yang relevan dan periksa data sumber | Data kosong pada periode bukan error |
| Inventory | Pilih barang dan periode pada kartu stok; stock opname sesuai hak akses | Bedakan saldo awal, masuk, keluar, adjustment dan saldo akhir |
Sebelum transaksi mesin/peralatan, pastikan kebijakan aset/stok perusahaan telah disepakati. Jangan membuat adjustment bebas untuk menutupi selisih tanpa pemeriksaan dokumen sumber.

## 15. Laporan dan export Excel
Laporan tersedia meliputi pemasukan/pengeluaran, mutasi bahan baku/penolong, posisi WIP, mutasi barang jadi, modal/barang lain, reject/scrap, rencana produksi, status harian, hasil produksi, retur dan audit.
1. Buka laporan yang diperlukan dan pilih periode.
2. Klik Tampilkan Data. Gunakan Search serta jumlah baris untuk mempermudah penelusuran.
3. Periksa satuan, referensi dokumen dan tanggal sebelum menyimpulkan saldo/selisih.
4. Pada laporan pemasukan/pengeluaran, gunakan penelusuran barang/MO bila tersedia.
5. Klik Export Excel. Pastikan periode dan pencarian sesuai kebutuhan; periksa jumlah baris hasil.
6. Simpan file dengan nama yang membedakan perusahaan, modul dan periode.
Log audit digunakan untuk menelusuri pengguna, waktu dan tindakan yang tercatat. Tampilan audit bukan pengganti persetujuan dokumen sumber.

## 16. Akuntansi
Menu Akuntansi berada pada bagian bawah sidebar. Gunakan akun berwenang untuk kas/bank, jurnal dan laporan yang tersedia. Periksa hubungan dokumen operasional dengan jurnal sebelum rekonsiliasi.
Tidak semua modul baru dinyatakan memiliki posting jurnal otomatis. Valuasi scrap, mesin, subkon dan perlakuan pajak harus mengikuti kesepakatan implementasi serta pemeriksaan akuntansi perusahaan.

## 17. Latihan satu siklus transaksi
Gunakan data latihan di lingkungan uji yang disepakati, bukan langsung pada data operasional.
1. Administrator menyiapkan satu bahan baku, satu bahan penolong dan satu barang jadi beserta satuan/BOM.
2. PPIC membuat MO latihan dan kebutuhan MR → PR → PO.
3. Gudang membuat GRN, pemeriksa menyetujui lalu memeriksa kartu stok.
4. Gudang mengeluarkan bahan ke produksi; produksi mengonfirmasi penerimaan.
5. Produksi mencatat cutting → sewing → packing → hasil produksi sesuai data latihan.
6. Buat GSN, lakukan pemeriksaan dan approval pengiriman.
7. EXIM mencatat BC IN/OUT yang relevan menggunakan referensi dokumen tersebut tanpa menggandakan stok.
8. Bandingkan laporan mutasi, posisi WIP, hasil produksi dan audit.
Catat nomor setiap dokumen, hasil aktual, selisih dan persetujuan penguji pada lembar UAT terpisah.

## 18. Pemeriksaan harian dan penyelesaian kendala
| Kondisi | Tindakan pengguna |
| --- | --- |
| Data tidak terlihat | Periksa perusahaan, periode, pencarian, hak akses dan status dokumen |
| Menu/tombol tidak ada | Hubungi administrator untuk pemeriksaan role/permission |
| Stok belum berubah | Periksa apakah masih draft/pending dan apakah approval/konfirmasi yang sesuai sudah dilakukan |
| GSN/GRN tidak muncul pada pemilih | Periksa status persetujuan, perusahaan, Customer/Supplier dan item sumber |
| Dokumen tidak dapat diedit/dihapus | Periksa apakah sudah ada transaksi lanjutan; gunakan proses koreksi yang disetujui |
| Export tidak sesuai | Samakan filter, muat ulang daftar, lalu export kembali |
| Selisih stok | Telusuri kartu stok dan dokumen sumber; jangan menggandakan input |
| Butuh bantuan teknis | Catat modul, nomor dokumen, tanggal, pesan error dan langkah kejadian; kirim melalui kanal dukungan perusahaan |

## 19. Catatan implementasi dan keputusan lanjutan
NEEDS CONFIRMATION: pemetaan Customer/Supplier/Subkon, batas quantity dan penggunaan referensi lintas BC, makna Item Per Unit, costing/kuota subkon, valuasi scrap/mesin, integrasi CEISA, aturan pajak/jurnal, serta prosedur koreksi setelah pelaporan.
Ketersediaan fitur harus dikonfirmasi pada versi aplikasi yang diserahterimakan. Jalankan pelatihan dan UAT per role sebelum pemakaian produksi. Dokumen ini tidak menetapkan SLA, harga, aturan legal atau janji integrasi eksternal.

## Lampiran: catatan pelatihan
Perusahaan: [Isi] • Lingkungan/versi: [Isi] • Tanggal pelatihan: [Isi]
Peserta/role: [Isi] • Trainer: [Isi]
Skenario berhasil: [Isi] • Kendala dan tindak lanjut: [Isi]
Persetujuan peserta dan penanggung jawab: [Isi]
