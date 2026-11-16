# Deploy otomatis GitHub → VPS

Workflow: `.github/workflows/deploy.yml`. Script VPS: `scripts/deploy/deploy-vps.sh`.

Implementasi diasumsikan memakai Ubuntu Server 22.04, Apache 2.4 dan PHP CLI 8.4 (VPS saat ini melaporkan 8.4.21). Build GitHub juga menggunakan PHP 8.4. Domain serta handler PHP Apache (FPM/mod_php) masih perlu diketahui. Belum ada koneksi VPS maupun GitHub Secrets yang dipasang oleh perubahan ini.

## Alur

1. Pull request ke `main`: validasi Composer, syntax PHP, install dependensi produksi, dan build Vite dengan Node 22.
2. Merge/push ke `main`: langkah yang sama, kemudian unggah artifact release ke VPS melalui SSH.
3. VPS menyiapkan folder release dengan vendor dan hasil build dari artifact. Tidak perlu Git credentials atau Node di VPS.
4. Link `.env` dan `storage` bersama; cek platform PHP, discover package, dan cache konfigurasi/route/view.
5. Maintenance singkat → `migrate --force` → pindah symlink `current` → reload PHP-FPM jika dikonfigurasi → restart queue → aplikasi dibuka kembali.
6. HTTP health check `/up`. Bila gagal setelah perpindahan, kode kembali ke release sebelumnya. Pada deploy pertama tanpa release sebelumnya, maintenance diaktifkan untuk investigasi.

`migrate:fresh`, reset database, dan seeder tidak pernah dijalankan oleh workflow ini. File `.env`, kunci aplikasi, database dan upload tidak berada dalam artifact. Rollback kode **tidak** membatalkan migration database. Migration rilis harus kompatibel dengan kode sebelumnya dan backup database tetap diperlukan sebelum perubahan schema yang berisiko.

Dua deploy tidak berjalan bersamaan (GitHub concurrency + `flock` VPS). Commit yang sudah tertinggal dari HEAD `main` dilewati sebelum upload. Workflow manual tersedia melalui tab Actions, pilih branch `main`.

## Setup VPS sekali saja

Contoh berikut menggunakan user `deploy` dan grup web `www-data`; sesuaikan dengan server. PHP CLI dan PHP-FPM harus sama-sama 8.4 beserta extension pada workflow. Sediakan Composer 2, curl, tar, dan flock. Apache/handler PHP/database disiapkan terlebih dahulu.

```sh
sudo mkdir -p /var/www/html/erpgarment/shared/storage /var/www/html/erpgarment/releases
sudo chown -R deploy:www-data /var/www/html/erpgarment
sudo chmod 2750 /var/www/html/erpgarment /var/www/html/erpgarment/shared /var/www/html/erpgarment/releases
sudo chmod 2770 /var/www/html/erpgarment/shared/storage
```

