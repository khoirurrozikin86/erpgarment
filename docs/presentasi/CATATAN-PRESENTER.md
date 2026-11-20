# Panduan presenter — PT Smart Dragon Industrial

Presentasi: 18 November 2026. Target: 15–20 menit materi + 5–7 menit demo + tanya jawab.

## Persiapan sebelum berangkat

- Buka PPT di PowerPoint/Keynote dan periksa font serta gambar. Gunakan Presenter View untuk catatan.
- Pastikan login, perusahaan aktif, periode, dan data demo tersedia di lingkungan yang akan dipakai. Seeder lokal sudah dijalankan; kondisi VPS perlu diperiksa sendiri sebelum demo.
- Buka tab Dashboard, MO, BOM/PO, penerimaan, WIP, GSN/BC, dan laporan.
- Coba satu export Excel dan simpan hasilnya. Jangan reset data/seeder saat presentasi.
- Siapkan laptop, charger, adaptor HDMI, salinan PPT offline dan aplikasi lokal yang sudah diperiksa sebagai cadangan.
- Screenshot di PPT berasal dari proposal sebelumnya dan diberi label ilustrasi. Struktur warna/size ditunjukkan sebagai diagram, bukan screenshot palsu.

## Jawaban singkat untuk pertanyaan umum

**Apakah stok langsung berubah ketika Simpan?** Bergantung proses modul. GRN perlu approval; penerimaan subkon menambah stok saat Konfirmasi Gudang. BC adalah administrasi, tidak memposting stok kedua kali.

**Apakah semua bahan harus punya size?** Tidak. Hanya bahan yang fisiknya berbeda; kain satu warna bisa digunakan lintas size, label yang berbeda size memakai SKU berbeda.

**Apakah sudah terhubung CEISA?** Cakupan saat ini administrasi BC. Integrasi eksternal dibahas terpisah.

**Kapan go-live?** Setelah scope, data, pelatihan dan UAT disetujui bersama; belum menetapkan durasi kontraktual.

**Harga?** Pembelian dan implementasi awal Rp84 juta sekali bayar; maintenance rekomendasi Rp18 juta/tahun terpisah. Pajak, termin, lisensi dan batas layanan mengikuti kesepakatan final.

**Jika demo error?** Beralih ke screenshot/lokal yang telah diperiksa, catat temuan, lanjutkan cerita alurnya. Jangan melakukan perbaikan server langsung di depan audiens.

## Catatan per slide

### 01. Satu alur data. Dari order hingga pengiriman.

Durasi pembuka 45 detik. Selamat pagi Bapak/Ibu. Hari ini saya akan menunjukkan bagaimana ERP menghubungkan PPIC, pembelian, gudang, produksi, subkon, dan EXIM. Fokusnya adalah ketertelusuran order sampai pengiriman, termasuk warna dan size. Setelah gambaran singkat, kita lihat satu alur demo dan pembahasan implementasi.

### 02. Apa yang ingin kita capai?

Jangan menyebut masalah ini sebagai fakta internal perusahaan. Katakan: tiga kebutuhan yang ingin kita validasi bersama adalah konsistensi order, kejelasan posisi barang, dan kemudahan pelaporan. Tanyakan singkat apakah ini sesuai prioritas Bapak/Ibu.

### 03. Alur utama yang saling terhubung

Jelaskan dari kiri ke kanan dalam 60 detik. BC melengkapi administrasi. Stok diposting oleh transaksi/approval terkait, bukan oleh pembuatan dokumen BC. Subkon, retur, scrap, dan mesin adalah cabang sesuai kebutuhan.

### 04. Cakupan aplikasi untuk tiap bagian

Kelompokkan per bagian, jangan bacakan seluruh menu. PPIC merencanakan, purchasing membeli, gudang mengendalikan perpindahan, produksi mencatat hasil, EXIM melengkapi BC, manajemen membaca laporan. Akuntansi mengikuti mapping yang disepakati.

### 05. Dashboard sebagai pintu pemantauan

Buka dashboard saat demo. Tunjukkan filter periode, aktivitas, dan antrean tindakan. Jelaskan bahwa angka dokumen bukan qty stok. Pilih perusahaan yang benar sebelum berpindah modul.

### 06. Order dapat dirinci per warna dan size

Ini ilustrasi struktur data, bukan screenshot. Contoh ABC warna BLACK: S=2, L=4, XL=7, total13. Satu nomor MO dapat memiliki beberapa rincian. Gambar dapat diunggah di MO. SKU varian belum ada dapat dibuat otomatis; tidak perlu input item satu per satu terlebih dahulu.

### 07. BOM menghubungkan order dan kebutuhan bahan

