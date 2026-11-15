# Monitoring kontrak subkon

Modul baca-saja sesuai screenshot pengguna, memakai SubcontractTableQuery dan SubcontractContract yang sudah ada. Tidak membuat kontrak/model/saldo baru, tidak mengubah kuota.

Kolom: No, Subkon, No Kontrak, No SKEP BC, Deskripsi, Mulai, Berakhir, Kuota. Periode memfilter starts_on sebagaimana daftar kontrak yang ada; default awal tahun sampai hari ini. Pencarian nama subkon/nomor kontrak/SKEP/deskripsi, pagination 10/25/50/100, Indonesia/Inggris. Data hanya company aktif; kontrak soft-deleted tidak ditampilkan. Urutan starts_on terbaru lalu ID.

Menu Subkon → Monitoring Kontrak Subkon; permission subcontract-contracts.view yang sudah ada. Pengelolaan tambah/ubah/hapus tetap pada master kontrak. Tidak menambahkan export/status/rumus penggunaan kuota yang tidak tampak pada referensi.

NEEDS CONFIRMATION: kuota menunjukkan nilai tersimpan, bukan sisa kuota. Hubungan hasil penerimaan dengan nomor kontrak manual dan perhitungan pemakaian/sisa kuota lintas satuan belum disepakati sehingga tidak dijumlahkan otomatis.

File: controller monitoring, view monitoring, terjemahan id/en, route dan sidebar. Tidak membutuhkan migration. Test/build tidak dijalankan sesuai AGENTS.md.
