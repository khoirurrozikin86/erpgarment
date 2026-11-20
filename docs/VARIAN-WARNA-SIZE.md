# Varian warna, size, gambar dan order

Implementasi memakai master Product yang sudah ada sebagai SKU fisik. Tidak ada model stok varian kedua dan tidak ada konversi data lama otomatis.

## Pemakaian

1. Di Item, buat barang induk untuk satu model/style, misalnya KAOS-BAPE. Barang lama dapat tetap mandiri.
2. SKU anak dapat dibuat otomatis dari form MO (langkah 4), atau melalui master Item. Untuk membuat manual, buat SKU anak, misalnya KAOS-BAPE-BLK-S. Pilih barang induk, isi warna BLACK, size S, satuan yang sama, dan unggah gambar melalui fasilitas gambar barang yang sudah ada. Kombinasi warna/size dalam induk yang sama tidak boleh duplikat.
3. Di BOM induk, pilih SKU bahan yang benar. Kolom **Warna order** dan **Size order** adalah kondisi untuk produk hasil, bukan atribut bahan. Kosong berarti semua. Misalnya kain hitam hanya untuk BLACK, size S konsumsi 1,2; size M konsumsi 1,3. Baris umum dan khusus yang cocok bersifat tambahan, bukan saling menggantikan.
4. Di tambah MO, pilih barang/style induk, tulis warna, lalu isi size dan qty pada tabel **Isi beberapa size sekaligus**. Contoh BLACK: S=2, L=4, XL=7 menghasilkan tiga rincian dengan total 13 PCS dalam nomor MO yang sama. Tombol Tambah size menambah baris input. Varian yang belum ada dibuat otomatis dengan SKU unik; varian yang sudah ada dipakai kembali. Seluruh rincian disimpan dalam satu transaksi. Gambar memakai gambar varian, atau gambar induknya bila gambar varian belum ada. Harga per unit berlaku untuk semua baris size yang diinput pada penyimpanan itu. Untuk warna berikutnya gunakan nomor MO dan header yang sama. Edit dilakukan per rincian warna/size, mengikuti penguncian MO yang sudah berjalan.
5. Sistem memilih BOM aktif SKU hasil; bila tidak ada, memakai BOM aktif induknya. Tanggal efektif tetap diperiksa. BOM SKU khusus memiliki prioritas penuh. Hanya komponen dengan kondisi warna/size yang cocok digunakan; perhitungan konsumsi dan wastage mengikuti rumus sebelumnya.
6. Ambil kebutuhan MO pada MR, lalu lanjutkan approval MR → PR → PO seperti alur sebelumnya. Referensi product_id material yang sama diteruskan, sehingga warna/size bahan dan stoknya tidak bercampur. Form, detail, PDF, dan ekspor PO menampilkan warna/size dari detail MR yang terhubung, dengan fallback ke atribut produk.

Bahan bersama seperti benang hitam tetap satu SKU tanpa size bila fisiknya memang sama. Label atau hangtag yang berbeda size memakai SKU berbeda. Pengelompokan dilakukan lewat barang induk; kode SKU tetap unik. Varian bahan tidak ditentukan otomatis hanya dari kesamaan nama warna produk hasil: pemetaan dipilih eksplisit di BOM.

Identitas induk/warna/size yang sudah dipakai BOM, MO, MR atau pergerakan stok dikunci pada perubahan melalui master barang. Buat SKU baru untuk identitas lain. Harga, approval gudang, dan cara posting stok tetap mengikuti transaksi sebelumnya.

## Database dan deployment

Migration: `2026_11_17_000001_add_product_variant_bom_selectors.php` (sudah diterapkan di lokal).

Di VPS setelah kode diperbarui, jalankan:

```sh
php artisan migrate --force
```

Tidak perlu reset atau seed ulang. Migration menambahkan relasi induk dengan penghapusan restrict, indeks kombinasi varian, selector BOM, dan indeks BOM pengganti. Rollback dapat ditolak apabila kombinasi komponen baru bertentangan dengan indeks lama; jangan rollback tanpa meninjau data tersebut.

