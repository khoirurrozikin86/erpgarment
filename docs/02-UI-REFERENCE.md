# Referensi UI dari Screenshot

## Master Customer — referensi image15 dan image16

- Daftar: `No`, `Kode`, `Nama`, `Alamat`, `Kontak Person`, `Telepon`, `Fax`, `Email`, `Negara`, `Aksi`; tersedia `Export Excel` dan `Tambah Data`.
- Form: `Kode Customer`, `Nama Customer`, `Alamat`, `Kontak Person`, `Telepon`, `Fax`, `Email`, dan pilihan `Negara`; tersedia tombol `Simpan` dan `Impor Data`.
- Field lain seperti jenis, NPWP, website, termin pembayaran, batas kredit, kota/provinsi, dan catatan tidak tampak pada referensi Customer.

## Master Supplier — referensi image13 dan image14

- Daftar: `No`, `Kode`, `Nama`, `Alamat`, `Contact Person`, `Telepon`, `Email`, `Fax`, `Negara`, `Aksi`; tersedia `Export Excel` dan `Tambah Data`.
- Form: `Kode Supplier`, `Nama Supplier`, `Alamat`, `Kontak Person`, `Telepon`, `Fax`, `Email`, dan `Negara` (pilihan negara), dengan tombol `Simpan` dan `Impor Data`.
- Field lain seperti jenis, NPWP, termin pembayaran, website, rincian bank, kota/provinsi, dan catatan tidak tampak pada referensi Supplier.

## Master Style — referensi image17

- Halaman `Pengaturan Style` menampilkan field wajib `Kode Style` dan `Nama Style`, serta tombol `Simpan`. Implementasi modal CRUD mengikuti pola interaksi master project setelah arahan pengguna.
- Tabel menampilkan `No`, `Aksi`, `Kode Style`, dan `Nama Style`, dengan aksi edit/hapus dan pencarian/paginasi.
- Referensi tidak menunjukkan deskripsi, status, atau atribut style lain; field tersebut tidak ditambahkan.

## Pola tata letak

- Aplikasi web desktop dengan navbar horizontal abu-abu gelap, nama perusahaan di kiri, menu dropdown; pilihan bahasa/user tampak di kanan pada sebagian layar.
- Area kerja berlatar putih. Tiap halaman umumnya diawali panel judul biru dengan nama halaman dan tombol administrasi/tambah/ekspor di kanan.
- Font monospace digunakan luas. Tabel memiliki header abu-abu, garis kisi tipis, sorting, input `Search`, pilihan jumlah record, dan pagination.
- Warna aksi yang tampak: biru untuk tambah/detail/edit/tampilkan, merah untuk hapus/cancel, abu-abu untuk cetak, hijau untuk ekspor atau status disetujui. Fungsi ikon perlu label aksesibel.

## Material Order (MO) — image22

- Filter tanggal PO dari–sampai dengan tombol `Tampilkan Data`; tombol `Administrasi Data` membuka pengelolaan transaksi.
- Tabel daftar awal: No, Aksi (edit/hapus), PO Buyer, No MO, Tgl PO, Tanggal Tempo, Buyer, Style, Kode Barang, Nama Barang, Satuan, Jumlah.
- Form pada screenshot lanjutan: No MO dan tanggal MO, Due Date, No CBD, No PO Buyer, Currency, Destination, Buyer. Bagian Detail Finished Good memuat Item Code dengan tombol Search, Item Name, Unit, Style, Qty, Price Per Unit, Total Price, Description, dan tombol Save.
- Tabel detail barang jadi: No, Action, Product Code, Item Name, Unit, Qty, Price Per Unit, Total Price, Description. No MO tampak sebagai input manual; mata uang contoh adalah USD.
- Implementasi memilih Item kategori Finished Goods lewat DataTable; kode/nama/unit/Style dibaca dari master. Total harga dihitung dari Qty × Price Per Unit. Satu baris detail disimpan per item; beberapa baris dapat menggunakan nomor MO sama.
- Wajib/tidaknya PO Buyer, No CBD, dan Destination serta pilihan Currency yang diizinkan **NEEDS CONFIRMATION**.

