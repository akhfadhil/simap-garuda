# SIMAP - Sistem Informasi Manajemen Arsip Pemilu

SIMAP adalah aplikasi Laravel untuk pengelolaan arsip dokumen pemilu dan rekapitulasi suara berjenjang di Kabupaten Banyuwangi. Aplikasi dipakai oleh admin/operator KPU Kabupaten, komisioner, partai politik, PPK, PPS, dan KPPS dengan pembatasan akses berdasarkan role serta wilayah kerja.

## Stack

| Layer | Teknologi |
| --- | --- |
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Blade, Tailwind CSS 4, Flowbite 4, Vite 7 |
| Database | MySQL/MariaDB |
| Auth | Session auth Laravel + `RoleMiddleware` |
| Export | Maatwebsite Excel |
| Grafik | Chart.js di halaman grafik admin |
| Peta | GeoJSON Banyuwangi di `public/geojson` |
| Cache rekap | Cache Laravel, dikonfigurasi lewat `config/rekap.php` |

## Role dan Akses

| Role | Level | Akses utama |
| --- | --- | --- |
| `admin` | Kabupaten | Admin/operator dengan akses penuh: kelola wilayah, user, setup pemilu, rekap kabupaten, grafik, verifikasi dokumen, backup/restore arsip |
| `komisioner` | Kabupaten | Read-only untuk beranda, rekap dokumen, rekapitulasi data, grafik, preview/download dokumen, dan export rekap |
| `partai` | Kabupaten | Login khusus partai, read-only untuk grafik dan rekap suara partainya sendiri beserta caleg; PPWP, gubernur, bupati, dan DPD tetap menampilkan semua calon |
| `ppk` | Kecamatan | Lihat rekap kecamatan, export, upload dokumen kecamatan, memantau PPS dan KPPS di kecamatan sendiri |
| `pps` | Desa/Kelurahan | Lihat rekap desa, export, verifikasi dokumen TPS, memantau KPPS di desa sendiri |
| `kpps` | TPS | Input rekap TPS, simpan draft/final, export TPS, upload dokumen TPS |

Hierarki akses: `admin` dapat mengakses semua wilayah, `ppk` dapat melihat PPS/KPPS di bawah kecamatannya, `pps` dapat melihat KPPS di bawah desanya, dan `kpps` hanya dapat mengakses TPS miliknya. Akses turun level untuk PPK/PPS bersifat lihat saja; perubahan rekapitulasi data normal dilakukan oleh KPPS. Admin memiliki akses koreksi rekap sementara untuk membuka form rekap TPS dari halaman rekap admin.

Admin juga bisa memakai mode "view as" untuk melihat konteks PPK, PPS, atau KPPS melalui session wilayah. Mode view menampilkan info konteks dan tombol kembali pada halaman beranda serta rekap dokumen.

## Jenis Pemilihan

Jenis pemilihan didefinisikan di `App\Models\RekapHeader::JENIS_LABELS` dan dapat diaktif/nonaktifkan lewat `pemilu_settings`.

| Key | Label |
| --- | --- |
| `ppwp` | Presiden & Wakil Presiden |
| `gubernur` | Gubernur & Wakil Gubernur |
| `bupati` | Bupati & Wakil Bupati |
| `dpd` | DPD |
| `dpr_ri` | DPR RI |
| `dprd_prov` | DPRD Provinsi |
| `dprd_kab` | DPRD Kabupaten |

## Fitur Utama

### Autentikasi dan UI

- Login/logout multi-role dengan redirect dashboard.
- Login khusus akun partai tersedia di `/partai/login`.
- Semua user login dapat mengganti password sendiri melalui `/password`.
- Middleware role untuk membatasi halaman.
- Layout utama dengan topbar, logo KPU, dark/light mode, toast konfirmasi global, dan modal preview PDF.
- Error page custom untuk 403, 404, 419, 500, dan 503.
- Beranda admin dan komisioner menampilkan ringkasan dokumen, rekap final, serta ringkasan pemenang tiap jenis pemilu aktif.

### Admin