Tidak menjalankan test suite/build sesuai instruksi project. Validasi fungsional melalui browser belum dilakukan.

## Upload gambar langsung di MO

Form tambah/edit MO menerima gambar JPG, PNG atau WebP hingga 5 MB, disertai pratinjau. Gambar disimpan khusus pada rincian MO; tidak mengubah gambar master Item. Pada input beberapa size, satu gambar dipakai untuk seluruh rincian yang dibuat pada penyimpanan itu. Edit tanpa file baru mempertahankan gambar yang sudah tersimpan. Jika MO belum memiliki gambar khusus, tampilan memakai gambar Item/induk. Upload dibersihkan apabila transaksi penyimpanan gagal. Migration tambahan: `2026_11_17_000002_add_image_to_production_orders.php`, sudah diterapkan lokal.

MO yang sudah memiliki transaksi material/produksi tetap dapat memperbarui gambar melalui edit. Form hanya mengaktifkan upload gambar, dan server menyimpan image_path beserta audit tanpa menghitung ulang BOM/material atau mengubah header/qty/stok. Penguncian edit/hapus transaksi tetap berlaku.

## Monitoring produksi dan WIP per varian

- Cutting, Sewing, Packing Result dan Barang Jadi: daftar dan pemilihan MO menampilkan warna, size, serta qty order dari rincian MO, bukan total nomor MO. Label pada form/detail juga menyertakan varian. Ekspor menyertakan warna dan size.
- Laporan Rencana Produksi, Status Produksi Harian dan Hasil Produksi: kolom warna/size, pencarian varian dan ekspor Excel diselaraskan. Status harian juga menampilkan hasil packing hari ini dan kumulatif. Tidak ada pembulatan ke ribuan; kuantitas tetap angka transaksi aslinya.
- Posisi WIP sekarang dikelompokkan berdasarkan ID rincian MO + SKU + tahap CUT/SEW/PKG. Dua MO dengan SKU sama tidak digabungkan. Kolom nomor MO, style, warna, size dan qty order ditampilkan bersama saldo WIP tiap tahap. Qty order berulang pada setiap posisi tahap untuk konteks, bukan untuk dijumlahkan lintas tahap.
- Formula event WIP tetap mengikuti alur sebelumnya: hasil baik tahap sebelumnya dikurangi input ke tahap berikutnya, sesuai ID rincian MO. Pemilihan Sewing mengabaikan transaksi yang sudah soft-delete agar sejalan dengan WIP.
- Data lama tanpa varian ditampilkan dengan warna/size kosong (—); tidak dipecah atau dipindahkan otomatis. Tidak ada perubahan saldo stok atau seeding pada pekerjaan monitoring ini.

File utama: query Manufacturing untuk empat laporan dan empat tahap, controller Cutting/Sewing/Packing/FinishedGoods, Blade laporan/tahap, pemetaan BcReportDataTable dan export terkait. Tidak menjalankan test/build maupun pengujian browser sesuai instruksi project.


## Form MO ringkas dan impor rincian Excel

Form memakai dua kolom desktop, field di kiri dan label di kanan, kontrol ringkas serta pratinjau gambar 72px. Rincian warna/size memiliki area scroll sendiri; form tetap dapat discroll pada mobile.

Di Tambah MO: isi header dan pilih barang hasil, unduh **Template Excel**, isi kolom `color`, `size`, `qty`, kemudian **Impor rincian Excel**. Mendukung .xlsx maksimal 2 MB dan 100 baris, beberapa warna dan size untuk barang hasil/header MO yang sama. Impor mengganti rincian form setelah seluruh file valid, belum menyimpan transaksi. Periksa rincian dan total, tambahkan gambar bila diperlukan, lalu Simpan. Gambar tidak dibaca dari Excel. Edit tetap per rincian dan mengikuti penguncian transaksi.

