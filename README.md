# ERP Garment — IT Inventory

Aplikasi ERP untuk pencatatan persediaan garment, pembelian, bahan, dan operasional perusahaan. Fondasi saat ini mencakup master barang garment, Material Request, Purchase Request, Purchase Order, penerimaan barang, stok, dan akuntansi.

## Modul

- **Master data:** perusahaan, kategori, satuan, bahan, produk garment per model/warna/ukuran, supplier, dan HS Code.
- **Pembelian:** Material Request, Purchase Request, Purchase Order, penerimaan barang, dan retur pembelian.
- **Persediaan:** stok, kartu stok, stock opname, serta laporan persediaan.
- **Penjualan:** quotation, sales order, pengiriman, invoice, customer return, dan piutang.
- **Laporan:** pembelian, persediaan, kas/bank, penjualan invoice, serta laba rugi.
- **Keuangan dan akuntansi:** kas/bank, utang, akun, jurnal, dan laporan laba rugi.
- **Administrasi:** dashboard, pengaturan, pengguna, role, permission, dan audit log.

## Alur Operasional Persediaan

### 1. Siapkan master bahan dan barang

Daftarkan fabric, aksesori, kemasan, WIP, dan barang jadi. Untuk varian pakaian, gunakan SKU terpisah per model, warna, dan ukuran.

### 2. Ajukan kebutuhan pembelian

Ajukan kebutuhan melalui Material Request, lanjutkan ke Purchase Request, lalu buat Purchase Order untuk supplier.

### 3. Catat penerimaan barang

Masukkan kuantitas yang benar-benar diterima dari PO. Penerimaan akan menambah stok dan memperbarui biaya persediaan. Pencatatan lot, karton, dan dokumen kepabeanan menjadi tahap pengembangan berikutnya.

### 4. Siapkan alur produksi garment

Target alurnya adalah BOM dan order produksi, pemakaian bahan, WIP cutting/sewing/finishing, hasil barang jadi, dan packing per karton. Modul produksi serta pelacakan lot sedang disiapkan.

Sistem ini belum menyatakan kepatuhan atau persetujuan Bea Cukai. Format laporan dan akses IT Inventory perlu disesuaikan dengan fasilitas kepabeanan serta kantor pengawas perusahaan.

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

4. Jalankan migrasi:

   ```bash
   php artisan migrate
   ```

   Email dan password super admin bisa diatur di `.env`:

   ```dotenv
   SEED_ADMIN_EMAIL=
   SEED_ADMIN_PASSWORD=
   ```

   Jika email dibiarkan kosong, seeder memakai `superadmin@localhost.test`. Jika password dibiarkan kosong, seeder membuat password acak dan menampilkannya satu kali. Seeder tidak mengganti password jika akun tersebut sudah ada. Jalankan seeder setup user dan permission:

   ```bash
   php artisan db:seed --class=UsersAndPermissionsSeeder
   ```

   Seeder data awal ERP lainnya dijalankan sesuai kebutuhan lingkungan.

   Seeder tidak mengubah password akun yang sudah ada. Seeder dasar hanya menyiapkan role, permission, perusahaan, master kategori/satuan/supplier, penomoran dokumen, akun, dan kas/bank.

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

- `app/Domain/` — logika domain untuk perusahaan, pembelian, persediaan, penjualan, akuntansi, dan modul lainnya.
- `app/Http/Controllers/` — controller aplikasi.
- `database/migrations/` — skema database.
- `database/seeders/` — data awal.
- `resources/views/` — tampilan Blade.
- `routes/` — definisi route aplikasi.