- CRUD kecamatan, desa, TPS, dan user.
- Manajemen pengguna dapat membuat admin/operator, komisioner, partai, PPK, PPS, dan KPPS.
- Admin/operator dan komisioner tidak memerlukan wilayah. Akun partai wajib dihubungkan ke master partai. PPK/PPS/KPPS wajib mengikuti scope wilayahnya.
- Proteksi penghapusan user mencegah admin menghapus akun yang sedang dipakai dan mencegah penghapusan admin terakhir.
- Bulk tambah TPS dari jumlah input dan edit TPS via modal.
- Setup master data pemilu: PPWP, gubernur, bupati, DPD, partai, caleg, dapil, dan pemilu aktif.
- Assign dapil ke kecamatan.
- Tool admin untuk menjalankan backup dokumen dan seed partai dari UI.

### Komisioner

- Memiliki beranda kabupaten dengan isi ringkasan seperti admin.
- Menu dibatasi ke Beranda, Grafik & Statistik, Rekap Dokumen, dan Rekapitulasi Data.
- Dapat melihat data kabupaten, preview/download dokumen, serta export rekap.
- Tidak dapat mengubah dokumen, rekap, setup pemilu, wilayah, atau pengguna.

### Partai

- Memiliki halaman login khusus di `/partai/login`.
- Akun partai dihubungkan ke salah satu master `rekap_partais`; scope data partai dicocokkan berdasarkan `nomor_urut`.
- Untuk jenis legislatif (`dpr_ri`, `dprd_prov`, `dprd_kab`), grafik dan rekap hanya menampilkan partai tersebut dan calegnya.
- Untuk PPWP, gubernur, bupati, dan DPD, data kandidat tetap tampil lengkap.
- Menu partai dibatasi ke Grafik & Statistik dan Rekapitulasi Data.

### Rekap Suara

- KPPS mengisi rekap per TPS dan per jenis pemilihan.
- Status rekap: `draft` dan `final`.
- Admin bisa unlock rekap yang sudah final agar dapat diedit ulang; komisioner tidak dapat unlock.
- Admin bisa membuka form koreksi rekap TPS dari detail rekap admin dan menyimpan perubahan langsung. Rekap final tetap berstatus final setelah dikoreksi admin.
- Admin juga dapat mengubah nilai cell TPS langsung dari tabel detail rekap admin melalui endpoint inline update.
- Rekap PPS menampilkan agregasi desa.
- Rekap PPK menampilkan agregasi kecamatan.
- Rekap Admin/Komisioner menampilkan agregasi kabupaten, filter/level wilayah, summary, dan export.
- Admin dapat memberi atau menghapus penanda koreksi manual pada cell TPS dari halaman Rekapitulasi Data admin setelah memilih jenis pemilu, kecamatan, dan desa. Jika sebuah cell TPS ditandai, cell total desa untuk baris yang sama ikut merah, rekap PPK pada kolom desa ikut merah, dan rekap kabupaten pada kolom kecamatan ikut merah; PPS/PPK hanya melihat tanda tersebut.
- Export Excel tersedia untuk KPPS, PPS, PPK, Admin, dan Komisioner sesuai akses baca masing-masing.
- `RekapExportService` membuat export bertingkat ketika rekap final memenuhi syarat.
- `RekapAdminCache` menyimpan agregasi admin, data chart, dan ringkasan dashboard sesuai TTL `REKAP_CACHE_TTL_SECONDS` dengan default 30 detik. Cache dapat dimatikan dengan nilai `0` dan di-flush saat data berubah.
- `DashboardElectionSummary` membuat ringkasan pemenang dan caleg/partai teratas di dashboard sesuai scope user atau mode view admin.

### Grafik dan Peta

- Halaman grafik admin/komisioner/partai ada di `admin/rekap/chart`.
- Data grafik diambil lewat AJAX dari `admin/rekap/chart/data`.
- Mendukung grafik perolehan suara, partisipasi, pemenang wilayah, dan mode dapil untuk `dprd_kab`.
- Aset peta ada di `public/geojson`, termasuk kecamatan dan desa Banyuwangi. JavaScript halaman grafik ada di `public/js/rekap-admin-chart.js`.

