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
| Produksi | Daftar bernomor MO/planning, tanggal, user, dokumen dan jumlah; subhalaman Detail; beberapa menu produksi menunjukkan record hasil tiap tahap | Hasil cutting `image83`, hasil packing `image89`, barang setengah jadi `image127` |
| Monitoring | Filter tanggal; kontrak, subkon, nomor kontrak/BC, keterangan, mulai/berakhir, kuota; beberapa tabel detail keluar/masuk | Kontrak `image125`/`image132`, monitoring `image133`–`image135` |
| Laporan | Filter periode/tanggal, kadang pilihan kategori atau jenis BC; Tampilkan Data dan Export Excel; tabel baca-saja dengan kolom agregat atau lintas tahap | Laporan pemasukan `image153`, WIP `image171`, status harian `image185` |

## Catatan konsistensi

- Label filter bervariasi: `Tanggal`, `Tanggal Doc`, `Tanggal Order`, `Periode`; format tanggal DD-MM-YYYY atau YYYY-MM-DD. Standar dan locale perlu dikonfirmasi.
- Beberapa screenshot menampilkan tabel kosong, record tidak cocok, atau pesan `Anda tidak berhak mengakses data / modul belum lengkap`. Itu adalah keadaan screenshot; bukan bukti fitur sengaja kosong atau aturan hak akses final.
- Banyak tabel lebar mempunyai horizontal scrollbar dan kolom bertingkat. Perilaku responsif, urutan/visibilitas kolom, serta format cetak belum jelas.
- Screenshot daftar umumnya tidak menunjukkan validasi form, dialog konfirmasi hapus, error server, loading state, filter selain tanggal, maupun perilaku mobile: **NEEDS CONFIRMATION**.
