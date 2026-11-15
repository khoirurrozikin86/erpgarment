# Pengeluaran bahan baku subkon

Implementasi diminta pengguna sesuai dua screenshot: daftar dengan periode/pencarian dan export Excel serta form surat jalan multi-item, konsisten dengan card/tabel/form modul subkon yang ada. UI Indonesia/Inggris, menu grup Subkon.

## Data dan akses

- Header: company aktif, nomor surat jalan otomatis, tanggal pengeluaran, kontrak, penerima subkon, kendaraan, pengguna pembuat dan timestamps. Detail: produk, qty empat desimal, style, keterangan. FK restrict ke master yang dipakai, unique company+number, indeks company+tanggal dan product+document.
- Penerima diambil dari kontrak yang dipilih dan divalidasi kecocokannya di server. Kontrak dan penerima harus milik company aktif, tidak dihapus, penerima aktif.
- Produk pilihan: stock/track_stock aktif, garment_item_type fabric atau kategori RAW/FAB. Pencarian barang dibatasi 50 hasil agar tidak memuat seluruh master; bukan mengklasifikasikan bahan dari nama produk.
- Qty positif maksimal empat desimal; maksimal 200 baris per dokumen. Detail tidak digabung hanya karena produknya sama.
- Tambah/ubah/hapus draft dalam transaksi DB dengan audit log; dokumen confirmed dikunci di server. Pengubahan mengunci dokumen, mengganti detail secara atomik, nomor tetap. Hapus soft delete header dan mempertahankan detail untuk jejak audit; query daftar/export mengecualikan header terhapus.
- Permission baru subcontract-material-issues.view/create/update/delete. Admin/super_admin mengikuti pola permission subkon yang ada; role lain dapat diatur pada pengelolaan permission. Seluruh endpoint dokumen membatasi company aktif. Export memakai permission view dan seluruh hasil pencarian/periode, bukan halaman aktif, angka numerik dan teks sebagai string.
- Penomoran menggunakan DocumentNumberService, konfigurasi awal TIMW/{NUMBER}/SBBK/{MM}/{YY} dari pola referensi; nomor final dibuat saat simpan, preview tidak memesan nomor. Konfigurasi dapat diubah pada pengaturan penomoran. Company baru serta seeder memperoleh jenis nomor ini.

## NEEDS CONFIRMATION

- Dampak stok, costing, lot, kuota kontrak, approval, serta pembatalan/edit setelah pengiriman belum disepakati pada docs/05-FUNCTIONAL-REQUIREMENTS.md. Aturan stok diperbarui melalui persetujuan pengguna pada bagian Konfirmasi Gudang di bawah. Kuota tetap belum dikurangi otomatis. Pemberitahuan eksplisit ada di daftar/form; jangan memperlakukan dokumen ini sebagai mutasi persediaan yang sudah diposting.
- Definisi batas kontrak, tanggal berlaku kontrak, apakah style harus dari MO, dan pembatasan produk/kuantitas rinci masih perlu konfirmasi. Tidak menebak kuota kontrak sebagai batas qty lintas satuan.
- Tombol Ambil Kontrak diwujudkan sebagai pemilihan kontrak/penerima; tidak menyalin item dari kontrak karena model kontrak saat ini belum memiliki detail item.

Test/build tidak dijalankan sesuai AGENTS.md. Migration baru hanya mencakup tabel/permission/penomoran modul ini.

## Konfirmasi Gudang — disetujui pengguna

Pengguna menyetujui alur Draft → Konfirmasi Pengeluaran Gudang → stok berkurang. Simpan membuat draft; dokumen lama tetap draft dan tidak diposting secara retroaktif. Permission konfirmasi terpisah `subcontract-material-issues.confirm`, diberikan ke admin/super_admin sesuai pola yang ada; role gudang dapat diberi permission melalui pengaturan peran.

Konfirmasi mengunci header dan detail, lalu produk/saldo berurutan menurut ID. Qty digabung per produk untuk memeriksa kecukupan seluruh baris; pencatatan mutasi tetap satu per item. Saldo dan lot FIFO dikurangi dalam transaksi database yang sama melalui allocator persediaan yang sudah ada. Kekurangan saldo/lot atau kegagalan satu item membatalkan seluruh konfirmasi. Harga pokok mutasi diambil dari alokasi lot aktual; tidak menambahkan jurnal otomatis tanpa aturan akun subkon.

Relasi dokumen/item disimpan pada StockMovement, dengan unique item untuk mencegah pemotongan ganda dan FK restrict untuk menjaga dokumen sumber. Status, confirmed_by, confirmed_at serta audit log dicatat pada transaksi yang sama. Pengulangan konfirmasi ditolak. Edit/hapus setelah confirmed ditolak pada action dan UI; belum ada pembatalan/koreksi transaksi confirmed tanpa persetujuan aturan reversalnya.

Kartu stok dan export kartu stok menampilkan nomor surat jalan dan jenis pengeluaran subkon; laporan mutasi berbasis ledger otomatis mencakup kuantitas keluar. Waktu mutasi mengikuti waktu konfirmasi, tanggal surat jalan tetap metadata dokumen. Export subkon menambahkan status/pengguna/waktu konfirmasi. Kuota kontrak tetap NEEDS CONFIRMATION dan tidak diubah.

Migration khusus konfirmasi berhasil dijalankan. Test/build tidak dijalankan sesuai AGENTS.md.
