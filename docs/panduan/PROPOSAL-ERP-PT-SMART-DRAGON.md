# PROPOSAL APLIKASI ERP GARMENT & IT INVENTORY
Untuk PT Smart Dragon Industrial
Application & Implementation Proposal
Versi 1.0 • 15 November 2026
Disampaikan oleh: Rozi • CP 089 637 498 586
Dokumen pembahasan dan persetujuan ruang lingkup

## Ringkasan Eksekutif / Executive Summary
Proposal ini menawarkan aplikasi ERP Garment & IT Inventory untuk mendukung pengelolaan alur kerja PT Smart Dragon Industrial, mulai dari perencanaan kebutuhan, pembelian, penerimaan gudang, produksi dan subkontrak, sampai pengiriman serta administrasi dokumen BC dan pelaporan.
Aplikasi menghubungkan referensi dokumen agar aktivitas dapat ditelusuri oleh bagian terkait. Dashboard menjadi pintu pemantauan; transaksi dan persetujuan tetap dilakukan pada modul masing-masing sesuai hak akses.
Harga pembelian aplikasi dan implementasi awal adalah Rp84.000.000, dibayar satu kali. Layanan maintenance direkomendasikan secara terpisah sebesar Rp18.000.000 per tahun. Ketentuan pelaksanaan ditetapkan bersama sebelum pekerjaan dimulai.
EN: This proposal covers a garment ERP and IT Inventory application with initial implementation for PT Smart Dragon Industrial. The one-time purchase price is IDR84,000,000. Recommended annual maintenance is offered separately at IDR18,000,000.

## 1. Informasi Perusahaan dan Tujuan
| Informasi | Keterangan |
| --- | --- |
| Perusahaan penerima | PT Smart Dragon Industrial |
| Alamat | Jl. Soekarno Hatta No.10B, Gembongan, Karangjati, Kabupaten Semarang, Jawa Tengah 50552 |
| Ruang penggunaan | PPIC, Purchasing, Gudang, Produksi, Subkon, EXIM dan Akuntansi sesuai hak akses |
| PIC pelanggan | Ditetapkan oleh PT Smart Dragon Industrial |
| Referensi penyusunan | Panduan Penggunaan Revisi Bilingual dan ruang lingkup aplikasi project |
Tujuan implementasi adalah menata pencatatan lintas bagian, memperjelas referensi transaksi, mempermudah pemeriksaan data, serta menyediakan laporan dan export Excel untuk kebutuhan operasional perusahaan.
Kebutuhan khusus Smart Dragon divalidasi saat kickoff. Proposal ini tidak menganggap seluruh ilustrasi dalam panduan sebagai aturan bisnis atau kewajiban implementasi tambahan.

## 2. Alur Kerja yang Diusulkan / Proposed Workflow
Master barang dan BOM → Material Order (MO) → Material Request (MR) → Purchase Request (PR) → Purchase Order (PO) → Goods Receipt (GRN) dan approval → pengeluaran bahan ke produksi → cutting → sewing → packing / hasil produksi → Goods Sent Notes (GSN) dan approval → administrasi BC OUT.
BC IN melengkapi administrasi dokumen pemasukan. Retur, subkon, scrap dan mesin merupakan cabang proses sesuai kebutuhan. Subkon tidak dipaksakan sebagai tahap wajib semua MO.
| Tahap | Pelaksana | Hasil yang dipantau |
| --- | --- | --- |
| Persiapan | Administrator / key user | Master, akun, role, penomoran |
| Perencanaan dan pembelian | PPIC / Purchasing | BOM, MO, MR, PR, PO |
| Penerimaan dan transfer | Gudang / pemeriksa | GRN, approval dan dokumen pengeluaran |
| Proses internal | Produksi | Hasil cutting, sewing, packing dan barang jadi |
| Proses eksternal | Pengelola Subkon / Gudang | Kontrak, pengeluaran, penerimaan, monitoring |
| Pengiriman | Pengiriman / pemeriksa | GSN dan persetujuan |
| Administrasi dan penelusuran | EXIM / manajemen | BC IN/OUT, laporan dan audit |
EN: The workflow links planning, purchasing, receipts, production, subcontracting, shipments and customs administration through source document references.

