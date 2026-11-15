# BC IN — BC 2.3

Implementasi diminta pengguna mengikuti daftar dan form screenshot, dengan tampilan card/form/picker seperti modul subkon. Grup BC IN sebelum Warehouse. UI Indonesia/Inggris, filter tanggal dokumen, pencarian, pagination, export XLSX seluruh hasil.

## Data

Fondasi baru customs_documents/customs_document_items diperlukan karena belum ada model dokumen BC multi-GRN; tidak membuat penerimaan atau stok baru. Header company aktif dan jenis BC 2.3, nomor aju, nomor/tanggal BC, BL-AWB, invoice, supplier, importir, jenis harga, valuta. Nomor disimpan string untuk nol depan. Unique company/jenis/aju; indeks company/jenis/tanggal; relasi master restrict, pengguna nullOnDelete, header soft delete dan audit.

Detail produk/master snapshot kode-nama-unit, sumber GRN opsional, nomor/tanggal terima, qty/harga unit empat desimal, total qty × harga unit, CIF manual, kode negara asal, HS. Boleh beberapa detail produk/GRN sama; tidak menganggap duplikat sebagai kesalahan atau menjumlahkan qty lintas satuan. Snapshot dan total divalidasi/dibentuk server; input total browser bukan sumber kebenaran.

Simpan Dok BC menyimpan header saja, termasuk dokumen belum memiliki item; header tetap tampil pada daftar melalui left join. Simpan Semua menyimpan header dan seluruh item secara atomik. Perubahan supplier pada header beritem harus melalui Simpan Semua agar relasi sumber diperiksa ulang. Hapus dokumen bersifat soft delete dan tidak menghapus GRN/stok. Audit mencatat snapshot header/detail sebelum/sesudah.

## Pemilihan sumber

Cari Barang memilih master produk aktif untuk input manual nomor/tanggal terima dan harga. Ambil GRN menampilkan detail penerimaan approved supplier yang dipilih dan penerimaan peralatan supplier/company aktif, maksimum 50 hasil gabungan per pencarian. Satu item hanya boleh mengacu satu sumber goods/equipment; FK dan kecocokan product/supplier/company peralatan divalidasi ulang pada simpan. Nomor/tanggal sumber diambil dari relasi di server, bukan dipercaya dari browser.

Purchase GRN belum mempunyai company_id, sehingga mengikuti cakupan purchasing bersama aplikasi; tidak mengarang isolasi company melalui relasi yang belum ada. Equipment GRN mempunyai company_id dan dibatasi company aktif. Penerimaan subkon belum ditambahkan ke pemilih ini karena tidak terlihat pada referensi BC 2.3 dan proses pengaitan belum ditetapkan.

## Batas yang disengaja / NEEDS CONFIRMATION

- Administrasi BC tidak menambah/mengurangi stok, lot, biaya rata-rata, atau jurnal. Stok tetap ditangani transaksi penerimaan yang sudah ada.
- Tidak menimpa customs_document_code/number/date pada GoodsReceipt otomatis: aturan sumber kebenaran dan pembagian satu penerimaan ke beberapa BC belum ditetapkan.
- CIF input manual; tidak menghitung dari FOB, freight, insurance atau kurs tanpa rumus disepakati. Valuta tiga huruf kapital; jenis harga string manual, tanpa menyimpulkan daftar legal dari satu opsi screenshot.
- HS dan negara adalah field detail BC, tidak mengembalikan kolom yang telah dihapus dari Product. Kode negara input manual (maksimal tiga karakter), belum master negara resmi.
- Format/legalitas aju, uniqueness nomor daftar, approval/finalisasi, toleransi qty terhadap GRN, penerimaan parsial dan pengaitan barang mesin tanpa product_id belum dipastikan. Modul ini administrasi internal, bukan pengiriman dokumen ke layanan kepabeanan.
- Nomor dokumen/aju manual; tidak dibuat otomatis dari contoh.

Permission bc23.view/create/update/delete; default admin/super_admin mengikuti pola project. Endpoints BC company/type scoped, selector memakai create/update, export memakai view. Migration khusus berhasil dijalankan. Test/build tidak dijalankan sesuai AGENTS.md. Tidak memposting transaksi selama implementasi.