### Dokumen

- KPPS upload dokumen PDF level TPS.
- PPK upload dokumen PDF level kecamatan.
- PPK memiliki form upload khusus di `/ppk/upload` untuk dokumen D-Hasil kecamatan.
- PPS/PPK/Admin/Komisioner dapat melihat dokumen sesuai role dan konteks wilayah.
- Verifikasi dokumen berjenjang dengan status `menunggu_verifikasi`, `terverifikasi`, dan `ditolak`.
- Penolakan dokumen menyimpan komentar/alasan.
- Preview dan download PDF tersedia melalui controller dengan guard akses.
- Dokumen dapat diarsipkan ke path backup dan direstore lewat CLI atau UI admin; komisioner hanya dapat melihat.

## Struktur Database

Migrasi utama saat ini mencakup:

- Wilayah dan user: `users`, `dapils`, `kecamatans`, `desas`, `tps`.
- Dokumen: `dokumens`.
- Setting pemilu: `pemilu_settings`.
- Master calon/partai: `rekap_ppwp_calons`, `rekap_gubernur_calons`, `rekap_bupati_calons`, `rekap_dpd_calons`, `rekap_partais`, `rekap_calegs`.
- Rekap: `rekap_headers`, `rekap_ppwp_suaras`, `rekap_gubernur_suaras`, `rekap_bupati_suaras`, `rekap_dpd_suaras`, `rekap_partai_suaras`, `rekap_caleg_suaras`.
- Penanda koreksi manual rekap: `rekap_cell_flags`.
- Infrastruktur Laravel: cache, jobs, dan tabel pendukung default.
- Index performa rekap ditambahkan pada migrasi `2026_05_20_*`.

Kolom penting `rekap_headers`:

- `tps_id`, `jenis`, `status`, `diinput_oleh`, `difinalisasi_at`.
- DPT, pengguna hak pilih, surat suara, disabilitas, dan `suara_tidak_sah`.
- Unique key `tps_id + jenis`.

Kolom penting `rekap_cell_flags`:

- `jenis`, `level`, `entity_id`, `row_key`.
- `level=tps` dipakai untuk menandai cell TPS spesifik. Tampilan agregat desa dan kecamatan diberi warna merah jika ada TPS turunannya yang ditandai pada `row_key` yang sama. `level=desa` masih dibaca sebagai kompatibilitas untuk tanda lama.
- Unique key `jenis + level + entity_id + row_key`.

Kolom penting `dokumens`:

- `tps_id`, `kecamatan_id`, `uploaded_by`, `verified_by`.
- `jenis`, `level`, `status`, `komentar`.
- `file_path`, `file_name`, `file_size`.
- `is_archived`, `archived_at`.

Kolom penting `users`:

- `role` mendukung `admin`, `komisioner`, `partai`, `ppk`, `pps`, dan `kpps`.
- `partai_id` dipakai untuk akun partai.
- `kecamatan_id`, `desa_id`, dan `tps_id` dipakai untuk scope PPK/PPS/KPPS.

## Seeder

| Seeder | Fungsi |
| --- | --- |
| `UserSeeder` | User awal aplikasi: admin/operator dan komisioner |
| `WilayahSeeder` | Data wilayah Banyuwangi |
| `PartaiSeeder` | Seed 18 partai untuk DPR RI, DPRD Provinsi, dan DPRD Kabupaten per dapil |
| `PemiluSettingSeeder` | Jenis pemilihan aktif/nonaktif |

Perintah umum:

```bash
php artisan db:seed
php artisan db:seed --class=PartaiSeeder
```

Credential default dari `UserSeeder`:

| Role | Username | Password |
| --- | --- | --- |
| Admin/Operator | `admin` | `admin123` |
| Komisioner | `komisioner` | `komisioner123` |

## Artisan Commands