Simpan `.env` production pada `/var/www/html/erpgarment/shared/.env`, permission `640`, owner `deploy:www-data`. Isinya antara lain:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.example.com
# Pertahankan APP_KEY existing jika memindahkan aplikasi yang sudah berjalan.
# Isi database, cache/session/queue, email, dan pengaturan server yang sebenarnya.
```

Untuk database kosong, buat APP_KEY baru satu kali. Jangan mengganti APP_KEY aplikasi existing. Seeder dasar instalasi baru dijalankan manual sekali setelah schema tersedia; bukan pada setiap deploy. `UsersAndPermissionsSeeder` project saat ini memakai password default, jadi ubah password admin sebelum aplikasi dibuka ke publik.

### Jika aplikasi sudah ada di VPS

- Gunakan direktori deploy baru jika folder `/var/www/html/erpgarment` masih berisi checkout lama; jangan menimpa atau menghapus folder lama.
- Salin `.env` existing ke `shared/.env` dengan APP_KEY dan koneksi DB tetap sama.
- Salin **seluruh** isi folder `storage` existing ke `shared/storage`, termasuk file privat/logo/upload. Sinkronisasi final saat aplikasi lama maintenance agar upload tidak terlewat.
- Arahkan webroot/panel ke `<VPS_APP_PATH>/current/public` setelah release pertama siap. `current` harus berupa symlink, bukan folder checkout lama.
- Untuk perlindungan rollback deploy pertama, siapkan release baseline dari aplikasi lama (kode, vendor, build) di `releases/baseline`, hubungkan `.env`/storage ke shared, lalu buat `current` menuju baseline sebelum deploy baru.
- Jangan pakai storage kosong untuk aplikasi existing.

### Apache, PHP dan proses latar

Contoh virtual host utama: `scripts/deploy/apache.example.conf`. Ganti `erp.example.com` dengan domain sebenarnya beserta path sertifikat, dan arahkan DocumentRoot ke `/var/www/html/erpgarment/current/public`. Template HTTPS mengasumsikan sertifikat sudah tersedia; jangan aktifkan path sertifikat contoh yang belum ada. `public/.htaccess` aplikasi memang memaksa HTTPS dan non-www. Contoh Nginx tetap disimpan hanya sebagai alternatif.

User deploy harus dapat menulis shared storage dan bootstrap/cache; user PHP-FPM juga harus dapat menulis keduanya. Reload FPM dapat dikonfigurasi melalui `PHP_FPM_SERVICE`, misalnya `php8.4-fpm`. Berikan sudo **hanya** untuk perintah reload service itu, sesuai path `systemctl` pada VPS. Jika memakai panel atau tidak mengisi variabel ini, pastikan OPcache memeriksa perubahan file dan server mengikuti symlink release baru.

Queue worker yang dikelola Supervisor/systemd harus menjalankan `/var/www/html/erpgarment/current/artisan queue:work`, dengan autorestart aktif dan working directory pada path current. Script mengirim sinyal `queue:restart`; process monitor yang menyalakan kembali worker. Jika menggunakan scheduler, cron mengarah ke path current:

```cron
* * * * * cd /var/www/html/erpgarment/current && php artisan schedule:run >> /dev/null 2>&1
```

## GitHub: cukup Repository Secrets

Tidak menggunakan GitHub Environment `production`, Environment Secrets, ataupun GitHub Variables. Pengaturan di tab Variables yang sudah dibuat tidak dibaca workflow dan boleh dibiarkan.

Buka **Settings → Secrets and variables → Actions → Secrets → New repository secret**:

| Secret | Isi |
| --- | --- |
| `VPS_HOST` | `43.133.146.37` |
| `VPS_PORT` | `22` |
| `VPS_USER` | `ubuntu` |
| `VPS_SSH_KEY` | Private key khusus deploy, tanpa passphrase |
| `VPS_KNOWN_HOSTS` | Baris host key SSH VPS yang sudah diverifikasi |

Public key pasangannya berada di `~ubuntu/.ssh/authorized_keys` VPS. Private key hanya di GitHub Secrets, bukan di repository/chat. Untuk port nonstandar, known_hosts menggunakan `[host]:port`.

Pengaturan nonrahasia ditetapkan langsung di `.github/workflows/deploy.yml`:

| Pengaturan | Nilai |
| --- | --- |
| Folder aplikasi | `/var/www/html/erpgarment` |
| PHP CLI | `/usr/bin/php8.4` |
| Web service | `apache2` |
| PHP-FPM | Deteksi service `php8.4-fpm` yang aktif; kosong jika tidak aktif |
| Health URL | Dibaca dari `APP_URL` pada `shared/.env`, ditambahkan `/up` |

`APP_URL` harus alamat aplikasi sebenarnya, termasuk HTTPS jika aplikasi memaksa HTTPS. Tidak perlu membuat variable `APP_HEALTH_URL` di GitHub. Jika FPM aktif, izin sudo reload service FPM juga diperlukan; template Apache dan PHP handler tetap harus disiapkan sekali di VPS.

Firewall SSH harus menerima koneksi runner GitHub yang digunakan. Workflow memakai GitHub-hosted runner, bukan IP tetap. Untuk membatasi akses ke IP tertentu, gunakan runner sendiri/jalur jaringan yang sesuai.

Aktifkan ruleset `main` dengan require pull request dan status check **Validate and build** jika production hanya boleh menerima hasil merge. Push langsung ke `main` juga men-trigger workflow jika masih diizinkan.

## Aktivasi dan pemeliharaan

Commit/push file workflow dan script melalui PR, pasang kelima Repository Secrets dan konfigurasi server, lalu merge PR ke `main`. Periksa **Actions → CI and Deploy VPS**. Tidak ada push/konfigurasi server yang dilakukan otomatis dari workspace ini.

Setelah gagal deploy, periksa log Actions dan `shared/storage/logs/laravel.log`. Folder release gagal dipertahankan untuk pemeriksaan. Jika migration sudah berhasil sebelum kegagalan kode, periksa kompatibilitas schema sebelum memilih rollback. Tidak ada `migrate:rollback` otomatis.

Release lama tidak dihapus otomatis agar riwayat kode tersedia. Hapus release lama hanya setelah memastikan bukan target `current`, bukan baseline/rollback yang diperlukan, dan storage/.env tetap berada di shared. Semua release memakai database yang sama.

CI saat ini menjalankan syntax/Composer validation dan build frontend, bukan PHPUnit. Tidak ada test/build lokal dijalankan saat menyiapkan file ini.

Rujukan: [GitHub continuous deployment](https://docs.github.com/en/actions/get-started/continuous-deployment), [deployment environments](https://docs.github.com/en/actions/concepts/workflows-and-actions/deployment-environments), [Laravel deployment](https://laravel.com/docs/12.x/deployment), [Laravel migrations](https://laravel.com/docs/12.x/migrations).


## Persiapan khusus VPS Ubuntu 22.04 / Apache milik project ini

Path yang dikonfirmasi ditafsirkan sebagai `/var/www/html/erpgarment` (tanpa titik di depan `/var`). Workflow sudah menjadikan path ini default. User SSH yang terlihat dari terminal adalah `ubuntu`; isi `VPS_USER=ubuntu` jika itu user deploy yang akan dipakai. User ini harus menjadi pemilik direktori deploy atau mempunyai hak tulis yang tepat. Jangan mengganti owner ke user `deploy` jika akun itu belum dibuat.

Periksa handler yang benar di VPS:

```sh
php -v
apache2ctl -M | grep -E 'php|proxy_fcgi|rewrite'
systemctl status php8.4-fpm --no-pager
```

PHP CLI 8.4 tidak membuktikan Apache juga memakai 8.4. Pastikan versi handler web sama dengan build. Tidak perlu mengganti atau menambah repository PHP pada VPS yang sudah mempunyai PHP 8.4 dan extension lengkap.

Aktifkan rewrite setelah memastikan konfigurasi virtual host yang benar. Jika FPM sudah terpasang, aktifkan juga konfigurasi FPM 8.4; jika mod_php yang dipakai, jangan jalankan langkah FPM berikut.

```sh
sudo a2enmod rewrite ssl
# Hanya untuk instalasi yang memang menggunakan PHP-FPM:
# sudo a2enmod proxy_fcgi setenvif
# sudo a2enconf php8.4-fpm
```

Sesudah mengganti domain dan sertifikat pada template, tempatkan konfigurasi pada `/etc/apache2/sites-available/erpgarment.conf`. Gabungkan dengan virtual host existing bila domain sudah dikonfigurasi; jangan mengaktifkan dua virtual host untuk domain sama.

```sh
sudo a2ensite erpgarment.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Aktivasi virtual host hanya dilakukan setelah baseline/current dan sertifikat tersedia. ServerName dan `APP_URL` di `shared/.env` harus menggunakan domain asli. Bila sertifikat belum ada, pertahankan virtual host existing dan siapkan sertifikat/domain lebih dahulu.

