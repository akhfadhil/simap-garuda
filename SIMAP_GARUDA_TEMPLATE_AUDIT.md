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
- `app/Http/Controllers/PpkController.php`
- `app/Http/Controllers/PpsController.php`
- `app/Http/Controllers/Admin/UserManagementController.php`
- Relasi wilayah di `users`: `kecamatan_id`, `desa_id`, `tps_id`.

Yang bisa masuk template:

- Role final `admin_partai`, `korcam`, `kordes`, `saksi_tps`.
- Scope akses bertingkat: kabupaten semua wilayah, kecamatan, desa, TPS.
- Validasi user management berdasarkan role.
- Bulk user generator untuk Korcam, Kordes, dan Saksi TPS.
- Mode view Admin Partai ke wilayah bawah.

Yang perlu dirapikan sebelum template:

- Nama class `PpkController`, `PpsController`, dan view folder `ppk`, `pps`, `kpps` masih membawa istilah legacy.
- Backward route `ppk`, `pps`, dan `kpps` sebaiknya tidak ikut template baru.
- Nama method internal seperti `dataPps`, `viewPps`, dan `bulkKppsRows` perlu diganti agar konsisten dengan istilah partai.

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

Yang perlu diparameterkan:

- Method `scopeGaruda()` dan `isGaruda()` perlu diganti menjadi nama generik seperti `scopeConfiguredParty()` dan `isConfiguredParty()`.
- Method `isGarudaPartaiRow()` perlu diganti menjadi validasi partai utama berbasis config.
- Pesan error yang menyebut `SIMAP Garuda`, `Partai Garuda`, atau nomor `11`.

### Input Manual TPS

File/konsep:

- `app/Http/Controllers/Rekap/KppsController.php`
- `resources/views/rekap/kpps/index.blade.php`
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

- Nama controller/view masih memakai `Kpps`.
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

- Key output `total_suara_garuda` perlu diganti menjadi nama generik seperti `total_suara_partai`.
- Method `totalGarudaSuara`, `garudaPartaiSuaraQuery`, `garudaCalegSuaraQuery`, dan `onlyGarudaPartai` perlu diganti nama generik.
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

- `tests/Feature/GarudaRoleAccessTest.php`
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

Yang perlu diparameterkan:

- Nama class test `GarudaRoleAccessTest`.
- Fixture `Partai Garuda`, `Caleg Garuda`, nomor `11`, dan key `total_suara_garuda`.
- Assertion label `Total Suara Garuda`.

## Spesifik Garuda Yang Tidak Boleh Masuk Template Apa Adanya

Berikut item yang harus diganti, diparameterkan, atau ditahan saat ekstraksi template:

- `config/party.php` berisi slug `garuda`, nama `Partai Garuda`, nomor historis `11`, warna Garuda, dan logo Garuda.
- Asset `public/images/logo-garuda.png`.
- Method dan scope bernama `garuda`, `scopeGaruda`, `isGaruda`, `onlyGarudaPartai`, `applyGarudaPartaiQuery`, dan `guardGarudaSuaraPayload`.
- Label UI/export `Garuda`, `Partai Garuda`, `Caleg Garuda`, `Total Suara Garuda`, dan `REKAPITULASI SUARA GARUDA`.
- Test fixture dan assertion yang menyebut Garuda.
- Dokumentasi operasional Garuda harus dijadikan template dokumentasi generik dengan placeholder partai.

## Legacy Yang Jangan Dibawa Ke Template Baru

Template baru sebaiknya tidak membawa:

- Backward redirect `ppk`, `pps`, dan `kpps`.
- Nama class/controller/view legacy `Ppk`, `Pps`, dan `Kpps`.
- Migration lama pembuat tabel PPWP, DPD, Gubernur, Bupati.
- Migration compatibility role lama jika template memakai fresh schema.
- Config `BACKUP_DOKUMEN_PATH` jika tidak ada modul dokumen.
- Schema enum historis jenis non-party jika template memakai fresh migration yang sudah bersih.
- Test guard route legacy yang hanya relevan untuk memastikan cleanup fork Garuda.

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
2. Rename konsep Garuda menjadi konsep party generik di SIMAP Garuda tanpa mengubah behavior. Sebagian selesai: matching partai, scope `configuredParty()`, dan label export/UI utama sudah berbasis config.
3. Rename controller/view legacy `Ppk/Pps/Kpps` menjadi `Korcam/Kordes/Saksi`.
4. Buat `PartyScopeService` untuk menyatukan aturan scope wilayah.
5. Hapus backward route legacy dari calon template.
6. Buat fresh migration template yang hanya membawa schema party app.
7. Generalisasi test fixture dari Garuda ke party config.
8. Baru copy ke folder/repo `simap-partai-template`.

## Status Audit

- Audit fitur generik selesai.
- Daftar bagian reusable selesai.
- Daftar hardcode Garuda selesai.
- Daftar legacy yang tidak boleh masuk template selesai.
- Helper `PartyConfig` dan scope `configuredParty()` sudah dibuat sebagai langkah awal generalisasi.
- Import snapshot dari SIMAP utama belum didesain; tetap menjadi pekerjaan terpisah setelah kebutuhan format data SIMAP utama jelas.
