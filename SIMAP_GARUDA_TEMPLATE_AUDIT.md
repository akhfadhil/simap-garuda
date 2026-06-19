# SIMAP Garuda Template Audit

Dokumen ini mencatat hasil audit fitur SIMAP Garuda yang layak dipromosikan ke `simap-partai-template`.

Audit ini belum membuat template baru. Tujuannya adalah memisahkan mana yang reusable untuk project partai lain, mana yang masih hardcoded Garuda, dan mana yang perlu dirapikan sebelum diekstrak.

## Ringkasan Keputusan

SIMAP Garuda sudah cukup matang untuk menjadi sumber awal template partai, dengan catatan ekstraksi tidak boleh berupa copy mentah. Bagian yang sudah generik perlu dipindah apa adanya atau dengan sedikit rename. Bagian yang masih menyebut Garuda harus diparameterkan lewat `config/party.php`, helper, service, atau fixture test generik.

Prioritas template:

1. Standarkan identitas partai berbasis `config/party.php`.
2. Standarkan role hierarchy `admin_partai`, `korcam`, `kordes`, `saksi_tps`.
3. Standarkan scope wilayah kecamatan, desa, dan TPS.
4. Standarkan input manual suara partai/caleg per TPS.
5. Standarkan dashboard, export, status internal, dan test wajib.
6. Baru setelah itu desain import snapshot dari SIMAP utama.

## Kandidat Reusable

### Identitas Partai

File/konsep:

- `config/party.php`
- `public/images/logo-garuda.png` sebagai pola lokasi asset logo.
- Pemakaian `config('party.*')` di login, layout, setup, dashboard, export, dan validasi.

Yang bisa masuk template:

- Struktur config `slug`, `name`, `short_name`, `app_name`, `tagline`, `active_year`, `historical_numbers`, `election_types`, `assets`, `colors`, dan `roles`.
- Pola logo lokal di `public/images`.
- Prinsip nomor urut sebagai metadata historis, bukan identitas permanen.

Yang perlu diparameterkan:

- Nilai `garuda`, `Partai Garuda`, `Garuda`, `SIMAP Garuda`, `logo-garuda.png`, dan warna Garuda.
- Fallback hardcoded `Garuda` atau `Partai Garuda` pada query/helper.

### Role dan Scope Wilayah

File/konsep:

- `routes/web.php`
- `app/Http/Middleware/RoleMiddleware.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/KorcamController.php`
- `app/Http/Controllers/KordesController.php`
- `app/Http/Controllers/Admin/UserManagementController.php`
- Relasi wilayah di `users`: `kecamatan_id`, `desa_id`, `tps_id`.

Yang bisa masuk template:

- Role final `admin_partai`, `korcam`, `kordes`, `saksi_tps`.
- Scope akses bertingkat: kabupaten semua wilayah, kecamatan, desa, TPS.
- Validasi user management berdasarkan role.
- Bulk user generator untuk Korcam, Kordes, dan Saksi TPS.
- Mode view Admin Partai ke wilayah bawah.

Status sebelum template:

- Nama class, method, folder view, dan DOM/helper internal legacy `Ppk/Pps/Kpps` sudah direname ke `Korcam/Kordes/Saksi` di SIMAP Garuda.
- Backward route `ppk`, `pps`, dan `kpps` masih ada untuk kompatibilitas SIMAP Garuda, tetapi tidak boleh ikut template baru.
- Audit backward route SIMAP Garuda: sisa legacy hanya ada di `routes/web.php` sebagai redirect `dashboard.ppk`, `dashboard.pps`, `dashboard.kpps`, `ppk.data-pps`, `ppk.view-pps`, `pps.data-tps`, `pps.view-tps`, `ppk.rekap.*`, dan `pps.rekap.*`.
- Tidak ada controller, view aktif, test, atau service yang masih bergantung ke route legacy tersebut.

### Master Wilayah dan Dapil

File/konsep:

- `app/Models/Kecamatan.php`
- `app/Models/Desa.php`
- `app/Models/Tps.php`
- `app/Models/Dapil.php`
- Controller admin kecamatan, desa, TPS, dan setup dapil.

Yang bisa masuk template:

- Struktur wilayah kecamatan, desa, TPS.
- Dapil dan mapping kecamatan ke dapil untuk DPRD Kabupaten.
- CRUD master wilayah sederhana.

