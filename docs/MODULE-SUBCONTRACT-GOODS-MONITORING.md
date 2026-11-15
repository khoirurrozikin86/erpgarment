# Monitoring barang subkon

Permintaan pengguna: daftar kontrak dengan tombol Detail Penerimaan/Detail Pengeluaran dan tabel detail style, barang, satuan, Qty Kontrak, Realisasi, Sisa. Menggunakan fondasi query/filter monitoring kontrak; tidak membuat master kontrak, transaksi atau saldo baru.

Menu Subkon → Monitoring Barang Subkon. Baca-saja, permission subcontract-contracts.view; seluruh daftar, seleksi kontrak, dan transaksi dibatasi company aktif. UI Indonesia/Inggris, filter tanggal mulai kontrak, pencarian dan pagination daftar/detail terpisah. Tampilan card dan tabel mengikuti modul subkon lainnya, tanpa mengubah layout.

Realisasi kumulatif sampai tanggal akhir (issue_date <= to_date), tidak dibatasi tanggal awal; tanggal awal hanya memilih kohort kontrak menurut starts_on seperti monitoring kontrak. Hanya dokumen confirmed yang belum dihapus. Detail pengeluaran menggabungkan raw/wip berdasarkan contract_id dan subcontractor_id. Barang dan style berbeda dipisahkan; agregasi berdasarkan product_id/style sehingga satuan tiap produk tidak dicampur.

Detail penerimaan memakai relasi contract_id bila tersimpan. Untuk nomor kontrak manual, pencocokan menggunakan company, subcontractor dan contract_number, hanya jika tepat satu kontrak aktif cocok. Pencocokan ini read-only, tidak membuat/mengubah relasi sumber. Jika ambigu, nomor manual tidak dihitung dan peringatan ditampilkan; relasi ID langsung tetap dapat ditampilkan. Tidak menebak kontrak dari kemiripan nama.

NEEDS CONFIRMATION: model kontrak belum mempunyai target per item/style/satuan. Qty Kontrak dan Sisa ditampilkan —; kuota header tidak ditafsirkan sebagai target untuk semua barang. Tidak menjumlahkan stok keluar bahan dengan hasil masuk sebagai selisih tanpa aturan konversi. Ketepatan pencocokan nomor manual perlu validasi pemilik proses.

Tidak membutuhkan migration. Test/build tidak dijalankan sesuai AGENTS.md.
