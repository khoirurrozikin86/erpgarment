# Simulasi lengkap warna dan size

Untuk **menghapus seluruh transaksi** dan membuat ulang simulasi (semua perusahaan), jalankan secara eksplisit:

```bash
php artisan db:seed --class=RebuildSimulationSeeder --force
```

Jalankan ketika aplikasi tidak sedang dipakai untuk input transaksi. Akun, hak akses, perusahaan, barang, gambar barang, supplier, customer, style, COA, rekening, dan pengaturan tetap disimpan. BOM termasuk transaksi yang dibangun ulang. Nomor urut dokumen tidak direset agar tidak menggunakan kembali nomor historis.

Backup transaksi dibuat sebelum penghapusan di `storage/app/private/simulation-backups/*.json.gz` (JSON per baris: nama tabel dan kumpulan baris). File ini bukan backup seluruh database/master. Jika pembuatan simulasi gagal, perubahan database dalam proses rebuild dibatalkan. Seeder rebuild tidak dipanggil oleh DatabaseSeeder maupun deployment.

Simulasi varian: BLACK, NAVY, LIGHT GREY × S, M, L, XL, 2XL, 3XL. Masing-masing memiliki 25 PCS order, kain sesuai warna, label sesuai warna/size, BOM, MR → PR → PO → GRN disetujui → pengeluaran bahan → konfirmasi produksi → cutting → sewing → packing → hasil produksi → GSN disetujui. Produksi parsial menyisakan WIP di tiap tahap. Dokumen BC masuk/keluar mengacu ke detail GRN/GSN yang sebenarnya. Stok mengikuti SKU varian, bukan gabungan warna/size.

Skenario umum tetap mencakup subkon draft/konfirmasi, scrap/reject, mesin/peralatan, retur, opname, pembayaran, jurnal, serta antrean approval. Barang umum/mesin/scrap tidak diberi size pakaian yang tidak relevan. Akun/master lama tidak dihapus atau ditimpa. Untuk menambah simulasi pada database yang sudah memiliki marker lengkap tanpa mengulang data, gunakan `SimulationSeeder`; rebuild di atas adalah perintah pengosongan yang disengaja.

---

## Catatan skenario dasar yang dipertahankan

# Data simulasi aplikasi

Seeder utama: `Database\Seeders\SimulationSeeder`, yang menjalankan dataset dasar V1, tambahan `SimulationModulesSeeder` (V2), dan `SimulationCompletionSeeder` (V3). Jalankan secara eksplisit; tidak dipanggil oleh `DatabaseSeeder`.

```sh
php artisan db:seed --class=SimulationSeeder
```

Prasyarat: migration aplikasi sudah diterapkan dan ada perusahaan aktif. Untuk database baru, jalankan instalasi/migration dan `DatabaseSeeder` lebih dahulu. Seeder memilih perusahaan aktif berkode SD001, atau perusahaan aktif pertama. Data bertanggal hari seeder dijalankan, sehingga terlihat dalam filter bulan berjalan.

## Cakupan

- Master: barang kain, label, barang jadi, WIP, scrap, reject, mesin dan peralatan; supplier, customer, style, BOM aktif, importir, eksportir, mitra subkon.
- PPIC/pembelian: MO 100 PCS, MR dan PR disetujui, PO diterbitkan, GRN disetujui 400 per barang, GRN pending 100 per barang, MR pending.
- Produksi: order bahan, pengeluaran gudang, penerimaan produksi, cutting 100 → 98, sewing 90 → 89, packing 80 → 79, hasil produksi 79, GSN 30 dan approval. Selisih antartahap sengaja menyisakan WIP.
- Order bahan penolong, penggunaan nonproduksi, WIP out, barang rusak/hilang, retur supplier, retur gudang dan barang jadi pending, stock opname draft.
- Penerimaan/pengeluaran scrap dan pencatatan reject; penerimaan/pengeluaran mesin dan peralatan.
- Subkon: kontrak, pengeluaran bahan baku dan WIP dikonfirmasi, penerimaan dikonfirmasi, hasil setengah jadi. Monitoring menggunakan dokumen tersebut.
- BC IN: 2.3, 2.6.2, 2.7, 4.0, 2.0. BC OUT: 3.0, 2.6.1, 2.7 OUT, 4.1, 2.5; setiap jenis mempunyai header/detail dan referensi GRN/GSN simulasi.
- Akuntansi: akun bank khusus SIM-BANK, modal simulasi Rp50 juta, jurnal otomatis sesuai action aplikasi, jurnal manual draft seimbang.
- Dashboard, laporan dan kartu stok membaca dokumen di atas; tidak diberi saldo/angka laporan buatan.

Contoh BC merupakan contoh administrasi untuk membuka seluruh layar, bukan deklarasi sah maupun skenario CEISA. Referensi penerimaan/pengiriman yang sama digunakan pada beberapa contoh tipe BC. Tidak ada pengiriman eksternal/email/API.

## Perilaku dan batasan