Tidak semua bahan harus punya size. Kain BLACK dapat dipakai lintas size. Label S/L/XL memakai SKU berbeda jika fisiknya berbeda. BOM khusus varian diprioritaskan; bila tidak ada, sistem memakai BOM induk dengan kondisi warna/size. Konsumsi harus divalidasi oleh PPIC.

### 08. Pembelian mengikuti referensi kebutuhan

Tunjukkan satu PO yang terkait PR/MR dari MO. Cari nama atau kode bahan dan tunjukkan warna/size bila ada. Soroti quantity dan supplier. Jangan membuat transaksi baru saat demo jika data contoh sudah siap; cukup buka detail dan export.

### 09. Penerimaan dan stok mengikuti konfirmasi

Gunakan contoh GRN menunggu dan GRN disetujui. Simpan draft belum menambah stok; approval GRN atau Konfirmasi Gudang penerimaan subkon memicu posting sesuai modul. BC tidak memotong atau menambah stok kedua kali. Jangan menggeneralisasi semua tombol Simpan di seluruh modul.

### 10. Monitoring produksi hingga posisi WIP

Contoh per varian: order25, cutting input25 hasilbaik24, sewing input22 hasilbaik21, packing input20 hasilbaik19, finishinginput18. Saldo tahap: CUT2, SEW1, PKG1. Qtyorder25 diulang pada tiap tahap hanya sebagai konteks, bukan untuk dijumlahkan. GSN10 dariFG18 menyisakanFG8 sebelum transaksi lain. Semua angka ilustrasi satu rantai simulasi.

### 11. Subkon sebagai cabang proses produksi

Tunjukkan kontrak, pengeluaran bahan/WIP dan penerimaan kembali. Nomor kontrak penerimaan dapat diisi manual sesuai fitur. Pilih perusahaan subkon dari master. Detail monitoring memperlihatkan qtykontrak, realisasi dan sisa sesuai data. Penerimaan subkon menambah stok saat konfirmasi gudang.

### 12. Administrasi BC masuk dan keluar

Tekankan cakupan pencatatan administrasi dokumen dan referensi GRN/GSN. Integrasi CEISA/API eksternal dibahas terpisah, bukan klaim sudah terhubung. Hindari menyimpulkan kepatuhan atau klasifikasi hukum dari modul; validasi dengan PIC EXIM.

### 13. Laporan untuk operasional dan penelusuran

Buka satu laporan WIP dengan warna/size, lalu contoh exportExcel. Pemasukan/pengeluaran,mutasi,WIP danproduksi memiliki tujuan berbeda. Untuk menelusuri asalbarang gunakan referensidokumen. Screenshot ini dari versi panduan sebelumnya, bukan bukti seluruhkolom varian terbaru sudah diperiksa pada VPS.

### 14. Demo singkat: satu order, satu cerita

Alokasikan5–7menit. 1Dashboard30detik.2MOgambarwarna/size1menit.3BOM&PO1menit.4GRN&kartustok1menit.5Cut/Sew/Pack/WIP2menit.6GSN&BC1menit.7Excel30detik. Gunakan data simulasi, hindari reset/seeder saat presentasi. Bila VPS bermasalah gunakan lokal yang sudah diperiksa; slide screenshot sebagai cadangan.

### 15. Implementasi dilakukan bertahap

Ini urutan usulan, bukan janji durasi. Sepakati PIC, scope, dataawal,approval serta mapping akuntansi. UAT dilaksanakan memakai transaksi perusahaan yang disetujui. Tanggal mulai dan go-live ditetapkan bersama. Jangan klaim aplikasi sudah lolos UAT karena ada simulasi/screenshot.

### 16. Harga pembelian aplikasi

Sampaikan tenang: harga pembelian aplikasi dan implementasi awalRp84juta, sekali pembayaran. Maintenance terpisah dijelaskan setelahini. Pajak,termin,haklisensi/sourcecode dan jadwal mengikuti penawaran/kesepakatan final. Jangan menganggap semuanya termasuk tanpa batas.

### 17. Maintenance tahunan yang direkomendasikan

Biaya18jutaper tahun,setara1,5jutaper bulan,ditagihkan tahunan. Rincian:VPS4,8;backupsecurity2,4;bugsupport4,8;updateminor3;alokasimayorterbatas3. Batas revisi,jamsupport,respons,backupretensi diaturkontrak. Bukan layanan pengembangan fitur tanpa batas.

### 18. Langkah berikutnya bersama Smart Dragon

Penutup45detik: kita ingin menyepakati prioritasproses,PICkeyuser dan skenarioUAT. Tanyakan bagianmana yang paling mendesak. Jika ditanya fituryangbelumterverifikasi,catat untukvalidasi; jangan menjanjikan langsung. Ucapkan terimakasih lalu undangdiskusi.

