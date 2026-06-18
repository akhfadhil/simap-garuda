# SIMAP Garuda Operasional

Dokumen ini menjadi panduan operasional SIMAP Garuda untuk setup, penggunaan harian, pengelolaan data, verifikasi, export, dan deployment.

SIMAP Garuda adalah aplikasi mandiri untuk Partai Garuda. Aplikasi ini tidak membaca database SIMAP utama saat runtime dan tidak membuka data partai lain. Sumber data suara di fase ini adalah input manual oleh Saksi TPS, Kordes, Korcam, atau Admin Partai sesuai scope wilayah.

## Ringkasan Aplikasi

| Item | Nilai |
| --- | --- |
| Nama aplikasi | SIMAP Garuda |
| Folder project | `simap-garuda` |
| Database default | `simap_garuda` |
| Partai | Partai Garuda |
| Slug permanen | `garuda` |
| Nomor historis 2024 | `11` |
| Jenis rekap aktif | DPR RI, DPRD Provinsi, DPRD Kabupaten |
| Jalur input data | Form manual |
| Import Excel/CSV/JSON | Tidak dipakai di fase ini |
| Runtime ke SIMAP utama | Tidak ada |

Nomor urut `11` hanya metadata historis Pemilu 2024. Identitas permanen aplikasi tetap memakai slug `garuda` dan konfigurasi `config/party.php`.

## Konsep Utama

SIMAP Garuda memakai model satu aplikasi untuk satu partai. Artinya:

- Semua dashboard, rekap, chart, dan export difokuskan ke Partai Garuda.
- Master partai hanya boleh berisi Partai Garuda untuk kebutuhan input dan laporan.
- Caleg hanya boleh dibuat di bawah Partai Garuda.
- Partai kompetitor tidak ditampilkan di form input, dashboard, chart, atau export.
- Admin tidak boleh menambah partai selain Partai Garuda lewat halaman setup.
- Data internal SIMAP utama seperti dokumen, verifikasi KPU, backup dokumen, import command, dan rekap non-legislatif tidak dipakai.

## Role dan Scope Akses

| Role DB | Nama UI | Scope Data | Fungsi Utama |
| --- | --- | --- | --- |
| `admin_partai` | Admin Partai | Semua kecamatan, desa, TPS | Setup master, kelola user, monitoring kabupaten, koreksi lintas wilayah, export |
| `korcam` | Korcam | Satu kecamatan | Monitoring desa/TPS dalam kecamatan, input/edit TPS dalam kecamatan, export kecamatan |
| `kordes` | Kordes | Satu desa | Monitoring TPS dalam desa, input/edit TPS dalam desa, export desa |
| `saksi_tps` | Saksi TPS | Satu TPS | Input suara TPS, simpan draft, finalisasi, export TPS |

Role legacy `komisioner` dan `partai` ditolak saat login. URI lama `ppk`, `pps`, dan `kpps` masih ada sebagai backward redirect sementara, tetapi penggunaan normal harus memakai istilah `korcam`, `kordes`, dan `saksi`.

## Jenis Rekap

Jenis rekap resmi:

| Key | Label |
| --- | --- |
| `dpr_ri` | DPR RI |
| `dprd_prov` | DPRD Provinsi |
| `dprd_kab` | DPRD Kabupaten |

Jenis `ppwp`, `dpd`, `gubernur`, dan `bupati` tidak tersedia di runtime SIMAP Garuda. Route, view, setup, model, dan tabel rekap non-legislatif sudah dibersihkan dari aplikasi.

## URL Utama

| Kebutuhan | URL |
| --- | --- |
| Login | `/` |
| Ubah password | `/password` |
| Dashboard Admin Partai | `/dashboard/admin-partai` |
| Dashboard Korcam | `/dashboard/korcam` |
| Dashboard Kordes | `/dashboard/kordes` |
| Dashboard Saksi TPS | `/dashboard/saksi` |
| Rekap input TPS | `/rekap` |
| Admin setup | `/admin/setup` |
| Admin user | `/admin/users` |
| Admin kecamatan | `/admin/kecamatan` |
| Admin desa | `/admin/desa` |
| Admin TPS | `/admin/tps` |
| Admin rekap | `/admin/rekap` |
| Admin chart | `/admin/rekap/chart` |

## Setup Fresh Clone

### 1. Prasyarat

Pastikan server atau lokal development memiliki:

- PHP 8.2 atau lebih baru.
- Composer.
- Node.js dan npm.
- MySQL atau MariaDB.
- Ekstensi PHP umum Laravel: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, dan `zip`.