Warna tiap baris dapat dikosongkan saat input manual untuk memakai warna utama. Kombinasi warna/size duplikat dan qty tidak positif ditolak server. Semua rincian disimpan melalui aksi MO/varian dalam satu transaksi; kegagalan membatalkan seluruh penyimpanan. Template dan pratinjau impor memerlukan permission `production-orders.create`. Label impor tersedia Indonesia/English. Tidak menjalankan test/build sesuai instruksi project; tampilan browser belum diverifikasi.

### Koreksi tata letak dan impor MO lengkap

Tombol **Impor MO lengkap** menerima kolom number, buyer_po_number, no_cbd, currency, destination, po_date, due_date, customer_code, style_code, color, size, qty, price_per_unit, description. Kode customer aktif wajib valid. Model/Style, warna, ukuran, dan kuantitas masuk ke detail MO draf. Satu nomor MO harus memiliki header yang konsisten. Nomor yang sudah ada ditolak untuk mencegah duplikasi. Pratinjau dan konfirmasi mendukung maksimal 500 baris di sheet pertama (.xlsx, 2 MB). Konfirmasi menyimpan semua baris dalam satu transaksi dan dapat memakai satu gambar opsional untuk seluruh impor.

Input manual dan impor MO otomatis mendaftarkan kode baru di **Master Style**. Setelah impor, buat item induk kategori Finished Goods jika diperlukan, lalu pilih Barang Jadi melalui **Ubah** pada tiap rincian MO. Sistem memakai atau membuat varian sesuai warna dan ukuran rincian. Rincian menjadi released saat Barang Jadi tersimpan. Master Item tetap dapat digunakan tanpa mengisi Model/Style.

Buka **Master Style → Input Komposisi** untuk mengisi BOM pada style baru. Dari MO yang seluruh rincian barang jadinya sudah diisi, buka **Buat Material Request**, lalu ambil kebutuhan bahan. Jika tersedia satu BOM aktif yang sesuai, sistem langsung menghitung kebutuhan. Jika ada beberapa, pilih berdasarkan nomor dan jenis BOM. Setiap MR menggunakan satu BOM dan menghitung rincian MO yang cocok. Snapshot kebutuhan tersimpan saat MR diajukan.

## Pemilihan sumber kebutuhan pada Material Request

Dropdown MO dan barang hasil/style di form MR diganti tombol Cari dengan popup DataTable, pencarian, pagination dan tombol Pilih. Tabel MO dibatasi perusahaan aktif dan status released/in_progress; menampilkan nomor MO, kode/nama barang, style, warna, size dan qty rincian. Pemilihan hasil/BOM hanya menampilkan master aktif kategori finished goods. Field ID sumber tetap tersembunyi; perubahan pilihan membersihkan snapshot kebutuhan sebelumnya. Tombol Ambil kebutuhan/Hitung tetap memakai endpoint dan validasi yang sudah ada; pemilihan saja tidak membuat transaksi. Tidak menjalankan test/build atau verifikasi browser sesuai instruksi project.


## Relasi MO header–detail

`production_order_headers` menyimpan identitas order unik per perusahaan dan nomor MO, buyer, PO buyer, CBD, mata uang, tujuan dan tanggal. Relasi `ProductionOrderHeader::details()` menunjuk rincian pada tabel `production_orders` melalui `production_order_header_id`. Tabel rincian dipertahankan agar semua FK MR, material, cutting/sewing/packing, WIP, GSN dan laporan tetap merujuk ID rincian yang sama. Kolom header pada rincian dipertahankan sebagai snapshot kompatibilitas, dan aksi create/update memeriksa/menyinkronkannya.

Migration `2026_11_17_000003_create_production_order_headers.php` mengelompokkan data lama menurut perusahaan + nomor MO, tanpa mengubah ID/qty/stok. FK header restrict delete; header terakhir dihapus lewat aksi hapus rincian bila sudah tidak memiliki detail. Perubahan header ditolak bila rincian lain sudah dipakai MR/produksi. Penguncian edit/hapus rincian tetap berlaku.

