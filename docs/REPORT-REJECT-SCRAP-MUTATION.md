# Mutasi reject dan scrap/waste

Permintaan pengguna: tabel mutasi sesuai screenshot dengan filter periode, pencarian, pagination, dan export Excel. Menu Laporan BC; permission inventory-report.view. Tidak mengubah schema, saldo, master kategori, maupun transaksi.

Sumber produk mengikuti kartu stok scrap: stock/track_stock berkategori WASTE. Mendukung kode kategori REJECT jika sudah ditetapkan pada data master; implementasi tidak membuat kategori atau menebak barang reject dari nama/kode produk.

Perhitungan dan export memakai query/pemetaan bersama laporan mutasi bahan: saldo awal dari quantity_after transaksi terakhir sebelum periode; masuk dan keluar berdasarkan tanda quantity, stock_adjustment dipisahkan dan dihitung satu kali. Periode stock_movements.created_at, cakupan ledger gabungan aplikasi. Mutasi koreksi/pembatalan tetap tercakup. Tidak menambahkan dokumen penerimaan/pengeluaran scrap lagi di luar ledger karena sudah diposting sebagai mutasi stok.

Pencacahan dan selisih mengikuti nilai tersimpan pada opname posted terakhir dalam periode; tanpa opname kosong/—. Selisih bukan perbandingan hasil hitung dengan saldo akhir periode yang berbeda waktu. Produk nonaktif tetap disertakan untuk histori.

NEEDS CONFIRMATION:
- Hubungan reject produksi ke stok reject dan klasifikasi kategori REJECT. rejected_quantity, waste_quantity, dan material_waste_quantity tahap produksi tidak otomatis ditambahkan sebagai penerimaan stok; kuantitas/satuan dan dokumen tujuan belum dipastikan.
- Definisi Jumlah Barang tetap belum ditetapkan: kosong/— sebagaimana laporan mutasi lainnya.

File export memakai seluruh hasil filter, bukan halaman aktif, dengan angka numerik dan teks diikat sebagai string. Test/build tidak dijalankan sesuai AGENTS.md.
