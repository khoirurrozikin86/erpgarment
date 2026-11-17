<?php

return [
    'stage_today' => 'Hari ini',
    'stage_total' => 'Kumulatif',
    'image_only_notice' => 'MO sudah dipakai transaksi. Anda tetap dapat menambah atau mengganti gambar; data order lainnya terkunci.',
    'image_updated_audit' => 'Mengubah gambar Material Order :number',

    'upload_hint' => 'Upload JPG, PNG atau WebP, maksimal 5 MB. Gambar berlaku untuk semua size pada penyimpanan ini. Saat edit, kosongkan untuk mempertahankan gambar.',
    'upload_failed' => 'Gambar MO gagal disimpan.',

    'matrix' => 'Isi beberapa size sekaligus',
    'matrix_hint' => 'Isi satu warna dan qty per size. Setiap size disimpan sebagai rincian dalam nomor MO yang sama. Varian barang dibuat otomatis jika belum ada.',
    'add_size' => '+ Tambah size',
    'duplicate_size' => 'Size tidak boleh berulang pada warna yang sama.',
    'inactive' => 'Varian barang ini tidak aktif. Aktifkan kembali di master Item.',

    'image' => 'Gambar barang',
    'image_hint' => 'Belum ada gambar. Pilih file untuk menambahkan gambar MO.',
    'parent' => 'Barang induk (opsional)',
    'standalone' => 'Barang mandiri / induk',
    'invalid_parent' => 'Pilih barang induk tanpa induk lain. Barang yang memiliki varian tidak dapat dijadikan anak.',
    'required_dimensions' => 'Isi warna atau size untuk varian.',
    'same_unit' => 'Satuan dan tipe varian harus sama dengan barang induk.',
    'duplicate' => 'Kombinasi warna dan size pada barang induk ini sudah ada.',
    'used_identity' => 'Identitas varian sudah dipakai transaksi/BOM. Buat SKU baru untuk varian berbeda.',
    'applies_color' => 'Warna order',
    'applies_size' => 'Size order',
    'all' => 'Semua',
    'selector_help' => 'Kosong = berlaku untuk semua varian order. Baris yang cocok dijumlahkan; isi konsumsi per size bila berbeda.',
    'parent_help' => 'Setiap varian fisik memakai kode SKU unik dan stok sendiri. Gambar dapat diunggah di bagian gambar barang.',
];