### 2. Install dependency

```bash
composer install
npm install
```

### 3. Siapkan environment

```bash
cp .env.example .env
php artisan key:generate
```

Konfigurasi minimal `.env`:

```env
APP_NAME="SIMAP Garuda"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://simap-garuda.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simap_garuda
DB_USERNAME=root
DB_PASSWORD=
```

Untuk production:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` memakai domain final.
- `DB_USERNAME` dan `DB_PASSWORD` memakai user database khusus.
- Jangan memakai akun database root.

### 4. Buat database

Buat database kosong:

```sql
CREATE DATABASE simap_garuda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Jalankan migration

```bash
php artisan migrate
```

Jika tersedia seeder project, jalankan:

```bash
php artisan db:seed
```

### 6. Build asset

Development:

```bash
npm run dev
```

Production:

```bash
npm run build
```

### 7. Jalankan aplikasi lokal

Dengan Laragon, arahkan host `simap-garuda.test` ke folder `public`.

Alternatif development:

```bash
php artisan serve
```

## Setup Data Awal

Urutan setup data yang disarankan:

1. Login sebagai Admin Partai.
2. Isi master kecamatan.
3. Isi master desa dan hubungkan ke kecamatan.
4. Isi master TPS dan hubungkan ke desa.
5. Isi master dapil.
6. Hubungkan kecamatan ke dapil jika DPRD Kabupaten dipakai.
7. Aktifkan jenis rekap legislatif yang dipakai di `Admin > Setup`.
8. Tambahkan caleg Partai Garuda pada masing-masing jenis/dapil. Master Partai Garuda dibuat otomatis oleh sistem sesuai konfigurasi aplikasi.
9. Buat akun Korcam, Kordes, dan Saksi TPS sesuai wilayah.

## Konfigurasi Identitas Partai

Identitas Partai Garuda berada di:

```text
config/party.php
```

File ini mengatur:

- `slug`
- `name`
- `short_name`
- `historical_numbers`
- `logo_path`
- warna utama dan aksen
- label role

Logo berada di:

```text
public/images/logo-garuda.png
```

Perubahan identitas sebaiknya lewat config, bukan hardcode di view/controller.

## Penggunaan Admin Partai

Admin Partai adalah role tertinggi di SIMAP Garuda.

### Dashboard

Dashboard Admin Partai menampilkan:

- Total suara Partai Garuda.
- Ranking caleg Garuda.
- Progres TPS masuk.
- TPS belum masuk.
- TPS perlu dicek internal.
- Wilayah kuat dan lemah.
- Ringkasan jenis rekap legislatif aktif.

Dashboard hanya mengambil data DPR RI, DPRD Provinsi, dan DPRD Kabupaten yang aktif.

### Kelola Wilayah

Menu wilayah:

- `Admin > Kecamatan`
- `Admin > Desa`
- `Admin > TPS`

Aturan pengisian:

- Kecamatan dibuat terlebih dahulu.
- Desa wajib punya kecamatan.
- TPS wajib punya desa.
- Hindari menghapus wilayah yang sudah punya user atau rekap, kecuali memang sedang reset data.

### Kelola Dapil

Dapil dipakai terutama untuk DPRD Kabupaten.

Alur:

1. Buat dapil di `Admin > Setup`.
2. Assign kecamatan ke dapil.
3. Tambahkan caleg Garuda untuk dapil terkait.
4. Sistem otomatis membuat master Partai Garuda untuk jenis/dapil tersebut jika belum ada.

### Kelola Caleg

Admin hanya mengisi caleg Partai Garuda. Admin tidak perlu mengisi master partai secara manual karena SIMAP Garuda adalah aplikasi satu partai.

Validasi utama:

- Identitas Partai Garuda diambil dari `config/party.php`.
- Nomor historis Garuda 2024 (`11`) hanya metadata untuk matching data periode 2024, bukan identitas permanen.
- Master Partai Garuda dibuat otomatis saat admin menambahkan caleg pada DPR RI, DPRD Provinsi, atau DPRD Kabupaten.
- Caleg hanya bisa masuk ke master Partai Garuda.
- Partai kompetitor tetap ditolak oleh sistem.

Perilaku halaman setup:

- Tambah caleg memakai AJAX, sehingga halaman tidak reload dan posisi scroll tidak kembali ke atas.
- Caleg yang berhasil ditambahkan langsung muncul di daftar jika panel partai/dapil sedang tersedia.
- Hapus caleg memakai dialog konfirmasi custom. Jika memilih `Batal`, sistem tidak mengirim request hapus. Jika memilih `Hapus`, baris caleg hilang tanpa reload.
- Counter jumlah caleg ikut diperbarui saat tambah atau hapus caleg.

