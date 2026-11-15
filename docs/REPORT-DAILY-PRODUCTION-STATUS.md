# Status produksi harian

Permintaan pengguna: laporan sesuai screenshot, filter periode/pencarian/pagination/export Excel. Menu Laporan BC; permission inventory-report.view. Tanpa perubahan schema/transaksi.

Satu baris per MO perusahaan aktif, periode berdasarkan po_date seperti laporan rencana produksi. Buyer dari buyer/customer MO; nomor planning dan PO Buyer dari MO; style/kode/nama dari outputProduct. Status MO tidak dibatasi tanpa aturan terkonfirmasi.

Today memakai tanggal akhir terpilih, bukan tanggal server; Total menghitung kumulatif sampai akhir tanggal tersebut. Cutting dan sewing memakai SUM(good_quantity), finishing memakai SUM(good_quantity) tahap finishing/penerimaan barang jadi. Stage soft deleted tidak ikut. Hasil packing tidak ditambahkan lagi ke finishing.

Selisih memakai formula yang tertulis di header screenshot: cutting total - order (4=3-1), sewing total - cutting total (7=6-3), finishing total - sewing total (10=9-6). Angka negatif tidak diubah menjadi nol dan tidak ditafsirkan otomatis sebagai status selesai/gagal.

Delivery menghitung quantity detail surat jalan yang masih aktif dan disetujui, terhubung ke MO, produk output yang sama, serta company aktif. Tanggal sent_date untuk Today/Total. Tidak menyatakan semua pengiriman sebagai ekspor. Approval dinilai menurut keadaan dokumen saat laporan diambil; bukan rekonstruksi histori approval.

NEEDS CONFIRMATION: definisi Today/Total dari proses asli, tanggal MO untuk cakupan periode, definisi output tahap, dan rincian kolom Delivery di sisi kanan screenshot yang terpotong. Implementasi hanya menyediakan Today/Total Delivery yang didukung data, tanpa menambahkan formula kolom terpotong. Pemetaan sementara dijelaskan pada halaman.

Export memakai query/filter dan pemetaan nilai yang sama dengan layar, seluruh hasil, angka numerik dan teks sebagai string. Test/build tidak dijalankan sesuai AGENTS.md.
