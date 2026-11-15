# Laporan retur ke supplier

Implementasi mengikuti screenshot pengguna: No, No Retur, Tanggal, Kode Barang, Nama Barang, Satuan, Jumlah. Filter periode, pencarian nomor retur/kode/nama, pagination, export XLSX seluruh hasil filter. Menu Laporan BC, permission purchase-report.view sesuai laporan pembelian.

Sumber PurchaseReturnItem dengan relasi purchaseReturn dan product.unit yang sudah ada. Satu baris per detail retur, tidak menggabungkan detail hanya karena produk sama. Periode returned_at mulai awal tanggal awal sampai sebelum hari setelah tanggal akhir. Jumlah dari quantity tersimpan, empat desimal. Export memakai query/filter sama, nilai numerik dan teks sebagai string.

Cakupan gabungan mengikuti model dan laporan pembelian: PurchaseReturn belum mempunyai company_id. Tidak menambahkan filter perusahaan lewat relasi yang belum ditetapkan. Retur belum mempunyai status/approval terpisah sehingga tidak menambahkan asumsi approved. Tidak mengubah schema/transaksi/saldo.

NEEDS CONFIRMATION: kebutuhan pemisahan perusahaan dan approval retur jika akan ditambahkan ke proses. Test/build tidak dijalankan sesuai AGENTS.md.
