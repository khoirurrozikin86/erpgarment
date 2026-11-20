# Sampel satu style dari awal

Seeder `ResetItemsForPoloSampleSeeder` menghapus transaksi dan seluruh item secara eksplisit, membuat backup JSON gzip privat (termasuk item dan metadata gambar), kemudian menjalankan `PoloThreeCombinationSeeder`. Tidak dijalankan otomatis oleh deployment/DatabaseSeeder. File gambar lama tidak dihapus agar backup tetap dapat dirujuk. Akun, perusahaan, supplier, customer, kategori, satuan, style lama, dan log audit dipertahankan.

Perusahaan dipilih SD001 aktif, fallback perusahaan aktif pertama. Sampel style SDI-POLO-001 dengan item global SDI-POLO (PCS), SDI-FABRIC (MTR), SDI-LABEL (PCS). Semua item master tanpa warna/size. BOM aktif output 1 PCS; 3 warna BLACK, NAVY, WHITE. Fabric konsumsi 1,2 MTR per output, ukuran kosong. Label konsumsi 1 PCS dengan kombinasi BLACK/S, NAVY/M, WHITE/L. Allowance 0%. Angka demonstrasi, bukan tech pack resmi.

MO, MR, PR, PO dan stok sengaja kosong untuk latihan manual. Buat satu MO dengan output item SDI-POLO dan tiga detail: BLACK/S 100 PCS, NAVY/M 150 PCS, WHITE/L 200 PCS. Buat MR dari aksi MO dan muat kebutuhan order. Ekspektasi fabric: BLACK 120 MTR, NAVY 180 MTR, WHITE 240 MTR (tanpa size); label: BLACK/S 100 PCS, NAVY/M 150 PCS, WHITE/L 200 PCS. Total fabric 540 MTR, label 450 PCS. Jangan menjumlahkan satuan MTR dan PCS.

Menambah sampel tanpa reset:

```sh
php artisan db:seed --class=PoloThreeCombinationSeeder --force
```

Reset destruktif transaksi dan item, lalu sampel:

```sh
php artisan db:seed --class=ResetItemsForPoloSampleSeeder --force
```

Reset telah dijalankan lokal atas persetujuan pengguna. Tidak dijalankan di VPS; tidak menjalankan test/build.

## Item pendukung

Seeder juga menyediakan benang, kancing, polybag, carton, WIP cutting/sewing/packing, scrap import/lokal/WIP, reject polo, mesin jahit, obras, mesin potong, gunting, dan setrika uap. Total 19 item global. Item pendukung belum ditambahkan ke konsumsi BOM; BOM latihan tetap fabric dan label. Saldo awal nol, tidak membuat transaksi atau nilai biaya buatan. Jalankan PoloThreeCombinationSeeder untuk menambah tanpa reset.
