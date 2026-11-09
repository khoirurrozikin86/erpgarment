# ERP POS

Aplikasi ERP untuk mengelola data operasional, persediaan, pembelian, penjualan, kas/bank, dan akuntansi dalam satu sistem.

## Modul

- **Master data:** perusahaan, kategori, satuan, produk, supplier, customer, dan daftar harga.
- **Pembelian:** permintaan pembelian, purchase order, penerimaan barang, dan retur pembelian.
- **Persediaan:** stok, kartu stok, stock opname, serta laporan persediaan.
- **Penjualan:** quotation, sales order, pengiriman, invoice, customer return, dan piutang.
- **POS:** sesi kasir, riwayat transaksi, cetak/cetak ulang nota, void dengan refund, pengurangan stok, dan pencatatan biaya pokok.
- **Laporan:** penjualan invoice dan POS, pembelian, persediaan, kas/bank, serta laba rugi.
- **Keuangan dan akuntansi:** kas/bank, utang, akun, jurnal, dan laporan laba rugi.
- **Administrasi:** dashboard, pengaturan, pengguna, role, permission, dan audit log.

## Flow Operasional Pembelian hingga POS

### 1. Siapkan master barang

Buat produk dan lengkapi nama, kategori, satuan, harga jual, serta harga beli. Pastikan produk aktif dan pengaturan penjualannya sesuai.

### 2. Buat purchase order

Pilih supplier, masukkan produk dan kuantitas yang akan dibeli, lalu simpan PO.

### 3. Catat penerimaan barang

Dari PO, masukkan kuantitas barang yang benar-benar diterima. Setelah penerimaan dicatat, sistem menambah stok dan memperbarui biaya rata-rata persediaan (`average_unit_cost`).

### 4. Jual melalui POS

Setelah stok tersedia, lakukan transaksi di POS. Sistem memvalidasi ketersediaan stok dan biaya persediaan, lalu mencatat penjualan, mengurangi stok, dan menghitung biaya pokok penjualan.

> Jika POS menampilkan pesan bahwa biaya persediaan belum tersedia, periksa harga beli produk dan pastikan penerimaan barang sudah dicatat. Stok tanpa biaya yang valid tidak dapat digunakan untuk transaksi POS.

### 5. Proses Customer Return

Customer Return dibuat dari Sales Invoice yang sudah diterbitkan. Sistem membatasi kuantitas retur sesuai jumlah yang belum pernah diretur, mengembalikan barang ke stok dengan biaya pokok pengiriman asal, membuat jurnal retur dan pajak, serta mencatat refund keluar dari akun kas/bank yang dipilih.

### 6. Void transaksi POS

Riwayat di halaman kasir menampilkan transaksi milik kasir, termasuk transaksi dari sesi sebelumnya untuk cetak ulang nota. Setelah checkout, nota thermal otomatis dibuka untuk dicetak; popup browser perlu diizinkan. Void memerlukan alasan dan hanya dapat dilakukan oleh kasir pemilik transaksi selama sesi masih terbuka. Setelah sesi ditutup, transaksi tidak dapat di-void. Sistem tidak menghapus struk: status, pelaku, waktu, dan alasan void disimpan; stok serta biaya pokok dipulihkan; refund keluar dicatat ke akun pembayaran asal; dan jurnal penjualan dibalik. Transaksi void tetap terlihat di laporan, tetapi tidak masuk total penjualan.

```text
Master Barang
    ↓
Purchase Order
    ↓
Penerimaan Barang
    ↓
Stok dan biaya persediaan tersedia
    ↓
Penjualan melalui POS
```

## Teknologi

- PHP 8.2 atau lebih baru
- Laravel 12
- Composer
- Node.js dan npm
- Database SQLite atau MySQL

## Menjalankan Secara Lokal

1. Pasang dependensi PHP dan JavaScript:

   ```bash
   composer install
   npm install
   ```

2. Siapkan file environment dan application key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Atur koneksi database pada `.env`.

   Konfigurasi contoh menggunakan SQLite. Buat file database jika belum ada:

   ```bash
   touch database/database.sqlite
   ```

   Pastikan `.env` berisi:

   ```dotenv
   DB_CONNECTION=sqlite
   DB_DATABASE=/absolute/path/to/project/database/database.sqlite
   ```

   Untuk MySQL, atur `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai database lokal.

4. Jalankan migrasi dan siapkan data pengguna serta permission:

   ```bash
   php artisan migrate
   php artisan db:seed --class=UsersAndPermissionsSeeder
   php artisan db:seed --class=DocumentNumberingSeeder
   php artisan db:seed --class=CustomerReturnsAccessSeeder
   php artisan db:seed --class=PosVoidAccessSeeder
   ```

   Seeder data awal lain tersedia di `database/seeders`; jalankan seeder yang dibutuhkan sesuai lingkungan.

   Untuk database yang sudah berjalan, `CustomerReturnsAccessSeeder` dan `PosVoidAccessSeeder` menambahkan permission modul tanpa mengubah akun atau password yang sudah ada.

5. Jalankan aplikasi dan Vite di terminal terpisah:

   ```bash
   php artisan serve
   ```

   ```bash
   npm run dev
   ```

   Buka URL yang ditampilkan oleh `php artisan serve`.

## Build Asset dan Pengujian

Build asset untuk penggunaan tanpa Vite dev server:

```bash
npm run build
```

Jalankan test suite:

```bash
php artisan test
```

## Struktur Direktori

- `app/Domain/` — logika domain untuk pembelian, persediaan, POS, penjualan, akuntansi, dan modul lainnya.
- `app/Http/Controllers/` — controller aplikasi.
- `database/migrations/` — skema database.
- `database/seeders/` — data awal.
- `resources/views/` — tampilan Blade.
- `routes/` — definisi route aplikasi.
