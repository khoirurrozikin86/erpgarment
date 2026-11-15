# Laporan retur ke gudang

Permintaan pengguna: tabel No, No Reject, Tanggal, Kode Barang, Nama Barang, Satuan, Jumlah, sesuai screenshot; filter periode, pencarian, pagination, export XLSX. Menu Laporan BC, permission inventory-report.view.

Menggunakan ProductionReturnTableQuery yang sudah ada dan detail ProductionReturnItem. Satu baris per detail, company_id aktif, periode return_date, status dokumen approved. Pending, cancelled, dokumen/detail soft deleted tidak disertakan. Quantity empat desimal. Tidak menggabungkan baris produk yang sama. Tidak mengubah schema atau transaksi/saldo.

No Reject mengikuti label screenshot dan mengambil document.number, bukan membuat jenis dokumen reject baru. NEEDS CONFIRMATION: kesetaraan istilah No Reject pada referensi dengan nomor retur produksi dalam sistem. Sumber serta pembatasan status dijelaskan pada halaman.

Export memakai query/filter sama dengan halaman, seluruh hasil, jumlah numerik dan teks sebagai string. Test/build tidak dijalankan sesuai AGENTS.md.