Daftar menampilkan satu baris per header dengan blok detail sejajar untuk barang/style, warna, size, qty, harga, gambar dan aksi; pagination menghitung header. Form matrix mendukung pilihan barang hasil/style per baris, atau mengikuti barang hasil utama jika kosong. Header diisi sekali; rincian memakai beberapa style/warna/size. Impor lengkap juga memakai relasi ini melalui aksi MO yang sama. Detail tetap menjadi unit monitoring produksi/WIP. Qty lintas tahap tidak dijumlahkan sebagai total order.

Migrasi sudah diterapkan di lokal. Di VPS setelah merge jalankan `php artisan migrate --force`. Tidak melakukan reset transaksi, menjalankan test/build maupun pengujian browser pada perubahan ini.


## MR berdasarkan seluruh nomor MO/order

Picker MR menampilkan satu pilihan per header MO dengan gabungan style/warna/size dan qty. Muat kebutuhan order menghitung semua detail dalam header, menjumlahkan `max(0, required_quantity - issued_quantity)` per material, lalu mengelompokkan berdasarkan product_id. SKU varian berbeda tetap dipisah; tidak menggabungkan barang hanya berdasarkan nama. Rumus sisa mengikuti fondasi lama; ini bukan perhitungan kekurangan pembelian setelah mengurangi PR/PO/stok gudang.

MR menyimpan production_order_header_id, snapshot source_details seluruh ID rincian dan BOM/versi/warna/size/qty, serta production_order_id rincian pertama untuk kompatibilitas tampilan lama. bill_of_material_id/output_product_id/bom_version tunggal dikosongkan karena satu order dapat memiliki banyak BOM/style. Saat submit sumber MO, server mengambil ulang seluruh kebutuhan; perubahan manual qty pada preview tidak mengganti perhitungan order. MR satu order diteruskan melalui alur PR/PO yang sudah ada sebagai satu dokumen dengan detail bahan. Sumber per detail tetap tersedia di snapshot MR/audit.

Semua rincian pada header yang dipakai MR gabungan dikunci dari edit/hapus transaksi MO; perubahan gambar tetap diperbolehkan. Migration `2026_11_17_000004_add_order_source_to_material_requests.php` sudah diterapkan lokal; data MR lama tetap seperti semula. Tidak mengubah stok atau melakukan reset/seeder. Tidak menjalankan test/build maupun browser check sesuai instruksi proyek.


Perbaikan alur MR: tombol Hitung kebutuhan bahan, bila sumber MO sedang dipilih, memuat ulang kebutuhan seluruh MO melalui endpoint MO (termasuk order satu detail). Endpoint BOM hanya dipanggil setelah barang hasil/style dipilih. Memilih barang hasil/style mengosongkan sumber MO agar kedua mode tidak tercampur; hasil MO gabungan tidak lagi ditulis sebagai product_id kosong pada pilihan BOM. Tidak perlu reset data/seeder.

### BOM manual dari master Style

- Buat dokumen melalui **Tambah BOM**. Pilih kode dari pencarian Master Style; tidak perlu memilih/membuat SKU barang jadi untuk header BOM.
- Nomor otomatis `BOM-` + ID dokumen delapan digit, misalnya `BOM-00000001`. Nomor unik dan tetap saat diedit.
- Header memakai `style_code`, sedangkan `product_id` nullable. Relasi BOM lama berbasis produk tetap dipertahankan.
- Satu Style dapat memiliki beberapa BOM aktif. Pilih jenis **Fabric**, **Aksesoris**, atau **Umum** pada tiap dokumen agar BOM terpisah mudah dikenali. Isi material, proses, warna, size, consumption dan allowance. Tambah Warna menambah baris untuk material/proses yang sama; Tambah Kolom Size mendukung kode size lain. Baris umum dan spesifik tetap bersifat aditif sesuai resolver sebelumnya.
- Daftar menampilkan dokumen yang sudah dibuat, nomor BOM dan master Style beserta rincian material; bukan daftar seluruh SKU barang jadi.
- Untuk kalkulasi MO/MR, resolver mempertahankan prioritas BOM produk lama (SKU lalu induk), lalu mencari BOM Master Style yang sesuai dengan kode Style di rincian MO. Warna/size menyaring rincian sebelum menghitung kebutuhan. Snapshot MO lama tetap dipakai.
- Jika Style memiliki lebih dari satu BOM aktif yang cocok, pengguna memilih nomor dan jenis BOM. Satu MO dapat memiliki beberapa MR, satu MR per BOM terpilih. Kebutuhan setiap BOM disimpan sebagai snapshot terpisah pada material MO; MR berikutnya menambah snapshot BOM-nya tanpa mengganti snapshot lama. MR yang belum ditolak mengurangi sisa kebutuhan BOM yang sama, dan pengeluaran bahan dialokasikan ke snapshot BOM MR tersebut.
- Migrasi: `2026_11_17_000005_add_style_and_number_to_boms.php`, `2026_11_18_000001_add_bom_type.php`, dan `2026_11_18_000002_add_bom_reference_to_production_order_materials.php`. BOM yang sudah ada diberi jenis **Umum** dan snapshot material MO lama ditautkan ke BOM yang sudah digunakan. Tidak ada perubahan saldo stok atau penghapusan data.
- Verifikasi sesi ini: `git diff --check` bersih. Test/build/migrasi tidak dijalankan sesuai instruksi proyek.