## Komponen menurut pola layar

| Pola | Yang tampak | Referensi |
|---|---|---|
| Daftar master | Tombol Tambah Data dan kadang Export Excel; tabel kolom master; aksi edit/hapus (hak akses tidak dapat disimpulkan) | Pengguna `image3`, supplier `image13`, style `image17`, barang `image18` |
| Form master | Field berlabel dan tombol Simpan; contoh pengguna memiliki ID, sandi, nama, telepon, jabatan, departemen, atasan | `image4`; style `image17` |
| Daftar transaksi | Filter rentang tanggal dan `Tampilkan Data`; tabel nomor dokumen, tanggal, mitra, detail, status, aksi edit/hapus/cetak; beberapa menyediakan ekspor Excel | MO `image22`, PO supplier `image25`, GRN `image52`, GSN `image93` |
| Approval | Kolom action (Cancel/Detail), Approved, dokumen, approver dan timestamp; status Yes/Belum selesai | GRN `image57`, approval surat jalan `image97` |
| Dokumen BC | Filter tanggal dokumen; tabel lebar berisi No Aju, No/Tgl dokumen, referensi transaksi penerimaan/pengiriman, supplier/customer, kode/nama barang, kuantitas, satuan, harga, mata uang dan HS code; administrasi data serta ekspor | BC masuk `image39`–`image48`, BC keluar `image137`–`image151` |
| Mutasi/kartu stok | Pilih/tampilkan barang atau periode, tabel agregat saldo awal, pemasukan, pengeluaran, adjustment, saldo akhir dan pencacahan/selisih; ekspor Excel | Kartu stok bahan baku `image70`, laporan mutasi `image168`–`image179` |
| Kartu Stok Barang Jadi | Tabel pilih barang kategori Finished Goods dengan kode/nama/satuan dan tombol Tampil; detail memuat tanggal, nomor mutasi, uraian, jumlah, tanda +/- (S untuk saldo fisik), saldo berjalan, pengguna; ekspor Excel pada daftar dan detail | Screenshot Kartu Stock Barang Jadi yang diberikan pemilik |
| Produksi | Daftar bernomor MO/planning, tanggal, user, dokumen dan jumlah; subhalaman Detail; beberapa menu produksi menunjukkan record hasil tiap tahap | Hasil cutting `image83`, hasil packing `image89`, barang setengah jadi `image127` |
| Monitoring | Filter tanggal; kontrak, subkon, nomor kontrak/BC, keterangan, mulai/berakhir, kuota; beberapa tabel detail keluar/masuk | Kontrak `image125`/`image132`, monitoring `image133`–`image135` |
| Laporan | Filter periode/tanggal, kadang pilihan kategori atau jenis BC; Tampilkan Data dan Export Excel; tabel baca-saja dengan kolom agregat atau lintas tahap | Laporan pemasukan `image153`, WIP `image171`, status harian `image185` |

## Catatan konsistensi

- Label filter bervariasi: `Tanggal`, `Tanggal Doc`, `Tanggal Order`, `Periode`; format tanggal DD-MM-YYYY atau YYYY-MM-DD. Standar dan locale perlu dikonfirmasi.
- Beberapa screenshot menampilkan tabel kosong, record tidak cocok, atau pesan `Anda tidak berhak mengakses data / modul belum lengkap`. Itu adalah keadaan screenshot; bukan bukti fitur sengaja kosong atau aturan hak akses final.
- Banyak tabel lebar mempunyai horizontal scrollbar dan kolom bertingkat. Perilaku responsif, urutan/visibilitas kolom, serta format cetak belum jelas.
- Screenshot daftar umumnya tidak menunjukkan validasi form, dialog konfirmasi hapus, error server, loading state, filter selain tanggal, maupun perilaku mobile: **NEEDS CONFIRMATION**.
# Return to Warehouse / Receipt from Production