## 3. Ruang Lingkup Aplikasi / Application Scope
| Kelompok | Cakupan |
| --- | --- |
| Dashboard | Peta alur interaktif, aktivitas periode, antrean tindakan, ringkasan BC dan akses laporan |
| Pengguna dan pengaturan | Pengguna, role/hak akses, perusahaan, satuan, kategori, importir/eksportir dan penomoran sesuai fitur |
| Master PPIC | Barang, supplier, customer, style dan BOM |
| PPIC dan pengadaan | MO, MR, PR dan PO dengan referensi kebutuhan/pesanan |
| Gudang dan inventory | GRN, persetujuan penerimaan, transfer produksi, retur, kartu stok dan stock opname sesuai akses |
| Produksi | Order bahan baku/penolong, penerimaan gudang, penggunaan nonproduksi, cutting, sewing, packing, hasil produksi dan WIP |
| Pengiriman | GSN, persetujuan surat jalan dan penelusuran barang jadi |
| Subkon | Master, kontrak, pengeluaran bahan baku/WIP, penerimaan dan monitoring kontrak/barang |
| Scrap dan mesin | Pencatatan masuk/keluar scrap, mesin/peralatan dan kartu stok sesuai modul |
| BC IN | BC 2.3, BC 2.6.2, BC 2.7 Pemasukan, BC 4.0 dan BC 2.0 |
| BC OUT | BC 3.0, BC 2.6.1 Out, BC 2.7 Out, BC 4.1 Out dan BC 2.5 Out |
| Laporan BC/operasional | Pemasukan/pengeluaran, mutasi bahan, WIP, barang jadi, modal/lain, reject/scrap, produksi, retur dan audit |
| Akuntansi | Kas/bank, jurnal dan laporan sesuai fitur serta mapping yang disepakati |
| Bahasa dan export | Indonesia/English, pencarian menu, filter periode serta export Excel sesuai modul |
Daftar di atas mengikuti fondasi aplikasi. Kesiapan versi yang diserahkan diperiksa saat UAT; daftar modul bukan pernyataan bahwa seluruh kebutuhan khusus perusahaan atau integrasi pihak ketiga telah selesai diverifikasi.

## 4. Pengendalian Dokumen dan Stok
Pencatatan dipisahkan dari persetujuan yang memicu posting sesuai proses modul. Contohnya, penerimaan subkon disimpan sebagai draft dan stok bertambah saat Konfirmasi Gudang. GRN baru menunggu approval sebelum posting sesuai fondasi penerimaan.
Administrasi BC tidak menambah atau memotong stok kembali. Pengambilan GRN/GSN menjadi referensi dokumen dan harus diperiksa kesesuaiannya. Data BC masuk dan keluar dipisahkan menurut jenis/direction serta perusahaan pada modul BC.
Dashboard menghitung dokumen/catatan proses; jumlah tersebut bukan saldo stok dan tidak menjumlahkan satuan MTR, PCS, KG atau UNIT. Saldo dan pergerakan diperiksa melalui kartu stok/laporan terkait.
EN: Stock posting follows the relevant transaction and approval process. Customs records are administrative references and do not duplicate stock movements.

## 5. Hasil Pekerjaan / Deliverables
| Hasil | Bentuk penyerahan |
| --- | --- |
| Aplikasi terkonfigurasi | Instalasi dan konfigurasi pada lingkungan yang disepakati |
| Konfigurasi perusahaan dan akses | Perusahaan, akun awal, role dan izin yang disetujui |
| Persiapan master | Template, pemetaan dan pendampingan master awal sesuai cakupan yang ditetapkan |
| Panduan penggunaan | Dokumen panduan operasional dengan contoh tampilan bilingual |
| Pelatihan | Sesi per role sesuai jadwal, durasi dan peserta yang disepakati |
| UAT dan tindak lanjut | Skenario uji, hasil aktual, daftar temuan dan persetujuan |
| Go-live dan serah terima | Deployment, pemeriksaan akses dan berita acara |
Jumlah sesi, volume data, hak source code/lisensi, batas instalasi/pengguna serta masa pendampingan dituangkan dalam kesepakatan pelaksanaan. Tidak ada batas atau hak tambahan yang dianggap otomatis dari proposal ini.

