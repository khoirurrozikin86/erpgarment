# Laporan rencana produksi

Implementasi atas permintaan pengguna: tabel mengikuti screenshot, filter periode, pencarian nomor MO/kode/nama barang, pagination, export XLSX seluruh hasil. Menu Laporan BC dan permission inventory-report.view. Tanpa perubahan model, schema, atau transaksi.

Sumber: MO perusahaan aktif. Nomor dokumen = number, tanggal = po_date, jumlah order = planned_quantity, barang = outputProduct. Seluruh status MO tercakup karena tidak ada aturan status khusus yang dikonfirmasi. Satu baris per MO; nomor allowance/RPL tidak ditafsirkan atau digabungkan.

Periode membatasi po_date MO. Angka realisasi bersifat kumulatif sampai akhir tanggal to_date (processed_at < hari berikutnya), bukan hanya aktivitas di dalam periode. Stage soft deleted tidak ikut.

Keputusan implementasi sementara:
- Jumlah Reject = penjumlahan rejected_quantity tahap produksi yang masih aktif.
- Jumlah Prod = good_quantity tahap finishing/penerimaan barang jadi; tidak menambahkan output packing lagi untuk menghindari penghitungan hasil dua kali.

NEEDS CONFIRMATION: definisi bisnis reject lintas tahap, sumber tanggal rencana selain po_date, status MO yang harus ditampilkan, serta definisi realisasi pada referensi. Pemetaan di atas ditampilkan pada catatan halaman.

Jumlah Export: —. NEEDS CONFIRMATION: klasifikasi ekspor/lokal dan relasi dokumen BC keluar; pengiriman disetujui saja belum cukup membuktikan ekspor. Jangan memakai semua surat jalan sebagai jumlah ekspor.

Export memakai query dan filter yang sama, angka tetap numerik, kode/nomor sebagai string. Test/build tidak dijalankan sesuai AGENTS.md.