- Return list: date range + Show Data + Export Excel; columns match screenshot (return no/date, MO, style, supplier PO, item, unit, qty, description); Add opens compact modal; item is chosen in searchable DataTable; actions edit/delete for pending records.
- Warehouse receipt: date range + DataTable with approval state and actions; detail shows returned item lines; pending rows can be approved, approved rows can be cancelled. Excel export follows the selected period.
- Item picker is a server-side DataTable. MO and supplier PO remain optional as allowed by the reference. Supplier PO is recorded as a reference string because no canonical PO relationship is present in the existing return flow.

## Order Support Material

Form order berisi nomor dan tanggal, pemilih item, nama/satuan readonly, qty, No. PO Other, dan style; rincian dapat diedit/dihapus. Daftar memiliki filter rentang tanggal dan export Excel. Pemilih item menggunakan DataTable server-side.

## Cutting

Tambah/edit menggunakan modal. Nomor dan tanggal cutting, MO (yang mengisi produk/style, satuan, dan PO Buyer), qty cutting, qty kirim ke sewing, reject, pilihan bahan dari material MO, dan jumlah waste. Daftar menampilkan nomor/user/MO dan rincian cutting; tersedia filter tanggal, DataTable, dan export Excel.

## Sewing

Daftar menggunakan filter tanggal, Show Data, export Excel, dan DataTable ringkas dengan tombol Detail untuk membuka rincian lengkap. Form tambah/edit memakai modal: nomor/tanggal, pilih MO (mengisi produk/style/PO Buyer dan sisa hasil Cutting), qty sewing, reject, kirim ke Packing, dan bahan/waste dari material MO.

## Hasil Packing — implementasi

- Daftar memakai filter tanggal, export Excel, DataTable ringkas (No Packing, tanggal, No Planning), dan tombol Detail untuk membuka rincian lengkap.
- Tambah/edit menggunakan modal. MO dan material dipilih melalui modal berisi DataTable; nomor berasal dari Document Numbering.

## Packing / Barang Jadi — referensi terbaru

- Daftar berfilter tanggal dan dapat diekspor; kolom meliputi No Barang Jadi, tanggal, style, item code/name, terima dari Packing, hasil barang jadi, No MO, dan PO Buyer. Aksi edit/hapus berupa ikon kecil.
- Form tambah/edit memakai modal; No Barang Jadi/tanggal, produk/style/unit dari MO, qty hasil Barang Jadi, dan terima dari Packing. Pilihan MO disajikan dalam DataTable.

## Pengeluaran WIP (implementasi)
- Daftar DataTable berisi nomor/tanggal transaksi, kode/nama barang, style, satuan, kuantitas, keterangan, dan pencatat; filter tanggal, pencarian, modal tambah/ubah, menu aksi, serta ekspor Excel.
- Pemilih item menggunakan DataTable dan hanya menampilkan item aktif, terlacak stok, dengan kategori `WIP`/Barang Setengah Jadi atau tipe barang `work_in_process`.
- Form mencatat satu item WIP per dokumen, tanggal, jumlah, dan keterangan. Sumber MO/permintaan dan tujuan pengeluaran belum diputuskan (**NEEDS CONFIRMATION**).

## Scrap Masuk Gudang (implementasi)

- Daftar mengikuti referensi: filter rentang tanggal dan kolom nomor/tanggal penerimaan, kode/nama scrap, satuan, dan jumlah; ditambah kode/nama perca sumber, No. Produksi, dan keterangan untuk mempertahankan field form yang terlihat.
- Add/edit menggunakan modal; master barang kategori `WASTE` dan item sumber dipilih melalui DataTable. Nomor mengikuti Document Numbering, daftar memiliki ekspor Excel dan aksi koreksi/hapus kecil.
- Grup sidebar **Bahan Sisa** berisi Damage, Lost, dan Scrap Masuk Gudang. Grup **Mesin** tampil sebagai placeholder tanpa fitur transaksi sampai scope mesin dikonfirmasi.
- Makna relasi item perca/No. Produksi, valuasi, serta apakah dokumen dapat multi-item **NEEDS CONFIRMATION**.