### Item induk dan permintaan beberapa varian

- **Buat Permintaan** kini halaman `/material-requests/create`, bukan modal. Modal hanya untuk mencari/memilih MO atau barang.
- Tabel Item menampilkan produk induk saja. Kolom warna/size pada form master diganti keterangan item induk; identitas data lama tetap dipertahankan saat diedit.
- Satu item dapat ditambahkan beberapa kali di MR dengan warna/size/qty berbeda melalui tombol **+ Varian**. Ringkasan menjumlahkan qty per induk dan satuan. Baris dengan item yang sama dan warna serta size kosong disimpan sebagai satu item global dengan qty dijumlahkan; item tanpa varian tetap dibatasi pada `product_id` yang sama. Warna/size boleh kosong untuk bahan tanpa varian.
- `ProductVariantService` memakai resolver varian yang sebelumnya dipakai MO; nomor SKU internal tetap unik, tetapi label permintaan menampilkan item global dengan dimensi pada kolom terpisah. Penyimpanan varian dan MR berada dalam satu transaksi, dengan audit. MR sendiri tidak mengubah stok.
- Baris dengan SKU/varian identik dijumlahkan ketika disimpan. PR/PO/penerimaan tetap membawa `product_id` varian, sehingga stok tidak tercampur antar warna/size. Warna dan size tiap baris PR ditampilkan mengikuti detail MR yang menjadi sumbernya.
- Permintaan bersumber dari MO boleh memecah bahan induk menjadi varian, dengan total qty setiap bahan wajib sama dengan kebutuhan MO. Jika snapshot MO sudah menunjuk SKU varian tertentu, identitasnya tetap dikunci.
- Pengeluaran MR mengambil stok/lot SKU yang dipilih dan mengalokasikan kebutuhan pada seluruh detail header MO, dengan fallback bahan induk untuk varian hasil pemecahan. Tidak ada pemindahan saldo induk otomatis ke varian.
- Kolom **Total stok item** = stok induk + seluruh varian, mengikuti cakupan stok global yang sudah dipakai aplikasi. Tombol **Varian** membuka rincian DataTable warna/size/qty/satuan; kartu stok per SKU mengikuti permission `stock-card.view`.
- Tidak menghapus data, mengganti ID transaksi lama, atau mengubah saldo. Tidak ada migrasi baru untuk perubahan ini. Test/build/browser tidak dijalankan sesuai instruksi proyek; diff diperiksa tanpa whitespace error.

### Penyamaan rincian kalkulasi dan tabel permintaan