Yang perlu diputuskan:

- Apakah template menyediakan seed wilayah kosong, import wilayah dari SIMAP utama, atau command setup wilayah.
- Apakah istilah wilayah perlu dibuat configurable untuk daerah di luar kabupaten.

### Setup Partai dan Caleg

File/konsep:

- `app/Http/Controllers/Admin/SetupController.php`
- `app/Models/RekapPartai.php`
- `app/Models/RekapCaleg.php`
- `resources/views/admin/setup/index.blade.php`

Yang bisa masuk template:

- Setup jenis legislatif aktif.
- Setup partai utama dari config.
- Guard agar admin tidak menambahkan partai lain saat mode single-party.
- Guard agar caleg hanya masuk ke partai utama.
- Relasi partai-caleg dan dapil.

Status sebelum template:

- Scope/model final memakai `scopeConfiguredParty()` dan `isConfiguredParty()`; alias lama `scopeGaruda()` dan `isGaruda()` sudah dihapus.
- Validasi partai utama sudah berbasis config/helper party.
- Pesan error yang menyebut `SIMAP Garuda`, `Partai Garuda`, atau nomor `11`.

### Input Manual TPS

File/konsep:

- `app/Http/Controllers/Rekap/SaksiController.php`
- `resources/views/rekap/saksi/index.blade.php`
- `app/Models/RekapHeader.php`
- `app/Models/RekapPartaiSuara.php`
- `app/Models/RekapCalegSuara.php`

Yang bisa masuk template:

- Form input suara partai dan caleg.
- Draft dan finalisasi.
- Proteksi rekap final untuk non-admin.
- Input/edit oleh Saksi TPS, Kordes, Korcam, dan Admin Partai sesuai scope.
- Guard payload agar hanya partai/caleg utama yang bisa diinput.
- Pengisian field administratif legacy ke `0` agar schema lama tetap kompatibel.

Yang perlu dirapikan:

- Pesan guard masih menyebut Partai Garuda.
- Field administratif legacy bisa dipertimbangkan untuk dihapus saat template punya fresh schema sendiri.

### Dashboard Partai

File/konsep:

- `app/Services/DashboardElectionSummary.php`
- `app/Http/Controllers/DashboardController.php`
- View dashboard di `resources/views/dashboard`.

Yang bisa masuk template:

- Overview total suara partai.
- Ranking caleg partai.
- Progress TPS masuk.
- TPS belum masuk.
- TPS perlu dicek.
- Wilayah kuat dan lemah.
- Scope dashboard berdasarkan role.
- Cache summary rekap.

Yang perlu diparameterkan:

- Key output dashboard sudah memakai `total_suara_partai`; fallback legacy `total_suara_garuda` sudah dihapus.
- Method query dashboard sudah memakai istilah `configuredParty`.
- Label dashboard yang menyebut Garuda harus berbasis `config('party.short_name')`.

### Export Laporan

File/konsep:

- `app/Exports/RekapExport.php`
- `app/Exports/RekapSheetExport.php`
- `app/Exports/RekapTotalSheetExport.php`
- `app/Exports/TpsBelumMasukExport.php`
- `app/Exports/TpsPerluDicekExport.php`
- `app/Services/RekapExportService.php`

Yang bisa masuk template:

- Export TPS, desa, kecamatan, dan kabupaten.
- Export TPS belum masuk.
- Export TPS perlu dicek.
- Format Excel yang hanya membawa suara partai/caleg utama dan status input.
- Pemisahan data administratif KPU dari laporan partai.

Yang perlu diparameterkan:

- Judul `REKAPITULASI SUARA GARUDA`.
- Label `SECTION I - PEROLEHAN SUARA GARUDA`.
- Label `Total Suara Garuda`.
- Query `garuda()` di service export.

### Status Internal

File/konsep:

- Migration `2026_06_16_000001_add_internal_review_status_to_rekap_headers.php`
- `rekap_headers.status`
- `rekap_headers.catatan_internal`
- Export TPS perlu dicek.

Yang bisa masuk template:

- Status `draft`, `perlu_dicek`, dan `final`.
- Catatan internal TPS.
- Dashboard dan export TPS perlu dicek.
- Aturan Admin Partai bisa menandai perlu dicek.

Yang perlu diputuskan:

- Apakah template perlu audit trail perubahan status, bukan hanya status terakhir.
- Apakah finalisasi perlu timestamp/user finalisasi lebih rinci pada fresh schema.

### Test Wajib

File/konsep:

- `tests/Feature/PartyRoleAccessTest.php`
- `tests/Unit/DashboardElectionSummaryTest.php`

Yang bisa masuk template:

- Test login role legacy ditolak.
- Test scope wilayah Korcam/Kordes/Saksi TPS.
- Test route non-party tidak accessible.
- Test guard partai utama.
- Test input/update/finalisasi rekap.
- Test proteksi final non-admin.
- Test export tidak membawa field KPU legacy.
- Test dashboard hanya menampilkan partai/caleg utama.

Status sebelum template:

- Nama class test utama sudah generik: `PartyRoleAccessTest`.
- Fixture partai/caleg utama di feature dan unit test sudah mengambil nama serta nomor historis dari `config('party.*')`.
- Assertion label utama sudah memakai `config('party.short_name')`.
- Key backward-compatible `total_suara_garuda` sudah dihapus dari service/dashboard.

## Spesifik Garuda Yang Tidak Boleh Masuk Template Apa Adanya

Berikut item yang harus diganti, diparameterkan, atau ditahan saat ekstraksi template:

- `config/party.php` berisi slug `garuda`, nama `Partai Garuda`, nomor historis `11`, warna Garuda, dan logo Garuda.
- Asset `public/images/logo-garuda.png`.
- Method dan scope bernama `garuda`, `scopeGaruda`, `isGaruda`, `onlyGarudaPartai`, `applyGarudaPartaiQuery`, dan `guardGarudaSuaraPayload` tidak boleh masuk template; alias runtime yang tersisa sudah dibersihkan di SIMAP Garuda.
- Label UI/export `Garuda`, `Partai Garuda`, `Caleg Garuda`, `Total Suara Garuda`, dan `REKAPITULASI SUARA GARUDA`.
- Test fixture dan assertion Garuda lama sudah digeneralisasi di SIMAP Garuda; template tetap perlu memastikan tidak ada fixture spesifik partai yang ikut.
- Dokumentasi operasional Garuda harus dijadikan template dokumentasi generik dengan placeholder partai.

## Legacy Yang Jangan Dibawa Ke Template Baru

Template baru sebaiknya tidak membawa:

- Backward redirect `ppk`, `pps`, dan `kpps`.
- Nama route legacy `dashboard.ppk`, `dashboard.pps`, `dashboard.kpps`, `ppk.*`, dan `pps.*`.
- Migration lama pembuat tabel PPWP, DPD, Gubernur, Bupati.
- Migration compatibility role lama jika template memakai fresh schema.
- Config `BACKUP_DOKUMEN_PATH` jika tidak ada modul dokumen.
- Schema enum historis jenis non-party jika template memakai fresh migration yang sudah bersih.
- Test guard route legacy yang hanya relevan untuk memastikan cleanup fork Garuda.

## Audit Backward Route Legacy

SIMAP Garuda masih mempertahankan backward route hanya sebagai jembatan link lama. Hasil audit:

- Dipertahankan sementara di SIMAP Garuda:
  - `/dashboard/ppk` -> `/dashboard/korcam`
  - `/dashboard/pps` -> `/dashboard/kordes`
  - `/dashboard/kpps` -> `/dashboard/saksi`
  - `/ppk/data-pps` -> `/korcam/data-kordes`
  - `/ppk/view-pps/{desa}` -> `korcam.view-kordes`
  - `/pps/data-tps` -> `/kordes/data-tps`
  - `/pps/view-tps/{tps}` -> `kordes.view-tps`
  - `/ppk/rekap/*` -> `korcam.rekap.*`
  - `/pps/rekap/*` -> `kordes.rekap.*`
- Bisa dihapus saat masa kompatibilitas selesai karena semua controller/view aktif sudah memakai route final.
- Tidak boleh disalin ke template fresh karena project baru tidak punya riwayat URL `ppk/pps/kpps`.
- Template cukup memakai route final `dashboard.korcam`, `dashboard.kordes`, `dashboard.saksi`, `korcam.*`, `kordes.*`, `korcam.rekap.*`, dan `kordes.rekap.*`.

## Fresh Schema Template