- Barang/mitra/deskripsi memakai awalan `SIM-`; nomor dokumen transaksi mengikuti penomoran aplikasi. MO menggunakan `SIM-MO-001`.
- Semua perubahan persediaan memakai action aplikasi dan dokumen sumber. Tidak mengubah saldo barang lama atau menghapus data lama. Data simulasi ikut laporan dan jurnal perusahaan yang dipilih.
- Konfirmasi stok penerimaan subkon dilakukan melalui action konfirmasi gudang. Simpan draft saja tidak menambah stok.
- Retur gudang/barang jadi dan stock opname sengaja draft/pending untuk latihan konfirmasi; belum diposting.
- Mesin/peralatan mengikuti perilaku action pencatatan yang sudah ada, tanpa menambah mekanisme posting stok baru.
- Kuantitas, harga, BOM dan kontrak adalah nilai latihan, bukan aturan bisnis baru. Tidak mengubah pengaturan aplikasi, akun login lama, role/permission, maupun kredensial integrasi. Halaman pengaturan tidak memerlukan transaksi demo tersendiri.
- Dua identitas `sim.operator@localhost.test` dan `sim.reviewer@localhost.test` digunakan sebagai pelaku audit/approval berbeda. Password awal keduanya `passwrod`; tidak diberi role otomatis. Gunakan akun admin yang sudah ada untuk menjalankan simulasi dari UI.
- Seluruh proses dibungkus satu transaksi database. Kegagalan membatalkan semua data proses tersebut. Marker audit `SIMULATION-V1-COMPANY-{id}` dibuat saat berhasil, sehingga pemanggilan berikutnya melewati pembuatan ulang.
- Jika kode barang SIM sudah ada tanpa marker, seeder berhenti agar tidak memakai saldo/data yang tidak diketahui asalnya. Tidak tersedia reset/penghapusan massal; jangan menghapus marker saja karena dapat menggandakan transaksi.


## Tambahan semua modul (V2 dan V3)

Perintah yang sama juga memperbarui database yang sudah pernah diisi V1. Setiap versi punya marker terpisah dan transaksi database sendiri; tidak perlu reset atau menghapus marker V1.

- Lima rantai tambahan: MO → MR → PR → PO → GRN disetujui → order bahan → MIS → konfirmasi produksi → cutting → sewing → packing → hasil produksi → GSN disetujui. Semua menggunakan FK dokumen/detail sumber.
- Tiap rantai mempunyai satu BC masuk dan satu BC keluar tersendiri; jumlah, harga, GRN/GSN sumber konsisten. Referensi ini berbeda dari contoh V1 yang menggunakan ulang satu sumber untuk memperlihatkan seluruh tipe dokumen.
- GRN memiliki tanggal dan nomor dokumen BC yang diperlukan filter laporan pemasukan. Metadata GRN V1 juga dilengkapi dengan referensi BC V1 yang sudah ada.
- Retur supplier dilakukan sebelum lot dikonsumsi. Retur bahan produksi disetujui gudang dan terhubung order bahan/PO asal.
- Tambahan antrean: MR ditolak, PR pending, PO draft, PO issued, MIS menunggu penerimaan produksi. Data tersebut sengaja tidak dipaksa menjadi selesai.
- Subkon memakai kontrak dan mitra yang sama untuk draft/konfirmasi pengeluaran bahan, WIP, serta penerimaan sehingga detail monitoring dapat dibuka dari kontrak.
- Stock opname disimpan dan diposting sesuai saldo fisik, tanpa penyesuaian buatan. Ada pembayaran sebagian supplier untuk lima GRN, beban kas/bank dan jurnal akrual posted; sisa utang tetap tersedia.
- Modul input customer-return lama sudah dihapus dari aplikasi, sementara laporan retur barang jadi masih membaca event `customer_return`. V3 menambahkan tiga **fixture retur stok**, masing-masing 1 PCS, berelasi ke header/detail GSN yang benar-benar sudah disetujui. Batas jumlah retur diperiksa terhadap pengiriman; biaya mengikuti alokasi lot pengiriman; lot retur dibuat observer persediaan. Event diberi nomor referensi SIM3-RET pada catatan dan audit. Fixture ini tidak membuat refund, pajak, faktur penjualan, atau aturan operasional baru. Ini satu-satunya fixture stok yang dibuat langsung di seeder karena tidak ada action input retur pelanggan yang aktif.
- Dokumen peralatan tetap mengikuti ledger penerimaan/pengeluaran peralatan, bukan dipaksakan masuk ProductStock.
- Dokumen pengaturan/email/dokumentasi yang tidak memiliki transaksi demo tetap mengikuti data dasar. Tidak mengirim email, tidak mengisi kredensial SMTP/CEISA palsu, dan tidak menambah modul yang sudah dihapus.

### Hasil pembacaan query laporan, 16 November 2026

| Laporan | Baris |
| --- | ---: |
| Pemasukan | 13 |
| Pengeluaran | 8 |
| Mutasi bahan baku/penolong | 2 |
| Mutasi barang jadi | 1 |
| Mutasi barang modal/lain | 2 |
| Mutasi reject/scrap | 2 |
| Retur gudang | 6 |
| Retur barang jadi | 3 |
| Posisi WIP | 3 |
| Rencana produksi | 7 |
| Status produksi harian | 7 |
| Hasil produksi | 6 |
| Retur supplier | 6 |

Jumlah di atas berasal dari query aplikasi dengan filter bulan berjalan, bukan data laporan terpisah. Kartu stok dan monitoring memerlukan pemilihan barang/kontrak di halaman masing-masing. Tidak ada test/build dijalankan; validasi menggunakan eksekusi seeder, guard transaksi aplikasi, pemeriksaan saldo stok/lot, serta pembacaan query laporan.

## Awalan perusahaan SDI

Default penomoran subkon kini memakai `SDI`. `DocumentNumberingSeeder` menjalankan `SdiDocumentNumberingSeeder` untuk mengganti prefix TIMW pada pengaturan, nomor dokumen, dan salinan nomor referensi terkait yang sudah ada. Nomor urut, ID/FK, stok dan transaksi tidak direset. Audit lama tetap dipertahankan; perubahan nomor dicatat dalam audit baru. Menjalankan ulang simulasi tetap melewati versi yang sudah tersimpan sehingga tidak menggandakan data.
