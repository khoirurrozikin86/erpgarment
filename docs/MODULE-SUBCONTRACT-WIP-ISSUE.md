# Pengeluaran barang setengah jadi subkon

Permintaan pengguna: modul pengeluaran barang setengah jadi dengan tampilan konsisten modul subkon dan screenshot daftar/form surat jalan. Mengikuti persetujuan alur sebelumnya: Draft → Konfirmasi Pengeluaran Gudang → pemotongan stok. UI Indonesia/Inggris, filter tanggal, pencarian/pagination, surat jalan multi-item, kontrak/penerima, kendaraan, style, keterangan, export Excel.

## Fondasi bersama

Tidak membuat model stok/dokumen duplikat. Memakai tabel dan model surat jalan pengeluaran subkon yang sudah ada dengan discriminator issue_kind raw/wip. Data lama tetap raw. Query, penomoran, route, permission dan endpoint dokumen membedakan jenis; akses lintas jenis/company ditolak. Controller WIP memperluas controller dasar; form/daftar/export serta action simpan/konfirmasi dipakai bersama.

Barang WIP yang boleh dipilih: produk stock, track_stock aktif, garment_item_type work_in_process atau kategori WIP/nama barang setengah jadi, mengikuti klasifikasi modul subkon setengah jadi yang sudah ada. Tidak menafsirkan barang jadi berdasarkan nama atau menyalin nilai screenshot. Tabel/form menampilkan data aktual.

Penomoran jenis subcontract_wip_issue, konfigurasi awal TIMW/{NUMBER}/SBSJK/{MM}/{YY}, mengikuti pola referensi dan dapat dikonfigurasi. Mendukung company saat ini, company baru, dan seeder. Permission subcontract-wip-issues.view/create/update/delete/confirm, default admin/super_admin sesuai pola modul sebelumnya.

Draft tidak memengaruhi saldo. Konfirmasi memvalidasi klasifikasi produk, stok agregat seluruh detail, dan kecukupan lot melalui allocator FIFO yang sama dengan modul bahan baku. Semua pengurangan, alokasi, status, confirmed_by/at, dan audit disimpan dalam satu transaksi. Pengulangan konfirmasi ditolak; edit/hapus dokumen confirmed terkunci. Mutasi menyimpan referensi header/item dan kartu stok memakai label sesuai jenis. Export mencakup seluruh hasil filter dan status/konfirmator/waktu konfirmasi.

## NEEDS CONFIRMATION

- Kuota kontrak, aturan tanggal kontrak, jurnal subkon, dan reversal dokumen confirmed tetap memerlukan aturan terpisah. Kuota tidak berubah otomatis dan tidak ditafsirkan sebagai batas lintas satuan.
- Sumber stok WIP aktual diperlukan untuk konfirmasi. Pencatatan hasil setengah jadi subkon lama masih bersifat administrasi dan tidak otomatis membuat saldo/lot. Jangan membuat stok semu atau mengonversi hasil antar tahap dari screenshot; jika saldo/lot belum tersedia konfirmasi akan ditolak.
- Migration khusus jenis dokumen/penomoran/permission berhasil dijalankan. Test/build tidak dijalankan sesuai AGENTS.md.
