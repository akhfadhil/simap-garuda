# SIMAP Garuda

SIMAP Garuda adalah aplikasi Laravel mandiri untuk Partai Garuda. Project ini dipakai untuk input manual, monitoring, rekapitulasi, grafik, dan export perolehan suara Partai Garuda dan caleg-calegnya.

Project ini bukan portal multi-partai dan tidak lagi membawa alur internal SIMAP/KPU. Data suara masuk dari form manual Saksi TPS, lalu dipantau berjenjang oleh Kordes, Korcam, dan Admin Partai.

## Identitas Aplikasi

| Item | Nilai |
| --- | --- |
| Nama aplikasi | SIMAP Garuda |
| Folder project | `simap-garuda` |
| Database | `simap_garuda` |
| Partai | Partai Garuda |
| Slug permanen | `garuda` |
| Nomor historis Pemilu 2024 | `11` |
| Konfigurasi identitas | `config/party.php` |
| Logo | `public/images/logo-garuda.png` |

Nomor urut `11` hanya metadata historis untuk Pemilu 2024, bukan identitas permanen Partai Garuda.

## Stack

| Layer | Teknologi |
| --- | --- |
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Blade, Tailwind CSS 4, Flowbite 4, Vite 7 |
| Database | MySQL/MariaDB |
| Auth | Session auth Laravel + `RoleMiddleware` |
| Export | Maatwebsite Excel |
| Grafik | Chart.js |
| Peta | GeoJSON Banyuwangi di `public/geojson` |
| Cache rekap | Cache Laravel, dikonfigurasi lewat `config/rekap.php` |

## Role

Nilai role database sudah memakai istilah teknis Partai Garuda. URI publik utama juga memakai istilah partai; URI lama `ppk`, `pps`, dan `kpps` masih tersedia sebagai backward redirect sementara.

| Role DB | Istilah UI | Scope |
| --- | --- | --- |
| `admin_partai` | Admin Partai | Semua wilayah |
| `korcam` | Korcam | Satu kecamatan |
| `kordes` | Kordes | Satu desa |
| `saksi_tps` | Saksi TPS | Satu TPS |

Role legacy `komisioner` dan `partai` tidak boleh login ke SIMAP Garuda.

## Jenis Rekap Resmi

SIMAP Garuda hanya memantau pemilihan legislatif:

| Key | Label |
| --- | --- |
| `dpr_ri` | DPR RI |
| `dprd_prov` | DPRD Provinsi |
| `dprd_kab` | DPRD Kabupaten |

Jenis PPWP, DPD, Gubernur, dan Bupati sudah dinonaktifkan dari aplikasi dan dibersihkan lewat migration `2026_06_14_000002_remove_non_party_rekap_data`.

## Kebijakan Data

- Data suara hanya untuk Partai Garuda dan caleg Partai Garuda.
- Partai kompetitor tidak ditampilkan di setup, form input, rekap berjenjang, chart, dashboard, atau export.
- Admin tidak bisa menambah partai selain Partai Garuda dari setup.
- Caleg hanya bisa ditambahkan ke master Partai Garuda.
- Request input manual yang membawa ID partai/caleg kompetitor ditolak.
- SIMAP Garuda tidak memakai command import Excel/CSV/JSON.
- Jalur input suara resmi hanya form manual Saksi TPS.

## Fitur Utama

### Autentikasi

- Login/logout berbasis session.
- Redirect dashboard sesuai role.
- Ubah password akun sendiri melalui `/password`.
- Error page custom untuk akses/route/error umum.

### Admin Partai

- Kelola kecamatan, desa, TPS.
- Kelola pengguna Admin Partai, Korcam, Kordes, dan Saksi TPS.
- Setup jenis rekap legislatif aktif.
- Setup dapil dan assign kecamatan ke dapil.
- Setup master Partai Garuda dan caleg Garuda.
- Melihat dashboard, grafik, rekap kabupaten, dan export.
- Mode view sebagai Korcam, Kordes, atau Saksi TPS untuk monitoring wilayah.

### Korcam

- Melihat data Kordes/TPS dalam kecamatan.
- Melihat rekap kecamatan.
- Export rekap kecamatan.

### Kordes

- Melihat data TPS dalam desa.
- Melihat rekap desa.
- Export rekap desa.

### Saksi TPS

- Input manual rekap TPS untuk DPR RI, DPRD Provinsi, dan DPRD Kabupaten.
- Simpan draft.
- Finalisasi rekap.
- Export rekap TPS.

## Alur Input Rekap

1. Admin menyiapkan master wilayah, TPS, dapil, Partai Garuda, dan caleg.
2. Admin membuat akun Saksi TPS, Kordes, dan Korcam sesuai wilayah.
3. Saksi TPS mengisi suara Partai Garuda dan caleg Garuda dari form rekap.
4. Saksi TPS menyimpan draft atau finalisasi data.
5. Kordes, Korcam, dan Admin Partai memantau progres dan agregasi.
6. Export dibuat dari data manual yang sudah masuk.

## Struktur Data Penting

| Tabel | Fungsi |
| --- | --- |
| `users` | Akun dan role pengguna |
| `dapils` | Master dapil |
| `kecamatans` | Master kecamatan |
| `desas` | Master desa |
| `tps` | Master TPS |
| `pemilu_settings` | Jenis rekap legislatif aktif |
| `rekap_partais` | Master Partai Garuda per jenis/dapil |
| `rekap_calegs` | Master caleg Partai Garuda |
| `rekap_headers` | Header rekap TPS per jenis |
| `rekap_partai_suaras` | Suara Partai Garuda |
| `rekap_caleg_suaras` | Suara caleg Garuda |
| `rekap_cell_flags` | Penanda koreksi/manual check jika masih dipakai |

Kolom legacy `users.partai_id` sudah diaudit dan dihapus dari schema user SIMAP Garuda lewat migration `2026_06_17_000001_drop_legacy_partai_id_from_users_table`.

## Artisan dan Development

```bash
composer install
npm install
php artisan migrate --seed
npm run dev
php artisan serve
```

Test:

```bash
php artisan test
```

Build asset:

```bash
npm run build
```

Format PHP:

```bash
vendor/bin/pint
```

## Catatan Penting

- Jangan menambah kembali command import tanpa perubahan keputusan product.
- Jangan membuka tampilan partai kompetitor di SIMAP Garuda.
- Jangan membuat koneksi runtime ke database SIMAP utama.
- Data Garuda harus berdiri sendiri di database `simap_garuda`.
- Checklist kerja lanjutan ada di `SIMAP_GARUDA_PHASE_CHECKLIST.md`.
