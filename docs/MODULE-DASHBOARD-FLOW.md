# Dashboard Alur ERP Garment

Permintaan pengguna: dashboard lengkap mengikuti alur contoh E-SIKAT, disesuaikan dengan modul aplikasi saat ini. Layout sidebar/header tetap dipertahankan.

## File perubahan
- app/Http/Controllers/Admin/DashboardController.php
- resources/views/super/dashboard.blade.php
- config/dashboard_flow.php
- lang/id/dashboard_flow.php
- lang/en/dashboard_flow.php

## Hasil
Peta alur berisi PPIC → BC IN/penerimaan → gudang/transfer → produksi sampai barang jadi → cabang subkon → pengiriman/BC OUT. Subkon merupakan cabang opsional, bukan tahap wajib seluruh produksi. Cabang mesin, scrap, retur, dan tautan laporan/audit mengikuti modul yang sudah ada. Semua tautan dan query indikator mengikuti permission pengguna. Peta adalah navigasi, bukan engine posting atau bukti bahwa dokumen BC memicu stok.

Periode awal bulan sampai hari ini, dapat diganti. Ringkasan menghitung MO baru berdasarkan created_at, GSN berdasarkan sent_date, dokumen subkon berdasarkan issue_date, proses cutting/sewing/packing/finishing berdasarkan processed_at, dan tiap jenis BC berdasarkan document_date. Hitungan dokumen tidak sama dengan quantity atau saldo. Dokumen soft-deleted tidak ikut sesuai model asal. Data transaksi memakai perusahaan aktif; tanpa konteks perusahaan query menggunakan company_id 0.

Antrean MR/PR pending dan PO terbuka mengikuti fondasi data bersama aplikasi, di luar filter periode. GSN belum disetujui memakai perusahaan aktif. Angka master menunjukkan jumlah kode fabric/accessory/WIP/finished_good bersama aplikasi, bukan stok. Tidak menjumlahkan saldo lintas satuan. Grafik batang BC merupakan perbandingan jumlah dokumen tiap jenis dalam periode.

MO terbaru dibatasi enam baris dalam periode dan perusahaan aktif. Laporan mutasi, WIP, hasil produksi, retur, audit dibuka melalui modul perhitungan yang sudah ada. Dashboard tidak mengubah persediaan atau aturan approval/costing/BC.

## Asumsi / batas verifikasi
NEEDS CONFIRMATION tetap berlaku untuk aturan rinci proses pada dokumen sumber. Tidak menambahkan rasio produksi, target, costing, omzet, atau saldo bulanan dari inferensi screenshot. Tidak meniru otomatis pemetaan legal jenis BC ke mesin/scrap/subkon pada ilustrasi.

Tidak ada migration/schema baru. Test/build tidak dijalankan sesuai instruksi proyek. Tampilan browser belum diverifikasi.