## 6. Tahapan Implementasi / Implementation Plan
| Tahap | Aktivitas | Hasil / gerbang persetujuan |
| --- | --- | --- |
| 1. Kickoff | Menetapkan PIC, alur, ruang lingkup dan prioritas | Daftar kebutuhan disetujui |
| 2. Persiapan | Server, akses, perusahaan, role dan template data | Lingkungan siap |
| 3. Konfigurasi | Master, penomoran, akses dan mapping proses | Konfigurasi ditinjau key user |
| 4. Pelatihan | Latihan transaksi per bagian | Pengguna memahami alur dan approval |
| 5. UAT | Skenario end-to-end dan pemeriksaan laporan | Hasil uji dan temuan ditandatangani |
| 6. Go-live | Deployment dan pendampingan yang disepakati | Berita acara serah terima |
Tanggal mulai, durasi tiap tahap dan tanggal go-live ditetapkan bersama setelah scope, data dan infrastruktur siap. Proposal ini tidak menjanjikan jadwal yang belum disepakati.

## 7. Harga Pembelian Aplikasi / Application Purchase Price
| Uraian | Harga |
| --- | --- |
| Pembelian aplikasi ERP Garment & IT Inventory dan implementasi awal | Rp84.000.000 |
| Total pembelian aplikasi | Rp84.000.000 |
Terbilang: Delapan puluh empat juta rupiah.
One-time payment — pembelian aplikasi dan implementasi awal.
Harga di atas tidak digabungkan dengan biaya maintenance tahunan. Termin pembayaran, perlakuan pajak, masa berlaku penawaran dan rekening pembayaran ditetapkan pada dokumen penawaran/kesepakatan final sebelum penandatanganan. Tidak ada tambahan tarif pajak yang diasumsikan dalam proposal ini.

## 8. Maintenance Tahunan — Penawaran Terpisah
Maintenance direkomendasikan untuk mendukung pengoperasian aplikasi setelah implementasi. Detail kontrak layanan dituangkan dalam penawaran maintenance tersendiri, bukan sebagai tambahan tersembunyi pada harga pembelian.
| Uraian | Nilai |
| --- | --- |
| Maintenance tahunan yang direkomendasikan | Rp18.000.000/tahun |
| Setara per bulan | Rp1.500.000/bulan |
| Penagihan | Setiap tahun |
| Komponen | Biaya/tahun |
| --- | --- |
| Sewa VPS / Cloud Server | Rp4.800.000 |
| Backup, monitoring & keamanan | Rp2.400.000 |
| Perbaikan bug & technical support | Rp4.800.000 |
| Update sistem & revisi minor | Rp3.000.000 |
| Alokasi revisi mayor terbatas | Rp3.000.000 |
| Total | Rp18.000.000 |
Ruang lingkup dan batas revisi mayor/minor, spesifikasi server, frekuensi backup, retensi, jam dukungan, waktu respons dan periode kontrak disepakati terpisah. Alokasi revisi mayor terbatas tidak berarti pengembangan fitur tanpa batas.
EN: Annual maintenance is recommended and contracted separately. Service limits, infrastructure specifications and support commitments must be agreed in writing.

