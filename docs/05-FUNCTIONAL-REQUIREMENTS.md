# Kebutuhan Fungsional

Label **Terlihat** berasal langsung dari menu/form/tabel pada screenshot; **Inferensi** merupakan kebutuhan minimum untuk menghubungkan layar; **NEEDS CONFIRMATION** menandai aturan yang belum dipastikan. Dokumen ini tidak memperluas scope di luar referensi.

## A. Navigasi dan pola umum

- **Terlihat** — sediakan navigasi untuk Pengaturan, PPIC, BC Masuk, Gudang, Produksi, Bahan Sisa & Mesin, Subkon, BC Keluar, Laporan BC.
- **Terlihat** — halaman daftar menampilkan tabel berpaginasi, pengurutan, pencarian, pilihan jumlah record; halaman tertentu menyediakan filter tanggal/periode dan `Tampilkan Data`.
- **Terlihat** — aksi yang tampak meliputi tambah, detail, edit, hapus, cetak, approval/cancel dan export Excel sesuai halaman.
- **NEEDS CONFIRMATION** — aturan hak akses per aksi, konfirmasi hapus, responsive behavior, file export, format tanggal/angka dan lokalisasi final.

## B. Master dan akses

- **Terlihat** — kelola pengguna dengan ID pengguna, sandi, nama, telepon, jabatan, departemen dan atasan.
- **Terlihat** — kelola importer, exporter, supplier, customer, style, barang dan BOM; data barang menampilkan kategori, kode, nama, satuan, style, color, size, status ketersediaan.
- **Diimplementasikan (2026-11-13)** — master Importir: daftar DataTable, tambah/ubah/hapus dengan hak akses, field NPWP/nama/alamat/APIP/status, dan audit log. Validasi NPWP/APIP serta hubungan dokumen BC belum ditetapkan.
- **Penyesuaian UI** — form dan tabel Master Barang menampilkan field referensi kategori, kode, nama, satuan, style, color, size, dan status. Field internal yang masih dipakai proses stok, BOM, produksi, dan purchasing tetap disimpan; field legacy yang tidak dipakai dihapus lewat migration `2026_11_13_000011_remove_unused_product_fields`.
- **Terlihat** — BOM menyediakan daftar style/barang jadi dan aksi Detail/Input.
- **NEEDS CONFIRMATION** — aturan kode otomatis, field wajib, penghapusan master yang telah dipakai, peran/department, variasi item, import data dan versi BOM.

## C. Pengadaan dan penerimaan

- **Terlihat** — Form MO berisi No MO, tanggal, Due Date, No CBD, PO Buyer, Currency, Destination, Buyer; detail Finished Good berisi Item Code/Search, Item Name, Unit, Style, Qty, Price Per Unit, Total Price, Description. Detail tabel memiliki aksi edit/hapus.
- **Diimplementasikan (2026-11-13)** — CRUD MO mengikuti tabel image22 dan form lanjutan. Item dipilih lewat DataTable dari kategori Finished Goods; total harga dihitung dari Qty × Price Per Unit. Setiap baris menyimpan satu item dan baris bisa memakai nomor MO yang sama. BOM aktif menghasilkan snapshot kebutuhan Material Request.
- **Diimplementasikan (2026-11-13)** — Daftar Material Request menampilkan PO Buyer, produk hasil, dan kuantitas produksi dari MO yang tertaut (atau snapshot produk/kuantitas BOM bila dibuat tanpa MO). Aksi detail, issue, PDF, approval, dan reject ditampilkan sebagai tombol ringkas di setiap baris.
- **NEEDS CONFIRMATION** — kewajiban/keunikan No PO Buyer dan No CBD, daftar mata uang, kebutuhan Destination, serta aturan harga/pajak dan validasi tanggal.
- **Terlihat** — tampilkan PO supplier menurut rentang tanggal, mitra, nomor dokumen dan item; daftar PO supplier menyediakan detail, cetak, edit/hapus.
- **Terlihat** — `Cek Pesanan Pembelian` membedakan status Belum Selesai/Selesai dan menyediakan detail.
- **Terlihat** — catat penerimaan/GRN dan baris barang; layar menunjukkan supplier, invoice, MO/PO dan nomor/tanggal GRN.
- **Terlihat** — GRN approval menampilkan status, approver dan timestamp, dengan aksi Detail/Cancel.
- **NEEDS CONFIRMATION** — definisi status selesai (jumlah dipesan vs diterima), penerimaan parsial, toleransi, aturan approval, posting stok, nomor dokumen dan cetakan.