- Form MR memuat baris langsung dari breakdown kebutuhan MO/BOM, bukan hanya total SKU gabungan. Qty diminta memakai `request_quantity` dari baris kalkulasi.
- Kedua tabel dikelompokkan berdasarkan item induk, kemudian size dan warna. Urutan size standar XS, S, M, L, XL, XXL/2XL, 3XL, 4XL, 5XL; kode lainnya tetap didukung.
- Rincian Kalkulasi Bahan pada form dirender dari baris Barang yang Dibutuhkan yang sama. Perubahan barang, size, warna, qty maupun penghapusan baris langsung diselaraskan. Kolom consumption/order/allowance berasal dari metadata hasil kalkulasi; baris manual tidak diberi angka consumption rekaan.
- Untuk material yang sudah memiliki SKU varian, warna/size mengikuti identitas SKU tersebut. Material induk memakai rincian warna/size MO sebagai isian awal. Total MO tetap divalidasi server sebelum disimpan.
- Saldo stok tidak diubah. Test/build/browser tidak dijalankan sesuai instruksi proyek; diff diperiksa.

### Dimensi bahan BOM pada MR

Warna dan ukuran dari selector detail BOM ikut disalin ke snapshot bahan MO baru/diperbarui, lalu ke detail kalkulasi dan baris MR. Ukuran kosong tetap kosong (fabric tidak mewarisi ukuran baju). MO lama memakai kecocokan BOM produk/proses/konsumsi/allowance dan selector output hanya bila ditemukan satu baris yang cocok. Tidak mengubah kuantitas maupun saldo stok. Migrasi 000007 menambah kolom snapshot color dan size. Tidak menjalankan test/build sesuai instruksi project.

### Hapus BOM tanpa memutus transaksi lama

Atas permintaan pemilik, relasi MO/MR tidak lagi menghalangi hapus BOM. Hapus memakai SoftDeletes: BOM tidak muncul pada daftar/pemilihan baru, tetapi rincian historis tetap ada dan relasi billOfMaterial pada MO/MR memakai withTrashed. Ini menjaga pembagi output BOM dan kebutuhan bahan transaksi lama. Audit DELETE dicatat. Tidak menjalankan penghapusan BOM pengguna maupun test/build. Migrasi 000008 diperlukan.

### MR dari MO: ringkasan sebelum pemilihan BOM

Buka Buat MR dari MO menampilkan nomor MO dan matriks warna/size/qty tanpa memuat bahan otomatis. Ambil Data dari BOM membuka modal DataTable berisi BOM referensi detail MO (termasuk riwayat BOM yang dihapus). Pilih memuat snapshot kebutuhan untuk detail MO yang menggunakan BOM terpilih; grouping bahan dan ringkasan permintaan tetap tersinkron. Server memvalidasi BOM terpilih benar-benar terkait MO dalam perusahaan aktif dan menghitung ulang total saat simpan. Jika MO mempunyai beberapa BOM, satu pemilihan mengisi kebutuhan detail terkait BOM itu. Tidak mengganti konsumsi historis MO memakai BOM lain. Tidak menjalankan test/build atau transaksi sampel.

### Perbaikan cakupan warna/size saat mengambil BOM

Modal MR juga menampilkan BOM aktif sesuai style, selain referensi historis MO. Untuk MO tanpa MR, pengeluaran/pemakaian bahan, atau catatan produksi, preview menghitung ulang seluruh detail order dari BOM terpilih. Snapshot bahan MO diperbarui hanya ketika MR disimpan, dalam transaksi database yang sama dan dicatat audit. Preview GET tidak menyimpan perubahan. Jika MO sudah berjalan, snapshot lama dipertahankan dan pergantian BOM ditolak. Selector ukuran yang hilang untuk item/proses pada warna terkait menghasilkan validasi eksplisit, bukan menghilangkan material diam-diam. Ringkasan per item merinci size, warna, qty dan satuan, serta subtotal item; fabric tanpa size digabung per warna. Tidak menjalankan test/build dan tidak mengubah transaksi pengguna pada pengerjaan ini.

### Cutting banyak detail MO dalam satu penyimpanan

