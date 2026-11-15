# Laporan hasil produksi

Permintaan pengguna: tabel sesuai screenshot, filter periode, pencarian, pagination, export Excel. Menu Laporan BC dan permission inventory-report.view. Tidak mengubah schema atau transaksi.

Pemetaan sementara berdasarkan kolom Dari Sewing dan alur operasional aplikasi:
- Satu baris per production_order_stage_entries tahap packing yang masih aktif, MO perusahaan aktif.
- No Planning/PO Buyer dari MO; No Produksi dari packing_number; tanggal dari processed_at. Periode berdasarkan tanggal proses, bukan tanggal MO.
- Produk/satuan dari outputProduct MO, Dari Sewing = input_quantity per transaksi packing, Hasil Produksi = good_quantity packing. Tidak mengulang total sewing MO pada setiap baris atau menjumlahkan penerimaan finishing lagi.
- Gudang = — karena tidak ada field gudang pada transaksi packing.

NEEDS CONFIRMATION: referensi hanya berisi header tanpa contoh data. Pemetaan Hasil Produksi ke packing, definisi Dari Sewing, sumber No Produksi, dan gudang tujuan masih perlu validasi pemilik proses. Halaman menjelaskan sumber yang digunakan. Laporan ini berbeda dari laporan rencana/status produksi yang memakai penerimaan finishing sebagai hasil barang jadi.

Pencarian: nomor packing, nomor MO/PO Buyer, kode/nama produk. Export memakai query/filter yang sama, seluruh hasil, angka numerik dan teks sebagai string. Test/build tidak dijalankan sesuai AGENTS.md.