```bash
# Backup dokumen PDF ke path backup
php artisan backup:dokumen
php artisan backup:dokumen --days=30
php artisan backup:dokumen --dry-run

# Restore dokumen arsip berdasarkan ID dokumen
php artisan restore:dokumen {id}

# Import data PPWP/DPD/DPR RI/DPRD historis dari folder Excel semua kecamatan
php artisan import:ppwp-folder "storage/import/PPWP" --dry-run
php artisan import:ppwp-folder "storage/import/PPWP"
php artisan import:dpd-folder "storage/import/DPD" --dry-run
php artisan import:dpd-folder "storage/import/DPD"
php artisan import:dpr-ri-folder "storage/import/DPR RI" --dry-run
php artisan import:dpr-ri-folder "storage/import/DPR RI"
php artisan import:dprd-prov-folder "storage/import/DPRD PROV" --dry-run
php artisan import:dprd-prov-folder "storage/import/DPRD PROV"
php artisan import:dprd-kab-folder "storage/import/DPRD KAB" --dry-run
php artisan import:dprd-kab-folder "storage/import/DPRD KAB"

# Tulis laporan detail import ke storage/app/import-reports
php artisan import:ppwp-folder "storage/import/PPWP" --dry-run --report

# Buat dokumen TPS dummy dari File_contoh.pdf untuk jenis pemilu aktif
php artisan seed:dummy-dokumen --status=menunggu_verifikasi
```

Scheduler menjalankan backup dokumen harian melalui `app/Console/Kernel.php`.

Jalankan command import dengan `--dry-run` terlebih dahulu untuk memvalidasi baris yang terbaca dan melihat koreksi otomatis sebelum menulis ke database. `import:ppwp-folder`, `import:dpd-folder`, `import:dpr-ri-folder`, `import:dprd-prov-folder`, dan `import:dprd-kab-folder` adalah helper data historis: satu file Excel mewakili satu kecamatan dan setiap sheet mewakili satu desa. Command ini memakai nama sheet sebagai nama desa utama, melewati sheet pembuka tanpa TPS, dan menerima opsi `--only=NAMA_KECAMATAN`, `--desa=NAMA_DESA`, serta `--report[=path]`. Untuk `dprd_kab`, master partai/caleg dipisah per dapil sehingga kecamatan yang diimport harus sudah memiliki dapil.

## Routes Penting

```php
// Auth
GET  /                         login
POST /login                    login.post
GET  /partai/login             partai.login
POST /partai/login             partai.login.post
POST /logout                   logout
GET  /password                 password.edit
POST /password                 password.update

// Dashboard
GET /dashboard/admin
GET /dashboard/komisioner
GET /dashboard/partai
GET /dashboard/ppk
GET /dashboard/pps
GET /dashboard/kpps

// Dokumen
GET  /dokumen/upload
POST /dokumen/upload
GET  /dokumen/verifikasi
POST /dokumen/{dokumen}/verifikasi
GET  /dokumen/kecamatan
GET  /dokumen/semua
POST /dokumen/{dokumen}/verifikasi-admin
POST /dokumen/{dokumen}/restore
GET  /dokumen/{dokumen}/preview
GET  /dokumen/{dokumen}/download
GET  /ppk/upload
POST /ppk/upload
GET  /ppk/data-pps
GET  /ppk/view-pps/{desa}
GET  /pps/data-tps
GET  /pps/view-tps/{tps}

// Admin user & wilayah
GET    /admin/users
POST   /admin/users
PUT    /admin/users/{user}
DELETE /admin/users/{user}
GET    /admin/users/bulk
POST   /admin/users/bulk
GET    /admin/users/export
GET    /admin/kecamatan
GET    /admin/kecamatan/{kecamatan}/view
GET    /admin/desa
GET    /admin/desa/{desa}/view
GET    /admin/tps
GET    /admin/tps/{tps}/view

// Admin setup
GET    /admin/setup
POST   /admin/setup/pemilu-settings
POST   /admin/setup/ppwp
POST   /admin/setup/gubernur
POST   /admin/setup/bupati
POST   /admin/setup/dpd
POST   /admin/setup/partai
POST   /admin/setup/partai/{partai}/caleg
POST   /admin/setup/dapil
POST   /admin/setup/kecamatan-dapil

// Rekap KPPS
GET  /rekap
GET  /rekap/{jenis}
POST /rekap/{jenis}
POST /rekap/{jenis}/finalisasi
GET  /rekap/{jenis}/export

// Rekap PPS dan PPK
GET /pps/rekap
GET /pps/rekap/{jenis}
GET /pps/rekap/{jenis}/export
GET /ppk/rekap
GET /ppk/rekap/{jenis}
GET /ppk/rekap/{jenis}/export

// Rekap Admin/Komisioner/Partai
GET  /admin/rekap
GET  /admin/rekap/chart
GET  /admin/rekap/chart/data
GET  /admin/rekap/export/download
POST /admin/rekap/{jenis}/cell-flag  admin only
POST /admin/rekap/{jenis}/inline-update  admin only
POST /admin/rekap/{jenis}/unlock   admin only
GET  /admin/rekap/{jenis}/edit-tps/{tps}   admin only
GET  /admin/rekap/{jenis}/export
GET  /admin/rekap/{jenis}
```

