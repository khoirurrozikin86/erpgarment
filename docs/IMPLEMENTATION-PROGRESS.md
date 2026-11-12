# Progres Implementasi

Pengerjaan dilakukan satu modul per instruksi. Tidak ada coding modul berikutnya sebelum pengguna memberikan arahan.

## 2026-11-13 — Master Importir

- **Status:** selesai untuk scope CRUD master.
- **Lingkup:** migration/model, DTO, query DataTable, create/update/delete actions dan service, request validation, controller/routes, permission, navigasi, form daftar, dan audit log.
- **Field:** NPWP, nama, alamat, APIP, status aktif/nonaktif. Ini mengikuti field yang terlihat pada referensi; aturan bisnis yang tidak terlihat tidak diasumsikan.
- **Test:** `php artisan test --compact tests/Feature/Masters/ImporterTest.php` — **lulus**, 2 test / 13 assertion. `php artisan route:list --path=super/importers` menampilkan 5 route; file PHP yang disentuh lolos `php -l`.
- **Suite keseluruhan:** `php artisan test --compact` — 21 lulus, 11 gagal. Kegagalan yang terlihat berasal dari area lain: route bernama `dashboard` tidak terdefinisi pada test auth, syntax error pada partial profile, dan fixture user yang tidak ditemukan pada `CustomerReturnTest`. Tidak ada stack trace kegagalan yang menunjuk ke modul Importir.
- **Perbaikan CRUD lanjutan:** skrip modal sebelumnya berada di dalam section konten sehingga dirender sebelum jQuery/Bootstrap dari layout; dipindahkan ke `@push('scripts')`. Tombol edit/hapus kini memakai payload dan action partial DataTable yang sama dengan master kategori/satuan, termasuk validasi AJAX dan konfirmasi hapus. Test modul dijalankan ulang setelah perbaikan.
- **Sidebar:** menu dirapikan mengikuti kelompok navigasi yang tampak pada referensi. Grup yang belum punya halaman/route implementasi tidak ditampilkan sebagai menu kosong; menu ERP existing tetap dipertahankan dalam grupnya sendiri.
- **NEEDS CONFIRMATION:** format dan keunikan NPWP, makna/format APIP, lingkup multi-company, dan aturan delete ketika sudah direferensikan dokumen BC.
- **Belum dikerjakan:** Exportir dan relasi Importir ke dokumen BC; keduanya menunggu modul/instruksi berikutnya serta keputusan cardinality.
