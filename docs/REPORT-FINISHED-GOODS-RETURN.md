# Laporan retur barang jadi

Permintaan pengguna: tabel sesuai screenshot kosong, filter periode/pencarian/pagination/export Excel, menu Laporan BC, permission inventory-report.view.

Belum ditemukan model/route transaksi khusus retur barang jadi. Sumber read-only sementara adalah StockMovement movement_type customer_return dengan quantity positif dan produk track_stock jenis finished_good atau kategori FG/nama finished goods. Jenis movement ini sudah dikenali kartu stok/allocator. Tidak menciptakan dokumen, saldo, atau transaksi retur baru.

Tanggal mengikuti created_at ledger, bukan tanggal dokumen retur yang belum tersedia. Cakupan gabungan mengikuti ledger yang belum memiliki company_id. Pencarian kode/nama produk. Quantity numerik empat desimal. Export memakai query/filter yang sama, seluruh hasil.

No Reject tetap mengikuti label screenshot, nilai — karena belum ada sumber nomor dokumen retur. Tidak memakai nomor surat jalan asli atau ID ledger sebagai nomor retur. Tabel kosong jika tidak ada mutasi terkait; tidak mengisi dari retur produksi bahan atau quantity reject tahap produksi.

NEEDS CONFIRMATION: apakah retur barang jadi pada referensi adalah retur pelanggan atau jenis pengembalian lain, model dokumen sumber dan approval, definisi tanggal/no reject serta cakupan perusahaan. Sumber sementara dijelaskan pada halaman. Test/build tidak dijalankan sesuai AGENTS.md.
