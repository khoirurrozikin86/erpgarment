# Analisis Alur Bisnis

## Alur tingkat tinggi yang tergambar

Diagram `image1` membagi proses ke Sales/PPIC, Gudang, dan Production/WIP. Ia menampilkan bahan masuk melalui EXIM, BOM dan PO buyer, penerbitan PO supplier, GRN, transfer ke produksi, proses/subkon/finish good, surat jalan, serta dokumen EXIM masuk/keluar dan saldo bulanan. Diagram adalah sumber proses paling eksplisit. Beberapa panah/penamaan perlu validasi sebelum dijadikan aturan sistem.

## Alur yang didukung oleh halaman

1. **Persiapan data** — pengguna dan akses, importer/exporter, supplier/customer, style, barang dan BOM disiapkan.
2. **Kebutuhan dan pengadaan** — Material Order memuat nomor MO, tanggal MO/tempo, No CBD, PO Buyer, mata uang, destination, Buyer, dan satu atau beberapa baris Finished Goods dengan jumlah, harga satuan, total harga, serta deskripsi; PO supplier dan PO lain-lain dicatat.
3. **Penerimaan** — BC masuk mencatat data kepabeanan/penerimaan yang merujuk dokumen; Catatan Penerimaan Barang/GRN menyimpan pemasok, invoice, PO/MO, item, kuantitas; GRN Approve memiliki status dan pencatat persetujuan.
4. **Stok gudang** — penerimaan dan retur produksi masuk ke gudang. Pengiriman/transfer ke produksi (MIS), retur ke supplier, barang rusak/hilang tercatat sebagai transaksi yang berpengaruh pada stok. Kartu stok menunjukkan saldo/mutasi.
5. **Produksi** — material diminta/dikeluarkan dari gudang; catatan hasil Cutting, Sewing dan Packing memiliki nomor dokumen, tanggal, user, serta kaitan ke MO/planning; hasil akhir tercatat sebagai Barang Jadi. Surat Jalan (GSN) mengirim hasil dan kemudian disetujui.
6. **Subkontrak** — kontrak mengandung subkon, nomor kontrak, nomor skep BC, tanggal berlaku, keterangan dan kuota. Bahan baku/WIP dikeluarkan dan penerimaan subkon serta barang setengah jadi dimonitor. Kuota dan aturan pengurangan/pemulihan **NEEDS CONFIRMATION**.
7. **Barang sisa/mesin** — scrap masuk/keluar dan aset/mesin masuk/keluar dicatat, kemudian disajikan dalam kartu stok/laporan.
8. **Dokumen keluar & laporan** — BC keluar mencatat dokumen pengeluaran, barang, customer, jumlah dan nilai. Laporan pemasukan/pengeluaran, mutasi, WIP, produksi, barang jadi dan retur dapat ditampilkan menurut periode serta diekspor.

## Relasi antarlayar yang terlihat

- Tombol `Detail` dari daftar transaksi membuka atau menampilkan baris dokumen terkait; satu screenshot penerimaan produksi menampilkan tabel daftar dan tabel detail dokumen di bawahnya.
- Approval GRN dan Surat Jalan memuat nomor dokumen, status, nama approver, dan waktu persetujuan; membuktikan keterhubungan dokumen transaksi dengan status approval, tetapi bukan seluruh aturan transisi.
- Kartu stok/laporan merangkum transaksi sebelumnya menurut barang dan periode; angka ditampilkan sebagai saldo, pemasukan, pengeluaran, adjustment, hasil pencacahan, selisih.
- Data produksi menggunakan nomor MO, nomor planning/PO Buyer, style dan kode barang; hubungan foreign-key tepatnya tidak tampak.

## Aturan proses yang belum dapat dipastikan

- Apakah persetujuan wajib untuk semua GRN/GSN, siapa approver, dan apakah transaksi bisa diedit/dibatalkan setelah disetujui: **NEEDS CONFIRMATION**.
- Makna dan versi dokumen BC, persyaratan validasi/format resmi, aturan edit dan rekonsiliasi: **NEEDS CONFIRMATION**.
- Apakah stok diperbarui saat simpan, saat approval, atau dari impor dokumen: **NEEDS CONFIRMATION**.
- Satuan, konversi unit, toleransi penerimaan, partial receipt, over/under delivery, costing, dan pembulatan: **NEEDS CONFIRMATION**.
- Apakah produksi dimodelkan sebagai alur wajib cutting → sewing → packing, kemungkinan rework/reject, dan kapan barang jadi masuk saldo: **NEEDS CONFIRMATION**.
- Aturan wajib/unik PO Buyer, No CBD, Destination, Currency, kalkulasi harga/pajak, validasi rentang tanggal, dan izin mengubah/menghapus detail yang sudah dipakai transaksi: **NEEDS CONFIRMATION**. Form menunjukkan satu baris barang disimpan setiap kali; satu MO dapat memiliki beberapa detail.
- Arti nomor PO Buyer dibanding PO Supplier; arti MO, MIS, GSN, Kaber, serta relasi satu-ke-banyak dokumen: **NEEDS CONFIRMATION**.
- Penghitungan saldo awal/akhir, stock opname, adjustment, barang hilang/rusak, retur, dan periode tutup buku: **NEEDS CONFIRMATION**.

## Contoh alur minimum untuk konfirmasi pemilik proses

Pilih satu dokumen penerimaan dan satu order produksi nyata. Telusuri nomor dokumen sumber → detail barang/kuantitas → approval → perubahan stok → transaksi produksi/retur → laporan. Tetapkan titik posting stok dan pihak yang dapat mengubah/membatalkan dokumen di tiap langkah sebelum implementasi transaksi.