Template partai sebaiknya memakai migration fresh/squashed, bukan membawa histori migration SIMAP Garuda apa adanya. Rekomendasi schema awal:

### Wajib Masuk

- Core Laravel:
  - `users`
  - `password_reset_tokens`
  - `sessions`
  - `cache`, `cache_locks`
  - `jobs`, `job_batches`, `failed_jobs`
- Wilayah dan dapil:
  - `dapils`: `id`, `nama`, timestamps.
  - `kecamatans`: `id`, `nama`, `dapil_id`, timestamps.
  - `desas`: `id`, `kecamatan_id`, `nama`, timestamps.
  - `tps`: `id`, `desa_id`, `nama`, timestamps.
- User party final:
  - `users.role` hanya `admin_partai`, `korcam`, `kordes`, `saksi_tps`.
  - `users.kecamatan_id`, `users.desa_id`, `users.tps_id` langsung ada di migration awal.
  - Tidak ada `partai_id`, `admin`, `ppk`, `pps`, `kpps`, `komisioner`, atau `partai` di enum awal.
- Pemilu aktif:
  - `pemilu_settings`: `jenis`, `is_active`.
  - `jenis` dibatasi secara aplikasi ke `dpr_ri`, `dprd_prov`, `dprd_kab`.
- Master legislatif:
  - `rekap_partais`: `jenis`, `nomor_urut`, `nama_partai`, `dapil_id`.
  - `rekap_calegs`: `partai_id`, `nomor_urut`, `nama_caleg`.
- Rekap TPS:
  - `rekap_headers`: `tps_id`, `jenis`, `status` (`draft`, `perlu_dicek`, `final`), `catatan_internal`, `diinput_oleh`, `difinalisasi_at`, timestamps, unique `tps_id + jenis`.
  - Untuk kompatibilitas kode saat ini, field administratif legacy bisa tetap ada dengan default `0`: `dpt_*`, `pengguna_*`, `ss_*`, `disabilitas_*`, `suara_sah`, `suara_tidak_sah`.
  - Saat template benar-benar dipisah, field administratif tersebut boleh dihapus jika controller/export sudah disesuaikan.
- Suara legislatif:
  - `rekap_partai_suaras`: `rekap_id`, `partai_id`, `suara`, unique `rekap_id + partai_id`.
  - `rekap_caleg_suaras`: `rekap_id`, `caleg_id`, `suara`, unique `rekap_id + caleg_id`.
- Flag internal:
  - `rekap_cell_flags`: `jenis`, `level`, `entity_id`, `row_key`, `flagged_by`, timestamps, unique/index final.
- Index final:
  - Gabungkan index performa dari migration 2026-05-20 dan 2026-05-24 langsung ke migration create fresh, khusus tabel legislatif yang tetap ada.

### Jangan Masuk Fresh Schema

- Tabel non-legislatif:
  - `rekap_ppwp_calons`
  - `rekap_ppwp_suaras`
  - `rekap_dpd_calons`
  - `rekap_dpd_suaras`
  - `rekap_gubernur_calons`
  - `rekap_gubernur_suaras`
  - `rekap_bupati_calons`
  - `rekap_bupati_suaras`
- Tabel dokumen internal:
  - `dokumens`
- Migration compatibility/cleanup:
  - `remove_legacy_kpu_roles_from_users`
  - `remove_non_party_rekap_data`
  - `drop_legacy_partai_id_from_users_table`
  - `migrate_users_to_party_roles`
  - `drop_legacy_non_party_rekap_tables`
  - migration enum role `komisioner`/`partai`
- Enum `rekap_headers.jenis` yang masih memuat `ppwp` atau `dpd`.
- Config backup dokumen jika modul dokumen tidak ikut template.

## Kandidat Struktur Template

Struktur minimal yang disarankan:

```text
simap-partai-template
config/party.php
public/images/party-logo.png
app/Support/PartyConfig.php
app/Support/PartyRoles.php
app/Services/PartyScopeService.php
app/Services/DashboardElectionSummary.php
app/Services/RekapExportService.php
app/Http/Controllers/Admin/SetupController.php
app/Http/Controllers/Admin/UserManagementController.php
app/Http/Controllers/KorcamController.php
app/Http/Controllers/KordesController.php
app/Http/Controllers/Rekap/SaksiController.php
database/migrations
database/seeders
tests/Feature/PartyRoleAccessTest.php
tests/Unit/DashboardElectionSummaryTest.php
PARTY_PROJECT_OPERASIONAL.md
```