## 9. Peran dan Kesiapan PT Smart Dragon Industrial
1. Menunjuk PIC serta key user Administrator, PPIC/Purchasing, Gudang, Produksi, Subkon, EXIM dan Akuntansi sesuai penggunaan.
2. Menyediakan master dan saldo awal yang telah diperiksa, termasuk kode barang, satuan, supplier, customer, BOM dan order yang akan dipakai.
3. Menentukan operator input dan pemberi persetujuan sesuai SOP internal.
4. Menyiapkan atau menyetujui infrastruktur, akses, backup dan pengelolaan akun.
5. Mengonfirmasi aturan quantity, kuota, costing, akuntansi, pajak dan dokumen kepabeanan melalui pihak yang bertanggung jawab.
6. Menyediakan waktu pelatihan/UAT dan memeriksa hasil migrasi sebelum penggunaan operasional.

## 10. UAT dan Kriteria Serah Terima
| Area | Pemeriksaan penerimaan |
| --- | --- |
| Akses | Menu/tindakan sesuai role dan perusahaan yang digunakan |
| Master dan pengadaan | Referensi barang/BOM/MO/MR/PR/PO sesuai skenario |
| Persediaan | Posting pada aksi yang disetujui, kartu stok tertelusur dan tidak ada duplikasi |
| Produksi | Input dan hasil tahap sesuai MO dan catatan sumber |
| Subkon | Kontrak, pengeluaran, penerimaan dan monitoring sesuai data uji |
| BC | Jenis masuk/keluar benar; header, detail, referensi dan export sesuai input |
| Laporan | Periode, satuan dan angka hasil sesuai dokumen yang diperiksa |
| Operasional | Bahasa, navigasi, pelatihan dan dokumentasi ditinjau pengguna |
UAT memakai data dan lingkungan yang disetujui. Setiap temuan dicatat beserta prioritas serta tindak lanjut. Kriteria lolos dan persetujuan go-live ditandatangani bersama; lampiran screenshot bukan bukti UAT telah selesai.

## 11. Batas Lingkup dan Konfirmasi Lanjutan
Integrasi CEISA/API eksternal, sertifikasi kepabeanan, perangkat keras, payroll, mobile app khusus, migrasi seluruh histori, pembersihan data besar, integrasi bank/pajak serta pengembangan di luar scope memerlukan kesepakatan tambahan bila dibutuhkan.
Masih perlu penetapan proses: pemetaan Customer/Supplier/Subkon, batas penggunaan GRN/GSN lintas BC, quantity dan kuota subkon, Item Per Unit, valuasi scrap/mesin, posting jurnal dan prosedur koreksi setelah pelaporan. Hal tersebut tidak disimpulkan hanya dari tampilan aplikasi.
Perubahan scope diajukan sebagai change request yang mencantumkan pekerjaan, biaya, waktu dan persetujuan sebelum pelaksanaan. Ketentuan lisensi/source code, garansi, keamanan, kepemilikan data dan SLA ditetapkan dalam kesepakatan final.

## 12. Penutup dan Persetujuan
Kami berharap aplikasi ini dapat mendukung pencatatan serta pemantauan operasional PT Smart Dragon Industrial secara lebih terstruktur. Ruang lingkup, kesiapan data, rencana implementasi dan ketentuan komersial ditinjau bersama sebelum pelaksanaan.
| Pihak penyedia | Pihak PT Smart Dragon Industrial |
| --- | --- |
| Rozi | Nama: ____________________ |
| CP 089 637 498 586 | Jabatan: ____________________ |
| Tanggal: ____________________ | Tanggal: ____________________ |
| Tanda tangan: ____________________ | Tanda tangan: ____________________ |
Persetujuan mengikuti proposal dan lampiran scope final yang ditandatangani. Blok ini disediakan untuk penandatanganan, bukan menyalin persetujuan pelanggan.

## Lampiran A. Contoh Tampilan Aplikasi / Application Screenshots
Lampiran berikut diambil dari Panduan Penggunaan Revisi Bilingual yang diberikan. Gambar dan keterangannya dipertahankan untuk memperlihatkan contoh navigasi, master, pengadaan, laporan dan kartu stok. Tampilan serta hak akses dapat berbeda menurut versi dan role. Lampiran merupakan ilustrasi, bukan tambahan ruang lingkup otomatis atau bukti pengujian.