### Kelola User

Menu:

```text
Admin > Users
```

Prinsip pembuatan user:

- `admin_partai`: tidak perlu scope wilayah.
- `korcam`: wajib punya `kecamatan_id`.
- `kordes`: wajib punya `desa_id`.
- `saksi_tps`: wajib punya `tps_id`.

Pastikan satu akun hanya diberi scope sesuai tugasnya. Jangan memberi akun Korcam ke kecamatan yang bukan tanggung jawabnya.

### View Sebagai Wilayah

Admin bisa masuk ke mode view wilayah:

- View kecamatan sebagai Korcam.
- View desa sebagai Kordes.
- View TPS sebagai Saksi TPS.

Mode ini dipakai untuk monitoring atau koreksi data lintas wilayah. Setelah selesai, gunakan route clear view session jika perlu kembali ke scope normal.

### Koreksi Status Internal

Admin Partai bisa menandai TPS sebagai:

- `draft`
- `perlu_dicek`
- `final`

Status `perlu_dicek` dipakai untuk TPS yang perlu verifikasi internal, misalnya angka perlu dicocokkan ulang dengan foto C1 atau laporan saksi.

Catatan internal bisa diisi agar alasan koreksi jelas.

Ada dua jalur koreksi status internal:

1. Dari form input rekap TPS saat Admin Partai sedang melihat konteks TPS tertentu.
2. Dari `Admin > Rekapitulasi Data`, pilih jenis pemilihan, pilih kecamatan dan desa pada bagian detail wilayah, lalu gunakan baris `Tandai TPS` pada tabel TPS.

Pada jalur `Admin > Rekapitulasi Data`, tombol `Perlu Dicek` dan `Clear` berjalan tanpa reload halaman. Jika TPS yang sudah final ditandai `perlu_dicek`, tombol `Clear` akan mengembalikan statusnya ke `final`. Jika TPS belum final, `Clear` mengembalikan statusnya ke `draft`.

## Penggunaan Korcam

Korcam bertanggung jawab pada satu kecamatan.

Fitur utama:

- Melihat daftar Kordes/desa dalam kecamatan.
- Melihat daftar TPS melalui desa di kecamatan sendiri.
- Membuka rekap kecamatan.
- Menginput atau mengedit suara TPS dalam kecamatan sendiri.
- Export rekap kecamatan.

Batasan:

- Tidak bisa membuka desa/TPS di kecamatan lain.
- Tidak bisa mengelola master global.
- Tidak bisa membuat partai/caleg.
- Tidak bisa melihat data di luar scope kecamatan.

Alur kerja Korcam:

1. Login.
2. Buka dashboard Korcam.
3. Cek progres desa dan TPS.
4. Masuk ke desa/TPS yang perlu diisi atau dicek.
5. Input/edit suara jika masih dalam scope.
6. Pantau rekap kecamatan.
7. Export jika dibutuhkan.

## Penggunaan Kordes

Kordes bertanggung jawab pada satu desa.

Fitur utama:

- Melihat daftar TPS dalam desa.
- Membuka rekap desa.
- Menginput atau mengedit suara TPS dalam desa sendiri.
- Finalisasi data TPS jika sudah benar.
- Export rekap desa.

Batasan:

- Tidak bisa membuka TPS di desa lain.
- Tidak bisa mengelola master global.
- Tidak bisa menambah partai/caleg.
- Tidak bisa mengubah data final jika aturan proteksi final menghalangi non-admin.

Alur kerja Kordes:

1. Login.
2. Buka dashboard Kordes.
3. Cek TPS yang belum masuk.
4. Buka TPS.
5. Isi suara Partai Garuda dan caleg.
6. Simpan draft jika angka belum final.
7. Finalisasi jika angka sudah benar.
8. Export rekap desa jika dibutuhkan.

## Penggunaan Saksi TPS

Saksi TPS bertanggung jawab pada satu TPS.

Fitur utama:

- Input suara Partai Garuda.
- Input suara caleg Garuda.
- Simpan draft.
- Finalisasi.
- Export rekap TPS.

Batasan:

- Tidak bisa membuka TPS lain.
- Tidak bisa melihat data wilayah lain.
- Tidak bisa menginput partai atau caleg kompetitor.
- Tidak bisa mengubah rekap final jika proteksi final berlaku.

Alur kerja Saksi TPS:

1. Login.
2. Masuk ke menu rekap.
3. Pilih jenis rekap: DPR RI, DPRD Provinsi, atau DPRD Kabupaten.
4. Isi suara Partai Garuda.
5. Isi suara setiap caleg Garuda.
6. Simpan sebagai draft jika masih perlu dicek.
7. Finalisasi jika angka sudah benar.
8. Export rekap TPS jika perlu arsip.

Form input tidak meminta data KPU seperti DPT, surat suara, disabilitas, atau suara tidak sah. Field administratif lama diisi `0` oleh sistem agar schema tetap kompatibel.

## Status Rekap

| Status | Arti | Penggunaan |
| --- | --- | --- |
| `draft` | Data sudah masuk tetapi belum final | Dipakai saat angka masih perlu dicek |
| `perlu_dicek` | Data perlu verifikasi internal | Dipakai Admin Partai untuk menandai TPS bermasalah |
| `final` | Data sudah dikunci sebagai final | Dipakai setelah angka dianggap benar |

Rekomendasi operasional:

- Gunakan `draft` untuk input awal.
- Gunakan `perlu_dicek` jika ada selisih, laporan ganda, foto belum cocok, atau catatan saksi.
- Gunakan `final` hanya setelah angka diverifikasi.

## Export Laporan

Export yang tersedia:

- Rekap TPS.
- Rekap desa.
- Rekap kecamatan.
- Rekap kabupaten/admin.
- TPS belum masuk.
- TPS perlu dicek.
- Rekap chart/export per jenis legislatif.

Isi export difokuskan pada:

- Suara Partai Garuda.
- Suara caleg Garuda.
- Total suara Garuda.
- Status input TPS.
- Catatan yang relevan untuk internal partai.

Export tidak membawa:

- Data partai kompetitor.
- DPT.
- Pengguna hak pilih.
- Surat suara.
- Disabilitas.
- Suara tidak sah.
- Dokumen internal SIMAP utama.
- Log verifikasi KPU.

## Dashboard dan Monitoring

Dashboard dipakai untuk memantau:

- Total suara Garuda.
- Total suara caleg.
- Peringkat caleg.
- Persentase TPS masuk.
- Jumlah TPS belum masuk.
- Jumlah TPS perlu dicek.
- Wilayah kuat.
- Wilayah lemah.

Admin Partai melihat seluruh wilayah. Korcam melihat kecamatan sendiri. Kordes melihat desa sendiri. Saksi TPS hanya melihat TPS sendiri.

## Data Yang Tidak Dipakai

SIMAP Garuda tidak memakai:

- Login multi-partai.
- Role `partai`.
- Role `komisioner`.
- Modul dokumen/verifikasi internal.
- Backup/restore dokumen internal.
- Command import Excel legacy.
- Rekap PPWP.
- Rekap DPD.
- Rekap Gubernur.
- Rekap Bupati.
- Tabel/model suara non-legislatif.

Jika fitur import snapshot dari SIMAP utama nanti dibutuhkan, fitur itu harus dibuat sebagai alur baru yang aman dan terdokumentasi, bukan menghidupkan kembali command import lama.

## Checklist Operasional Harian

Untuk Admin Partai:

- Cek dashboard kabupaten.
- Cek TPS belum masuk.
- Cek TPS perlu dicek.
- Follow up Korcam/Kordes pada wilayah yang belum masuk.
- Export laporan berkala.
- Pastikan tidak ada akun dengan scope kosong atau salah wilayah.

Untuk Korcam:

- Cek progres semua desa dalam kecamatan.
- Follow up Kordes pada desa yang TPS-nya belum masuk.
- Input atau koreksi TPS dalam kecamatan jika diperlukan.
- Export rekap kecamatan untuk laporan internal.

Untuk Kordes:

- Cek semua TPS dalam desa.
- Follow up Saksi TPS yang belum input.
- Input atau koreksi TPS dalam desa jika diperlukan.
- Finalisasi data yang sudah benar.

Untuk Saksi TPS:

- Input angka suara dari TPS.
- Simpan draft jika belum pasti.
- Finalisasi setelah angka benar.
- Laporkan ke Kordes jika ada selisih atau kendala.

## Checklist Sebelum Hari Input

- Semua kecamatan sudah masuk.
- Semua desa sudah masuk dan terhubung ke kecamatan.
- Semua TPS sudah masuk dan terhubung ke desa.
- Dapil sudah dibuat jika DPRD Kabupaten dipakai.
- Kecamatan sudah diassign ke dapil.
- Caleg Garuda sudah lengkap untuk setiap jenis rekap aktif.
- Master Partai Garuda sudah otomatis tersedia dari proses tambah caleg.
- Akun Admin Partai, Korcam, Kordes, dan Saksi TPS sudah dibuat.
- Setiap akun wilayah sudah punya scope yang benar.
- Login tiap role sudah diuji.
- Form rekap tiap jenis sudah bisa dibuka.
- Export dasar sudah diuji.

