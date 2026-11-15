# Penerimaan Subkon

Permintaan pengguna: modul penerimaan mengikuti screenshot dan tampilan modul subkon lain. Pengguna secara eksplisit memilih “Stok bertambah saat konfirmasi”.

Menggunakan fondasi dokumen/detail subkon yang ada dengan issue_kind receipt, tanpa model/dokumen transaksi duplikat. Invoice number/date baru nullable. Nomor/tanggal terima, kontrak, subkon sesuai kontrak, invoice, dan item multi-baris (produk, qty, style, keterangan). Nomor awal TIMW/{NUMBER}/SBBM/{MM}/{YY} sesuai form baru pada referensi, dapat diatur pada penomoran. Label daftar memakai tanggal terima, bukan teks Date Pengeluaran yang tampak tidak konsisten pada screenshot.

Sumber produk adalah produk stock/track_stock aktif; tidak dibatasi FG/WIP karena referensi mencakup bahan dan penolong. Satu baris per detail, empat desimal, tanpa menggabungkan barang dengan style berbeda. Kontrak/penerima dibatasi perusahaan aktif dan kecocokannya divalidasi server. Dokumen dipisahkan per jenis pada setiap query/endpoint supaya tidak muncul pada pengeluaran bahan/WIP.

## Alur yang disetujui

- Simpan menghasilkan draft, belum menambah stok.
- Konfirmasi Penerimaan Gudang mengunci dokumen, detail, produk, dan saldo berurutan; menambah saldo serta membuat lot SUBREC per item, alokasi lot, dan StockMovement positif subcontract_receipt dengan referensi header/detail.
- Status confirmed, confirmed_by/at dan audit log dicatat pada transaksi yang sama. Unique sumber item mencegah posting ganda; pengulangan konfirmasi ditolak. Semua item batal jika salah satu gagal.
- Dokumen confirmed tidak bisa diubah/dihapus. Tidak ada reversal tanpa aturan pembatalan terpisah.
- Waktu ledger/lot memakai waktu konfirmasi; tanggal terima tetap tanggal dokumen. Saldo sebelum/sesudah dan sumber terlihat di kartu stok serta export kartu stok.

## Biaya dan batas proses

Rata-rata biaya stok yang sudah ada dipertahankan untuk saldo dan dipakai sebagai nilai unit lot penerimaan. Jika belum ada nilai rata-rata, nilainya tetap nol; tidak mengarang harga invoice atau jasa subkon. NEEDS CONFIRMATION: aturan valuasi hasil subkon, biaya jasa, transfer nilai bahan keluar ke hasil, dan jurnal. Kuota kontrak tidak berubah.

NEEDS CONFIRMATION: hubungan kuantitas hasil ke pengeluaran subkon, rasio hasil, retur sisa bahan, tanggal berlaku kontrak, invoice wajib/unik, dan status penerimaan parsial. Tidak menyimpulkan kuota atau toleransi dari screenshot.

Daftar/form memakai UI bersama dengan modul subkon, bahasa Indonesia/Inggris, filter tanggal/pencarian/pagination, export XLSX seluruh hasil filter termasuk invoice/status/konfirmasi. Permission subcontract-receipts.view/create/update/delete/confirm (admin/super_admin default); data perusahaan dibatasi server. Penomoran tersedia pada company aktif, company baru, dan seeder.

Migration khusus berhasil dijalankan. Test/build tidak dijalankan sesuai AGENTS.md. Tidak ada dokumen yang diposting atau saldo yang diubah selama implementasi.
