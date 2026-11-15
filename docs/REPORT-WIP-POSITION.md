# Laporan posisi barang dalam proses (WIP)

Implementasi atas permintaan pengguna, mengikuti tabel referensi: kode barang, nama, satuan, jumlah, keterangan; filter periode, pencarian, pagination, export XLSX. Menggunakan permission inventory-report.view, tanpa perubahan schema atau transaksi stok.

## Sumber dan perhitungan

- Sumber: production_order_stage_entries yang belum dihapus, terhubung ke MO perusahaan aktif dan produk output. Tanggal menggunakan processed_at.
- CUT = good_quantity cutting - input_quantity sewing.
- SEW = good_quantity sewing - input_quantity packing.
- PKG = good_quantity packing - input_quantity finishing.
- Rumus mengikuti aturan available quantity dalam SaveSewingAction, SavePackingAction, SaveFinishedGoodsAction serta query pemilihan MO saat ini. Tidak menggunakan urutan tahap legacy RecordProductionStageAction yang berbeda.
- Jumlah adalah posisi kumulatif sebelum awal hari sesudah tanggal akhir, bukan selisih mutasi dalam periode. Saldo sebelum tanggal awal tetap dihitung. Baris dengan saldo bukan nol atau aktivitas dalam periode ditampilkan, termasuk baris saldo nol yang bergerak dalam periode.
- Kode /CUT, /SEW, /PKG adalah label posisi laporan dari kode produk output, bukan master produk baru.
- Kuantitas negatif tetap terlihat untuk peninjauan koreksi/urutan transaksi, tidak dijadikan nol secara diam-diam.
- Export menggunakan query dan filter yang sama, seluruh halaman, angka tetap numerik sampai empat desimal. Teks diikat sebagai string.

## Batas dan keputusan terbuka

- NEEDS CONFIRMATION: apakah posisi legal WIP menggunakan definisi sisa antar tahap yang sama dengan aplikasi, dan apakah produk WIP master terpisah perlu direkonsiliasi dengan posisi ini.
- NEEDS CONFIRMATION: perlakuan data lama dari urutan cutting/sewing/finishing/packing pada RecordProductionStageAction; laporan ini mengikuti alur operasional cutting/sewing/packing/finishing saat ini.
- Koreksi dan soft delete berlaku pada data aktif saat laporan diambil; ini bukan rekonstruksi versi historis sebelum koreksi.
- Tidak ada baris/tahap atau kuantitas yang dibuat dari screenshot. Tidak menghubungkan WIP OUT mandiri ke MO tanpa relasi tersimpan.
- Test/build tidak dijalankan sesuai AGENTS.md.