## Checklist Setelah Input

- Tidak ada TPS yang seharusnya masuk tetapi masih kosong.
- TPS `perlu_dicek` sudah ditindaklanjuti.
- Rekap final sudah sesuai laporan internal.
- Export TPS belum masuk sudah kosong atau sudah dijelaskan.
- Export TPS perlu dicek sudah kosong atau sudah punya catatan.
- Laporan kabupaten/kecamatan/desa sudah diexport dan diarsipkan.
- Backup database dilakukan setelah data penting masuk.

## Deployment Production

Langkah umum:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan web server mengarah ke folder:

```text
public
```

Permission storage:

```bash
php artisan storage:link
```

Folder berikut harus writable oleh user web server:

```text
storage
bootstrap/cache
```

## Backup dan Restore

Backup database harus dilakukan di level MySQL/MariaDB, bukan lewat modul dokumen lama.

Contoh backup:

```bash
mysqldump -u USER -p simap_garuda > simap_garuda_backup.sql
```

Contoh restore:

```bash
mysql -u USER -p simap_garuda < simap_garuda_backup.sql
```

Rekomendasi:

- Backup sebelum migration production.
- Backup setelah input besar.
- Simpan backup di lokasi terpisah dari server aplikasi.
- Batasi akses backup karena berisi user dan data rekap.

## Verifikasi Teknis

Setelah perubahan kode:

```bash
php artisan test
npm run build
git diff --check
```

Untuk cek route aktif:

```bash
php artisan route:list --except-vendor
```

Untuk cek migration pending:

```bash
php artisan migrate:status
```

## Troubleshooting

### Tidak bisa login

Cek:

- Username benar.
- Password benar.
- Role user termasuk `admin_partai`, `korcam`, `kordes`, atau `saksi_tps`.
- Role legacy `komisioner` dan `partai` memang ditolak.
- Session/cache tidak rusak.

Command yang bisa dicoba:

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### User wilayah tidak melihat data

Cek:

- User Korcam punya `kecamatan_id`.
- User Kordes punya `desa_id`.
- User Saksi TPS punya `tps_id`.
- Desa terhubung ke kecamatan yang benar.
- TPS terhubung ke desa yang benar.

### Form rekap kosong

Cek:

- Jenis rekap aktif di setup.
- Master Partai Garuda sudah dibuat untuk jenis tersebut.
- Caleg Garuda sudah dibuat.
- Untuk DPRD Kabupaten, dapil dan mapping kecamatan ke dapil sudah benar.

### Export gagal

Cek:

- Folder `storage` writable.
- Dependency composer sudah terinstall.
- Tidak ada file cache export lama yang terkunci.
- Jalankan `php artisan cache:clear` jika perlu.

### Migration gagal

Cek:

- Database aktif.
- Kredensial `.env` benar.
- User database punya permission `CREATE`, `ALTER`, `DROP`, `INDEX`, `INSERT`, `UPDATE`, dan `DELETE`.
- Backup sudah dibuat sebelum menjalankan migration production.

### Data partai lain muncul

Ini tidak normal untuk SIMAP Garuda. Cek:

- Master `rekap_partais` tidak berisi kompetitor untuk alur aktif.
- Query baru harus memakai scope/filter Garuda.
- Jangan menambah fitur yang menampilkan ranking semua partai tanpa keputusan product.

## Batasan Saat Ini

- Import snapshot dari SIMAP utama belum menjadi fitur final.
- Seed/demo data khusus Garuda belum difinalkan.
- Backward redirect `ppk`, `pps`, dan `kpps` masih tersedia sementara.
- Enum historis jenis non-partai masih tersisa di migration/schema lama untuk kompatibilitas, tetapi runtime non-partai tidak aktif.
- Dokumentasi template partai generik belum diekstrak dari SIMAP Garuda.

## Rekomendasi Pengembangan Berikutnya

1. Audit fitur generik SIMAP Garuda untuk dipromosikan ke `simap-partai-template`.
2. Tandai bagian yang masih spesifik Garuda agar bisa diparameterkan.
3. Siapkan format snapshot dari SIMAP utama jika kebutuhan import muncul.
4. Tambahkan seed/demo data Garuda untuk testing internal.
5. Bersihkan backward route legacy setelah masa kompatibilitas selesai.