Untuk terminal/user `ubuntu` yang diberikan, contoh izin direktori (jalankan setelah menyesuaikan struktur existing, bukan menghapus aplikasi lama):

```sh
sudo mkdir -p /var/www/html/erpgarment/shared/storage /var/www/html/erpgarment/releases
sudo chown -R ubuntu:www-data /var/www/html/erpgarment
sudo chmod 2750 /var/www/html/erpgarment /var/www/html/erpgarment/shared /var/www/html/erpgarment/releases
sudo chmod 2770 /var/www/html/erpgarment/shared/storage
```

`shared/.env` milik `ubuntu:www-data` mode `640`. Gunakan `visudo` untuk mengizinkan perintah reload tanpa prompt. Contoh jika `systemctl` berada di `/usr/bin/systemctl`:

```sudoers
ubuntu ALL=(root) NOPASSWD: /usr/bin/systemctl reload apache2
# Tambahkan hanya bila menggunakan FPM:
# ubuntu ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm
```

Workflow sudah menetapkan path, PHP dan Apache secara langsung. Pastikan `.env` VPS mempunyai `APP_URL` sebenarnya. Semua Secrets dipasang pada tingkat repository; Environment `production` tidak diperlukan.

Referensi Apache: [Directory dan Options](https://httpd.apache.org/docs/2.4/mod/core.html), [PHP-FPM dengan mod_proxy_fcgi](https://httpd.apache.org/docs/2.4/mod/mod_proxy_fcgi.html).