Pemilihan MO menampilkan seluruh detail MO (ID produksi masing-masing), warna, ukuran, qty order PCS dan sisa output. Memilih fabric memuat sisa kebutuhan fabric per detail. Qty bahan, hasil baik ke sewing, reject dan waste diisi per detail; total atas readonly. Satu submit disimpan menjadi entry cutting masing-masing detail dalam satu transaksi database; baris nol dilewati. Semua ID diverifikasi berasal dari header MO/perusahaan yang sama; tiap output baik+reject dibatasi sisa qty order per detail, pemakaian fabric dibatasi kebutuhan fabric tersisa per detail dan product. Pengeluaran fabric tetap mengikuti konfirmasi/aturan gudang yang sudah ada. Tidak mencampur batas MTR bahan dengan PCS hasil. Halaman edit menunjukkan warna/size detail entry. Tidak mengubah skema/data dan tidak menjalankan test/build. Penghapusan BOM yang diminta sebelumnya belum dijalankan karena approval review mencapai usage limit.

### Cutting per detail MO

Form Cutting memuat detail warna/ukuran dari MO. Setiap detail memilih bahan fabric sendiri dari snapshot bahan detail tersebut. Validasi bahan sudah dikeluarkan dan batas pemakaian tidak lagi mencakup detail lain dalam MO. Jumlah hasil baik + reject dibatasi sisa kuantitas masing-masing detail; baris nol dilewati. Entri tetap memakai ID detail MO sehingga Sewing dan WIP mengikuti detail sumber. Ekspor memisahkan kuantitas hasil cutting dari kuantitas bahan yang digunakan beserta satuannya.

### Rekonsiliasi tahap dan kartu stok

- Urutan proses: Cutting → Sewing → Packing → penerimaan Barang Jadi. Validasi sisa menggunakan `ProductionStageBalance`, berdasarkan ID detail MO, bukan nomor MO gabungan. Input Cutting merupakan bahan (misalnya MTR); kuantitas MO dan hasil Cutting adalah PCS, sehingga batas hasil menggunakan baik + reject.
- Input Sewing mengurangi posisi CUT; input Packing mengurangi posisi SEW. Posisi PKG menunjukkan hasil Packing yang belum dicatat dalam Penerimaan Barang Jadi. Posisi ini menunjukkan progres produksi. Stok barang jadi dibukukan saat Hasil Packing. Penerimaan Barang Jadi tidak menambah stok kedua kali.
- Kartu stok Barang Jadi dan Excel menampilkan warna dan ukuran produk varian. Setiap varian tetap menggunakan product ID sendiri di saldo, lot, dan mutasi yang sudah ada; tidak membuat master `/CUT` atau `/SEW` tambahan.
- Pencatatan Cutting/Sewing dari endpoint tahap umum diarahkan ke modul khusus agar validasi bahan dan detail tidak terlewati.
- Lokasi fisik produksi belum dimodelkan di stage entry. Dokumen WIP OUT/subkon terpisah yang belum berelasi dengan detail MO tidak otomatis direkonsiliasi ke saldo tahap; jangan menyimpulkan keduanya sudah sinkron. Tidak ada migrasi saldo historis dalam perubahan ini.

## Saldo bahan dari rincian transaksi

Penerimaan yang disetujui memposting saldo dan lot ke varian berdasarkan warna dan ukuran pada rincian MR yang terhubung lewat PO. Jika rincian tidak membawa dimensi, transaksi memakai item yang dipilih pada dokumen. MIS dari MR memakai dimensi rincian MR. MIS dari Order Bahan Baku memakai dimensi rincian order atau material MO sumber, serta memperbarui jumlah terkirim pada sumber tersebut. Pengeluaran varian memakai saldo dan lot varian yang bersangkutan. Kode bahan yang sama dengan warna atau ukuran berbeda tetap memiliki saldo terpisah.

Master Item menampilkan saldo global sebagai jumlah saldo item induk dan varian. Klik rincian varian untuk membuka saldo masing-masing dan kartu stoknya. Retur supplier mengikuti item dan lot penerimaan. Koreksi MIS memakai SKU yang benar-benar diposting. Saldo global historis tetap tercatat di SKU asal. Rekonsiliasi fisik diperlukan sebelum saldo historis dipindahkan ke varian.
