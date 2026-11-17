# Varian warna, size, gambar dan order

Implementasi memakai master Product yang sudah ada sebagai SKU fisik. Tidak ada model stok varian kedua dan tidak ada konversi data lama otomatis.

## Pemakaian

1. Di Item, buat barang induk untuk satu model/style, misalnya KAOS-BAPE. Barang lama dapat tetap mandiri.
2. SKU anak dapat dibuat otomatis dari form MO (langkah 4), atau melalui master Item. Untuk membuat manual, buat SKU anak, misalnya KAOS-BAPE-BLK-S. Pilih barang induk, isi warna BLACK, size S, satuan yang sama, dan unggah gambar melalui fasilitas gambar barang yang sudah ada. Kombinasi warna/size dalam induk yang sama tidak boleh duplikat.
3. Di BOM induk, pilih SKU bahan yang benar. Kolom **Warna order** dan **Size order** adalah kondisi untuk produk hasil, bukan atribut bahan. Kosong berarti semua. Misalnya kain hitam hanya untuk BLACK, size S konsumsi 1,2; size M konsumsi 1,3. Baris umum dan khusus yang cocok bersifat tambahan, bukan saling menggantikan.
4. Di tambah MO, pilih barang/style induk, tulis warna, lalu isi size dan qty pada tabel **Isi beberapa size sekaligus**. Contoh BLACK: S=2, L=4, XL=7 menghasilkan tiga rincian dengan total 13 PCS dalam nomor MO yang sama. Tombol Tambah size menambah baris input. Varian yang belum ada dibuat otomatis dengan SKU unik; varian yang sudah ada dipakai kembali. Seluruh rincian disimpan dalam satu transaksi. Gambar memakai gambar varian, atau gambar induknya bila gambar varian belum ada. Harga per unit berlaku untuk semua baris size yang diinput pada penyimpanan itu. Untuk warna berikutnya gunakan nomor MO dan header yang sama. Edit dilakukan per rincian warna/size, mengikuti penguncian MO yang sudah berjalan.
5. Sistem memilih BOM aktif SKU hasil; bila tidak ada, memakai BOM aktif induknya. Tanggal efektif tetap diperiksa. BOM SKU khusus memiliki prioritas penuh. Hanya komponen dengan kondisi warna/size yang cocok digunakan; perhitungan konsumsi dan wastage mengikuti rumus sebelumnya.
6. Ambil kebutuhan MO pada MR, lalu lanjutkan approval MR → PR → PO seperti alur sebelumnya. Referensi product_id material yang sama diteruskan, sehingga warna/size bahan dan stoknya tidak bercampur. PO PDF dan ekspor MO/PO memiliki kolom warna dan size.

Bahan bersama seperti benang hitam tetap satu SKU tanpa size bila fisiknya memang sama. Label atau hangtag yang berbeda size memakai SKU berbeda. Pengelompokan dilakukan lewat barang induk; kode SKU tetap unik. Varian bahan tidak ditentukan otomatis hanya dari kesamaan nama warna produk hasil: pemetaan dipilih eksplisit di BOM.

Identitas induk/warna/size yang sudah dipakai BOM, MO, MR atau pergerakan stok dikunci pada perubahan melalui master barang. Buat SKU baru untuk identitas lain. Harga, approval gudang, dan cara posting stok tetap mengikuti transaksi sebelumnya.

## Database dan deployment

Migration: `2026_11_17_000001_add_product_variant_bom_selectors.php` (sudah diterapkan di lokal).

Di VPS setelah kode diperbarui, jalankan:

```sh
php artisan migrate --force
```

Tidak perlu reset atau seed ulang. Migration menambahkan relasi induk dengan penghapusan restrict, indeks kombinasi varian, selector BOM, dan indeks BOM pengganti. Rollback dapat ditolak apabila kombinasi komponen baru bertentangan dengan indeks lama; jangan rollback tanpa meninjau data tersebut.

Tidak menjalankan test suite/build sesuai instruksi project. Validasi fungsional melalui browser belum dilakukan.

## Upload gambar langsung di MO

Form tambah/edit MO menerima gambar JPG, PNG atau WebP hingga 5 MB, disertai pratinjau. Gambar disimpan khusus pada rincian MO; tidak mengubah gambar master Item. Pada input beberapa size, satu gambar dipakai untuk seluruh rincian yang dibuat pada penyimpanan itu. Edit tanpa file baru mempertahankan gambar yang sudah tersimpan. Jika MO belum memiliki gambar khusus, tampilan memakai gambar Item/induk. Upload dibersihkan apabila transaksi penyimpanan gagal. Migration tambahan: `2026_11_17_000002_add_image_to_production_orders.php`, sudah diterapkan lokal.

MO yang sudah memiliki transaksi material/produksi tetap dapat memperbarui gambar melalui edit. Form hanya mengaktifkan upload gambar, dan server menyimpan image_path beserta audit tanpa menghitung ulang BOM/material atau mengubah header/qty/stok. Penguncian edit/hapus transaksi tetap berlaku.

## Monitoring produksi dan WIP per varian

- Cutting, Sewing, Packing Result dan Barang Jadi: daftar dan pemilihan MO menampilkan warna, size, serta qty order dari rincian MO, bukan total nomor MO. Label pada form/detail juga menyertakan varian. Ekspor menyertakan warna dan size.
- Laporan Rencana Produksi, Status Produksi Harian dan Hasil Produksi: kolom warna/size, pencarian varian dan ekspor Excel diselaraskan. Status harian juga menampilkan hasil packing hari ini dan kumulatif. Tidak ada pembulatan ke ribuan; kuantitas tetap angka transaksi aslinya.
- Posisi WIP sekarang dikelompokkan berdasarkan ID rincian MO + SKU + tahap CUT/SEW/PKG. Dua MO dengan SKU sama tidak digabungkan. Kolom nomor MO, style, warna, size dan qty order ditampilkan bersama saldo WIP tiap tahap. Qty order berulang pada setiap posisi tahap untuk konteks, bukan untuk dijumlahkan lintas tahap.
- Formula event WIP tetap mengikuti alur sebelumnya: hasil baik tahap sebelumnya dikurangi input ke tahap berikutnya, sesuai ID rincian MO. Pemilihan Sewing mengabaikan transaksi yang sudah soft-delete agar sejalan dengan WIP.
- Data lama tanpa varian ditampilkan dengan warna/size kosong (—); tidak dipecah atau dipindahkan otomatis. Tidak ada perubahan saldo stok atau seeding pada pekerjaan monitoring ini.

File utama: query Manufacturing untuk empat laporan dan empat tahap, controller Cutting/Sewing/Packing/FinishedGoods, Blade laporan/tahap, pemetaan BcReportDataTable dan export terkait. Tidak menjalankan test/build maupun pengujian browser sesuai instruksi project.