## File Kunci

```text
app/
  Console/
    Kernel.php
    Commands/BackupDokumen.php
    Commands/RestoreDokumen.php
    Commands/ImportPpwpFolder.php
    Commands/ImportDpdFolder.php
    Commands/ImportDprRiFolder.php
    Commands/ImportDprdProvFolder.php
    Commands/ImportDprdKabFolder.php
    Commands/SeedDummyDokumen.php
    Commands/Concerns/WritesImportReport.php
  Http/
    Controllers/
      AuthController.php
      AccountController.php
      DashboardController.php
      DokumenController.php
      PpkController.php
      PpsController.php
      Admin/
        SetupController.php
        ToolsController.php
        KecamatanController.php
        DesaController.php
        TpsController.php
        UserManagementController.php
      Rekap/
        KppsController.php
        PpsController.php
        PpkController.php
        AdminController.php
    Middleware/RoleMiddleware.php
  Models/
    RekapHeader.php
    RekapCellFlag.php
    PemiluSetting.php
    Dokumen.php
    Dapil.php
    Kecamatan.php
    Desa.php
    Tps.php
    User.php
  Services/
    DashboardElectionSummary.php
    RekapAdminCache.php
    RekapExportService.php
  Exports/
    RekapExport.php
    RekapSheetExport.php
    RekapTotalSheetExport.php

resources/views/
  layouts/
  auth/
  dashboard/
  admin/setup/
  admin/wilayah/
  admin/tps/
  admin/users/
  dokumen/
  rekap/
    kpps/
    pps/
    ppk/
    admin/
  errors/

public/
  images/logo-kpu.png
  js/rekap-admin-chart.js
  geojson/
```

## Environment

```env
APP_DEBUG=true|false

# Path folder backup dokumen PDF
BACKUP_DOKUMEN_PATH=E:\Backup\SIMAP

# TTL cache agregasi/chart/dashboard rekap dalam detik, 0 untuk nonaktif
REKAP_CACHE_TTL_SECONDS=30
```

`config/filesystems.php` membaca fallback backup ke `storage_path('app/backup')` jika `BACKUP_DOKUMEN_PATH` tidak diisi.
`config/rekap.php` membaca `REKAP_CACHE_TTL_SECONDS` dan default ke 30 detik.

## Perintah Development

```bash
composer install
npm install
php artisan migrate --seed
npm run dev
php artisan serve

# Test aplikasi
composer test

# Build asset
npm run build
```

## Catatan Maintenance

- `PROJECT.md` sudah dihapus karena duplikat dan lebih lama dari `SIMAP.md`.
- Role `komisioner` dan `partai` bersifat read-only dan memakai sebagian route admin untuk baca data; aksi tulis tetap dipisahkan pada route/controller admin-only.
- Manajemen pengguna dapat membuat akun admin/operator dan partai. Pastikan minimal satu akun admin/operator tetap tersedia untuk mencegah lockout.
- Ada file contoh desain lokal yang belum menjadi bagian dokumentasi utama: `Contoh_design_grafik*.html`.