Helper yang perlu dibuat saat ekstraksi:

- `PartyConfig::matchesParty($nomorUrut, $namaPartai)`
- `PartyConfig::historicalNumbers()`
- `PartyConfig::name()`
- `PartyConfig::shortName()`
- `PartyRoles::all()`
- `PartyRoles::operational()`
- `PartyScopeService::scopeForUser($user)`
- `PartyScopeService::canAccessTps($user, $tps)`
- `PartyScopeService::canAccessDesa($user, $desa)`
- `PartyScopeService::canAccessKecamatan($user, $kecamatan)`

## Urutan Ekstraksi Yang Disarankan

1. Buat helper `PartyConfig` untuk menggantikan duplikasi query matching partai. Selesai di SIMAP Garuda.
2. Rename konsep Garuda menjadi konsep party generik di SIMAP Garuda tanpa mengubah behavior. Sebagian besar selesai: matching partai, scope `configuredParty()`, label export/UI utama, key dashboard, fixture test, dan identifier Blade/JS internal sudah berbasis config/party.
3. Buat `PartyScopeService` untuk menyatukan aturan scope wilayah. Selesai di SIMAP Garuda.
4. Rename controller/view legacy `Ppk/Pps/Kpps` menjadi `Korcam/Kordes/Saksi`. Selesai di SIMAP Garuda.
5. Hapus backward route legacy dari calon template. Audit selesai; route legacy hanya redirect kompatibilitas di SIMAP Garuda.
6. Buat fresh migration template yang hanya membawa schema party app. Audit schema selesai; daftar tabel wajib dan yang tidak boleh ikut sudah dicatat.
7. Generalisasi test fixture dari Garuda ke party config. Selesai di SIMAP Garuda.
8. Baru copy ke folder/repo `simap-partai-template`.

## Status Audit

- Audit fitur generik selesai.
- Daftar bagian reusable selesai.
- Daftar hardcode Garuda selesai.
- Daftar legacy yang tidak boleh masuk template selesai.
- Helper `PartyConfig` dan scope `configuredParty()` sudah dibuat sebagai langkah awal generalisasi.
- `PartyScopeService` sudah dibuat untuk memusatkan akses kecamatan, desa, TPS, active scope dashboard, dan active entity per role.
- Rename controller/view legacy selesai di SIMAP Garuda: class, method, folder view, dan DOM/helper internal sudah memakai istilah Korcam/Kordes/Saksi.
- Backward route `ppk/pps/kpps` sengaja masih dipertahankan di SIMAP Garuda sebagai redirect kompatibilitas, tetapi ditandai tidak boleh masuk template.
- Generalisasi fixture test selesai: test feature utama sudah menjadi `PartyRoleAccessTest`, dan fixture/assertion partai utama di test memakai `config('party.*')`.
- Generalisasi identifier internal selesai: key dashboard `total_suara_partai` menjadi satu-satunya key aktif, alias model Garuda dihapus, dan DOM/variabel Blade rekap memakai istilah party.
- Audit backward route legacy selesai: route lama `ppk/pps/kpps` hanya redirect kompatibilitas di `routes/web.php`, tidak dipakai controller/view aktif, dan tidak boleh masuk template.
- Audit fresh schema template selesai: template perlu migration squashed legislatif-only dengan role final, wilayah, dapil, master partai/caleg, rekap TPS, status internal, flag internal, dan index final; migration cleanup/histori legacy tidak ikut.
- Import snapshot dari SIMAP utama belum didesain; tetap menjadi pekerjaan terpisah setelah kebutuhan format data SIMAP utama jelas.
- Eksekusi template tahap pertama sudah dimulai di `../simap-partai-template`.
- Isi tahap pertama: `config/party.php`, `app/Support/PartyConfig.php`, `app/Services/PartyScopeService.php`, `database/migrations/0001_01_01_000000_create_party_app_schema.php`, `.gitignore`, `README.md`, dan `PARTY_PROJECT_OPERASIONAL.md`.
- Tahap pertama sengaja kecil dan belum membawa skeleton Laravel lengkap, route, controller, view, export, dashboard, atau test.