## D. Gudang dan persediaan

- **Terlihat** — catat retur produksi ke gudang, transfer/kirim ke produksi (MIS), retur supplier, barang rusak, barang hilang, kartu stok bahan baku.
- **Terlihat** — catatan pengeluaran/penerimaan memiliki tanggal, nomor dokumen, barang, satuan, jumlah dan kadang keterangan/nilai.
- **Terlihat** — stock opname memiliki filter bulan/tahun dan kolom kategori, kode/nama barang, satuan, tanggal opname dan jumlah hasil.
- **Inferensi** — tampilkan saldo dan riwayat mutasi berdasarkan barang/periode.
- **NEEDS CONFIRMATION** — saldo negatif, valuasi, gudang/lokasi, lot, posting/cancel, otorisasi adjustment, jadwal stock opname dan rekonsiliasi selisih.

## E. Produksi dan pengiriman

- **Terlihat** — tampilkan order bahan baku/penolong, penerimaan bahan dari gudang, pemakaian nonproduksi, retur ke gudang, hasil cutting/sewing/packing, barang jadi, surat jalan, approval surat jalan, WIP dan kartu stok barang jadi.
- **Terlihat** — laporan rencana produksi/status harian mengaitkan buyer/dokumen, style/kode barang, jumlah order, jumlah reject dan jumlah per tahap (cutting/sewing/finishing/delivery).
- **Inferensi** — setiap tahap perlu merujuk order atau planning agar laporan produksi dapat menggabungkan jumlah tahap.
- **NEEDS CONFIRMATION** — aturan kuantitas baik/reject, unit hasil antar tahap, rework, WIP posting, order closing, pengiriman parsial dan apakah `packing` sama dengan `finish good`.

## F. Subkontrak, scrap, aset

- **Terlihat** — catat kontrak subkon berisi subkon, nomor kontrak/BC, keterangan, periode dan kuota; tersedia transaksi pengeluaran bahan/WIP dan penerimaan serta monitoring.
- **Terlihat** — catat scrap masuk/keluar dan mesin/peralatan masuk/keluar Kaber, termasuk kartu stok.
- **NEEDS CONFIRMATION** — arti Kaber, asal dan tujuan scrap/aset, satuan kuota, pengurangan kuota, pelaporan kehilangan/kerusakan, persetujuan dan kebutuhan nilai aset.

## G. Bea Cukai dan laporan

- **Terlihat** — sediakan daftar dokumen BC masuk 2.3/2.6.2/2.7/4.0/2.0 dan BC keluar 3.0/2.6.1/2.7/4.1/2.5. Tabel menampilkan identitas dokumen, mitra, barang, jumlah/satuan, nilai/mata uang serta HS Code pada beberapa tipe.
- **Terlihat** — laporan pemasukan/pengeluaran dapat difilter periode dan jenis, ekspor ke Excel; laporan mutasi menampilkan saldo awal, masuk, keluar, adjustment, saldo akhir, pencacahan dan selisih.
- **Terlihat** — laporan mencakup WIP, barang jadi, barang modal/lain, reject/scrap/waste, rencana dan status produksi harian, retur supplier/gudang/barang jadi, histori user.
- **NEEDS CONFIRMATION** — format legal/export resmi, field wajib tiap jenis BC, sumber No Aju/HS, rekonsiliasi dengan sistem eksternal, pembatasan edit, retensi dan rincian audit.

## Kriteria penerimaan konseptual

