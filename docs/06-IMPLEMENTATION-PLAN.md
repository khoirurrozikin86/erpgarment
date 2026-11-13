# Rencana Implementasi

Rencana ini mengurutkan dependensi proses, bukan persetujuan untuk mulai coding. Project telah memiliki fondasi Laravel dan beberapa domain ERP: master produk/supplier/customer/unit, BOM, permintaan dan PO, GRN/stok, retur, stock opname, production order/stage, inventory lot serta role/permission. Periksa implementasi aktual sebelum membuat ulang komponen; dokumentasi screenshot menambah kebutuhan IT Inventory seperti BC, approval, stage garment lengkap, subcontract dan laporan terkait.

Persetujuan implementasi diberikan pengguna pada 2026-11-13. Progres per modul dicatat di [IMPLEMENTATION-PROGRESS.md](IMPLEMENTATION-PROGRESS.md); implementasi tetap satu modul per instruksi.

## Tahap 0 — Validasi analisis dan aturan

1. Minta persetujuan atas dokumentasi dan urutan ini sebelum implementasi fitur baru.
2. Konfirmasi status approval, titik posting stok, definisi dokumen/nomor, multi-gudang/lot, satuan, costing, dan relasi BC.
3. Pilih skenario ujung-ke-ujung barang masuk dan satu MO produksi untuk menjadi kriteria penerimaan awal.

## Tahap 1 — Gap analysis fondasi dan master

- Cocokkan model, migration, route, UI, permission dan laporan project terhadap peta modul.
- Tutup gap master: kategori/jenis bahan, style/varian, importir/eksportir, user/department/position, HS code, BOM dan administrasi nomor dokumen.
- Tetapkan desain master barang, unit/konversi, gudang/lokasi, serta audit.

## Tahap 2 — Ledger stok dan transaksi gudang

- Pastikan penerimaan, pengeluaran, transfer, retur, rusak/hilang dan opname menghasilkan mutasi yang dapat direkonsiliasi.
- Tentukan transaksi draft/approved/posted dan pembatalan melalui reversal.
- Bangun kartu stok bahan baku serta saldo per barang/lokasi/lot sesuai keputusan.

## Tahap 3 — Pembelian, penerimaan dan BC masuk

- Lengkapi MO/PO supplier dan lain-lain, pemeriksaan status pemenuhan, penerimaan parsial, GRN detail serta approval.
- Hubungkan BC masuk ke dokumen dan baris penerimaan tanpa menduplikasi identitas barang.
- Tambahkan export/import dan format cetak hanya setelah fields/format dikonfirmasi.

## Tahap 4 — Produksi garment dan barang jadi

- Lengkapi material request/issue, pelacakan perpindahan dan hasil Cutting, Sewing, Packing/Finishing, reject dan WIP.
- Hubungkan hasil ke planning/MO/buyer/style/SKU; bangun saldo dan kartu stok barang jadi.
- Tambahkan GSN/surat jalan, approval dan BC keluar sesuai proses yang dikonfirmasi.

## Tahap 5 — Subkontrak serta scrap/aset

- Kontrak dan kuota; pengeluaran bahan/WIP; penerimaan hasil; monitoring serta laporan rekonsiliasi.
- Scrap masuk/keluar dan transaksi mesin/peralatan; tentukan apakah sebagai stok, aset tetap, atau pelacakan kuantitas sederhana.

## Tahap 6 — Laporan, histori dan kontrol

- Mutasi per kategori (bahan baku/penolong, WIP, barang jadi, modal/lain, reject/scrap), pemasukan/pengeluaran BC, retur, rencana/status produksi.
- Audit log atas perubahan dan keputusan persetujuan; ekspor Excel menggunakan filter yang sama dengan layar.
- Rekonsiliasi laporan dengan ledger dan dokumen; verifikasi kontrol akses per role.

## Urutan prioritas

1. Konfirmasi aturan dan gap audit.
2. Master dan pola dokumen/akses.
3. Ledger stok dan transaksi gudang.
4. Pengadaan, GRN, approval, BC masuk.
5. Produksi, WIP, barang jadi, GSN/approval, BC keluar.
6. Subkontrak, scrap/aset.
7. Laporan, export, audit dan rekonsiliasi akhir.

## Risiko dependensi

Aturan posting stok dan format BC memengaruhi schema dan transaksi inti. Tetapkan sebelum tahap 2–4. Status project dan tingkat kesesuaian field harus diverifikasi pada implementasi hidup; daftar model/migration saja tidak membuktikan suatu alur sudah selesai.

## Progres implementasi terbaru

- Material Request yang disetujui dan terkait MO dapat dikirim parsial melalui modul Gudang **Kirim ke Produksi (MIS)**. Setiap pengiriman punya nomor dokumen dan jejak di ledger stok/lot; detail dan status saldo mengikuti `docs/03-BUSINESS-FLOW.md` serta `docs/05-FUNCTIONAL-REQUIREMENTS.md`.
- Modul Production **Penerimaan Produksi dari Gudang** mengonfirmasi penerimaan dokumen MIS dan menyimpan penerima/waktu tanpa mem-posting stok kedua kalinya. Penerimaan parsial serta asal data PO Supplier perlu konfirmasi.
- Modul Gudang **Pemakaian Material Non-Produksi** memakai nomor transaksi terkonfigurasi, pemilihan item berbasis DataTable, harga/total, koreksi stok melalui mutasi kompensasi, dan jejak kartu stok.