- Daftar dan detail untuk setiap modul dapat ditelusuri ke sumber datanya.
- Perubahan stok dapat direkonsiliasi terhadap dokumen sumber dan jejak pengguna.
- Approval menampilkan status dan identitas/waktu persetujuan.
- Laporan periode dapat ditelusuri ke baris transaksi dan hasil ekspor konsisten dengan filter yang ditampilkan.
- Kriteria rinci harus ditulis setelah jawaban untuk semua `NEEDS CONFIRMATION` yang memengaruhi data, saldo, dokumen pabean atau approval.


## Keputusan scope lanjutan — 2026-11-13

- Price Lists, Sales Quotation, Sales Order beserta alur turunan Delivery, Sales Invoice, Customer Return, dan Piutang dihapus sesuai arahan pengguna. Database migration akan menghapus tabel serta data dokumen tersebut.
- Kas/bank, mutasi stok, dan jurnal tetap dipertahankan sebagai ledger historis. Referensi ke dokumen sumber yang dihapus dilepas; saldo tidak dihitung ulang.
- POS, Ticket QR Code, Outlet, assignment outlet ke pengguna, dan riwayat scan kini juga dihapus atas instruksi lanjutan. Baris ledger kas/stok dan jurnal tetap dipertahankan; relasi sumber POS dilepas.

- **Diimplementasikan (2026-11-13)** — master Exportir dengan DataTable, modal tambah/ubah, hapus, validasi nama wajib, permission CRUD, dan audit log. Field sementara: NPWP, nama, alamat, status; APIP tidak ditampilkan. NPWP/alamat/identitas khusus Exportir dan relasi BC keluar masih **NEEDS CONFIRMATION**.


- **Keputusan scope lanjutan (2026-11-13)** — POS, sesi kasir, Ticket QR Code, Outlet, assignment user-outlet, dan scan record dihapus beserta tabel/data operasionalnya. Tabel kas/bank, mutasi stok, dan jurnal tetap ada; kolom relasi POS dilepas agar histori saldo tidak berubah.


## Master Supplier — penyesuaian referensi

- CRUD, daftar, validasi, impor, ekspor, dan template memakai delapan field: kode, nama, alamat, contact person, telepon, fax, email, negara.
- `is_active` tetap menjadi status internal untuk kebutuhan alur pembelian; edit mempertahankan status tersebut tanpa menampilkan kontrol status di form. Supplier baru aktif secara default.
- Field supplier lain yang sebelumnya ada di aplikasi dihapus karena tidak tampak pada referensi.


## Master Customer — penyesuaian referensi

- CRUD, daftar, impor, ekspor, dan template memakai delapan field: kode, nama, alamat, kontak person, telepon, fax, email, dan negara sesuai image15–16.
- `is_active` tetap menjadi status internal dan tidak ditampilkan; Customer baru aktif secara default.
- Field lama yang tidak terlihat pada referensi dihapus dari model data dan UI.


## Master Style

- CRUD Style menyediakan field wajib `Kode Style` dan `Nama Style` sesuai image17; permission `styles.view/create/update/delete` mengatur akses.
- Style muncul di grup sidebar PPIC. Style yang dipakai oleh barang tidak dapat dihapus atau diganti kodenya.
- Belum ada relasi foreign key ke barang: data `style_code` pada barang existing dipertahankan sebagai teks untuk menghindari migrasi data di luar permintaan.


## Master Item — export dan import

- Export, template import, dan importer menggunakan kolom: Kategori, Kode Barang, Nama Barang, Satuan, Kode Style, Warna, Ukuran, Available.
- Kategori dan satuan pada file dicocokkan ke master berdasarkan nama (tidak peka huruf besar/kecil); nilai yang tidak ditemukan ditolak. Kode barang wajib unik. Field internal di luar referensi diberi default aman saat import dan pencatatan memakai service/audit produk.
- Style dicari dari master Style dan dipilih dari hasil pencarian database. Field Available terpisah dari status aktif sistem; daftar menampilkan nilai Masih Ada/Tidak Ada. Modal Item diperkecil menjadi ukuran Bootstrap `modal-lg` dan tetap dapat di-scroll pada layar kecil.
