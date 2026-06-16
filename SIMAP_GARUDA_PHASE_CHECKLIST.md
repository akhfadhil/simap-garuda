# SIMAP Garuda Phase Checklist

Dokumen ini menjadi checklist kerja SIMAP Garuda berdasarkan `PARTAI_PORTAL_BRAINSTORM.md`.

## Status Phase 1

Target Phase 1: fork SIMAP menjadi aplikasi mandiri untuk Partai Garuda, database terpisah, role hierarchy partai, input manual TPS, dan fitur internal SIMAP/KPU dibersihkan.

### Sudah Sesuai

- [x] Project berjalan sebagai folder mandiri `simap-garuda`.
- [x] `.env` memakai `APP_NAME="SIMAP Garuda"`.
- [x] `.env` memakai `APP_URL=http://simap-garuda.test`.
- [x] `.env` memakai database `simap_garuda`.
- [x] Login partai lama dihapus karena project ini sendiri sudah khusus Partai Garuda.
- [x] Role legacy `komisioner` dan `partai` ditolak saat login.
- [x] Migration pembersih role legacy dibuat.
- [x] Modul dokumen/verifikasi internal SIMAP utama dihapus dari route, controller, model, command, dan view.
- [x] Backup/restore dokumen internal dihapus.
- [x] Menu dan layout utama diganti ke konteks SIMAP Garuda.
- [x] UI memakai istilah Admin Partai, Korcam, Kordes, dan Saksi TPS.
- [x] Manajemen user dibatasi ke Admin Partai, Korcam, Kordes, dan Saksi TPS.
- [x] Struktur wilayah kecamatan, desa, dan TPS dipertahankan.
- [x] Input manual rekap TPS masih tersedia untuk Saksi TPS.
- [x] Agregasi rekap tingkat desa, kecamatan, dan kabupaten masih tersedia.
- [x] Export Excel rekap masih tersedia.
- [x] Test akses role Garuda tersedia.
- [x] `php artisan test` lulus.
- [x] `npm.cmd run build` lulus.

### Belum 100% Bersih

- [ ] Nilai role teknis database masih `admin`, `ppk`, `pps`, `kpps`; belum menjadi `admin_partai`, `korcam`, `kordes`, `saksi_tps`.
- [ ] URI dan nama route teknis masih memakai `ppk`, `pps`, `kpps`.
- [ ] Kolom `users.partai_id` masih ada sebagai sisa schema lama, walau tidak dipakai di manajemen user.
- [ ] Beberapa method controller koreksi internal admin masih ada, walau route publiknya sudah dilepas.
- [x] Setup pemilu disederhanakan ke DPR RI, DPRD Provinsi, dan DPRD Kabupaten.
- [ ] Dashboard masih memakai fondasi dashboard SIMAP dan belum fokus penuh pada performa Partai Garuda saja.
- [ ] Master partai masih multi-partai; belum difilter/diubah menjadi konfigurasi Partai Garuda permanen dengan slug `garuda`.
- [x] Logo Garuda sudah tersedia sebagai asset lokal dan dipakai lewat konfigurasi `config/party.php`.

## Keputusan Phase 1

Phase 1 dapat dianggap selesai secara MVP operasional jika targetnya adalah:

- aplikasi sudah berdiri sendiri,
- fitur KPU/dokumen internal sudah tidak dapat diakses,
- role user sudah dipakai sebagai struktur partai di UI,
- input manual rekap TPS masih berjalan,
- test dan build lulus.

Phase 1 belum selesai secara arsitektur final jika targetnya adalah pemisahan penuh dari istilah dan schema SIMAP utama.

## Phase 2: Hardening SIMAP Garuda

Tujuan Phase 2 adalah mengubah fork yang sudah bersih secara permukaan menjadi aplikasi partai yang lebih tegas secara data, akses, dashboard, dan laporan.

### 1. Finalisasi Identitas Partai Garuda

- [x] Buat konfigurasi aplikasi untuk identitas Partai Garuda.
- [x] Simpan slug permanen `garuda`.
- [x] Simpan nomor urut historis 2024 sebagai metadata, bukan identitas utama.
- [x] Ganti placeholder logo dengan logo resmi.
- [x] Jadikan warna utama/aksen Partai Garuda sebagai konfigurasi UI.
- [x] Tambahkan logo Garuda dan nama aplikasi dari konfigurasi ke halaman login.

### 2. Rapikan Role Teknis

- [ ] Bersihkan data pengguna database SIMAP Garuda agar hanya berisi akun operasional Garuda yang relevan.
- [ ] Tambahkan field nomor telepon pengguna untuk Admin Partai, Korcam, Kordes, dan Saksi TPS.
- [ ] Tentukan apakah DB role akan tetap kompatibel (`admin/ppk/pps/kpps`) atau dimigrasi penuh.
- [ ] Jika dimigrasi penuh, ubah role menjadi `admin_partai`, `korcam`, `kordes`, `saksi_tps`.
- [ ] Update middleware, route, controller, seeder, factory, dan test.
- [ ] Rename URI publik ke `/korcam`, `/kordes`, dan `/saksi`.
- [ ] Tambahkan backward redirect sementara jika masih ada link lama.

### 3. Sederhanakan Data Partai

- [x] Batasi jenis rekap aktif ke DPR RI, DPRD Provinsi, dan DPRD Kabupaten.
- [x] Tambahkan migration pembersih rekap PPWP, DPD, Gubernur, dan Bupati.
- [x] Tentukan mode data legislatif: single-party Partai Garuda saja.
- [x] Jika hanya Garuda, filter master `rekap_partais` ke slug/identitas Garuda di halaman setup.
- [x] Batasi input caleg hanya untuk Partai Garuda.
- [x] Batasi dashboard dan export agar fokus pada suara Partai Garuda dan calegnya.
- [x] Tambahkan guard agar admin tidak tanpa sengaja membuat data partai lain jika mode single-party aktif.

### 4. Dashboard Khusus Partai

- [x] Buat kartu total suara Partai Garuda.
- [x] Buat ranking caleg Garuda.
- [x] Buat progres TPS masuk/final.
- [x] Buat wilayah kuat dan lemah per kecamatan/desa.
- [x] Buat daftar TPS belum masuk.
- [ ] Buat daftar TPS bermasalah atau perlu dicek internal.
- [x] Sesuaikan chart agar default menampilkan Garuda, bukan ranking semua partai.

### 5. Validasi Input Manual TPS

- [x] Hapus input DPT, pengguna hak pilih, surat suara, disabilitas, dan suara tidak sah dari form Saksi TPS.
- [x] Batasi form Saksi TPS agar hanya menerima suara Partai Garuda dan caleg Garuda.
- [x] Set field administratif lama ke `0` saat simpan rekap TPS agar schema lama tetap kompatibel.
- [ ] Bersihkan tampilan agregasi Kordes/Korcam/Admin dari field administratif KPU yang tidak relevan untuk partai.
  - [x] Kordes: rekap desa hanya menampilkan suara Garuda, caleg Garuda, total Garuda, dan status TPS.
  - [x] Korcam: rekap kecamatan dan detail desa hanya menampilkan suara Garuda, caleg Garuda, total Garuda, dan status TPS.
  - [x] Admin: rekap kabupaten dan detail wilayah hanya menampilkan suara Garuda, caleg Garuda, total Garuda, dan status TPS.
- [x] Bersihkan export laporan dari field suara tidak sah, DPT, surat suara, dan disabilitas.
- [ ] Tambahkan status internal: draft, perlu dicek, final.
- [ ] Tambahkan catatan internal partai untuk TPS bermasalah jika diperlukan.
- [ ] Tentukan siapa yang boleh finalisasi: Saksi TPS saja atau juga Kordes/Korcam/Admin Partai.
- [ ] Tambahkan test untuk input, update, finalisasi, dan scope wilayah.

### 6. Kebijakan Input Data

- [x] Putuskan SIMAP Garuda tidak memakai import command.
- [x] Tetapkan input suara hanya melalui form manual Saksi TPS.
- [x] Hapus command import Excel legacy dari project.
- [x] Hapus file contoh import Excel legacy dari folder command.
- [x] Perkuat validasi form manual untuk suara Partai Garuda dan caleg Garuda karena form adalah satu-satunya jalur input data.
- [ ] Tambahkan validasi lanjutan untuk status internal dan catatan koreksi TPS.

### 7. Export Laporan Partai

- [x] Buat export ringkasan Garuda per kabupaten/kecamatan/desa.
- [x] Buat export caleg Garuda.
- [ ] Buat export TPS belum masuk.
- [ ] Buat export TPS bermasalah.
- [x] Pastikan export tidak membawa data internal SIMAP utama yang tidak relevan.

### 8. Bersihkan Sisa Legacy

- [ ] Hapus cabang kode PPWP, DPD, Gubernur, dan Bupati yang sekarang sudah tidak reachable.
- [ ] Hapus atau refactor method controller koreksi inline/unlock yang sudah tidak diroute.
- [ ] Hapus kolom atau relasi user legacy yang tidak dipakai setelah audit aman.
- [ ] Hapus view Laravel default yang tidak dipakai jika masih ada.
- [x] Hapus command import lama yang masih terlalu spesifik SIMAP utama.
- [ ] Audit ulang string dan route legacy sebelum commit.

## Rekomendasi Urutan Kerja

1. Selesaikan commit Phase 1 cleanup dulu.
2. Kerjakan Phase 2A: identitas Garuda + dashboard fokus Garuda.
3. Kerjakan Phase 2C: single-party data guard.
4. Kerjakan Phase 2D: dashboard dan export resmi khusus Garuda.
5. Kerjakan Phase 2B: role teknis dan URI publik setelah model data stabil.
6. Kerjakan Phase 2E: bersih-bersih legacy lanjutan dan test penuh.

## Mapping ke PARTAI_PORTAL_BRAINSTORM.md

Bagian ini memetakan 12 tahapan eksekusi awal di `PARTAI_PORTAL_BRAINSTORM.md` ke status SIMAP Garuda saat ini. Mapping ini dipakai sebagai audit agar roadmap brainstorming tidak terlewat, sementara struktur kerja utama tetap memakai Phase 1 dan Phase 2A-E di atas.

| No | Tahapan Brainstorming | Status SIMAP Garuda |
| --- | --- | --- |
| 1 | Duplikasi/fork project SIMAP ke folder project baru | Selesai: project berjalan di folder `simap-garuda`. |
| 2 | Tentukan nama project, nama database, dan identitas aplikasi partai | Selesai: `SIMAP Garuda`, database `simap_garuda`, identitas di `config/party.php`. |
| 3 | Tentukan istilah role final | Sebagian selesai: UI memakai Admin Partai, Korcam, Kordes, Saksi TPS; nilai DB teknis masih `admin`, `ppk`, `pps`, `kpps`. |
| 4 | Bersihkan role lama yang tidak diperlukan | Sebagian selesai: `komisioner` dan `partai` ditolak saat login dan ada migration pembersih; audit schema/kolom legacy masih tersisa. |
| 5 | Sesuaikan middleware role dan redirect dashboard | Sebagian selesai: alur role partai berjalan dengan role teknis lama; migrasi penuh role/URI belum dilakukan. |
| 6 | Sesuaikan menu sidebar/topbar agar hanya menampilkan fitur partai | Selesai untuk MVP: menu KPU/dokumen internal sudah dilepas dan branding Garuda dipakai. |
| 7 | Pertahankan struktur wilayah kecamatan, desa, dan TPS | Selesai: struktur wilayah dipertahankan. |
| 8 | Sesuaikan manajemen user agar mengikuti hierarchy partai | Selesai untuk MVP: manajemen user dibatasi ke Admin Partai, Korcam, Kordes, dan Saksi TPS. |
| 9 | Tentukan apakah data diinput manual, import Excel/CSV, atau import JSON | Selesai: SIMAP Garuda hanya memakai input manual lewat form Saksi TPS; import command dihapus. |
| 10 | Buat dashboard awal khusus partai | Sebagian selesai: identitas, kartu total suara Garuda, ranking caleg Garuda, progres TPS, TPS belum masuk, dan wilayah kuat/lemah tersedia; TPS bermasalah belum selesai. |
| 11 | Sesuaikan grafik dan export agar fokus pada suara partai/caleg | Sebagian selesai: dashboard legislatif, chart default Garuda, dan export rekap sudah fokus Garuda; export TPS belum masuk/bermasalah belum selesai. |
| 12 | Jalankan test dasar login, akses role, scope wilayah, input/import data, agregasi, dan export | Sebagian selesai: test login legacy, akses role, scope wilayah, guard jenis rekap, guard input Garuda, dan export Garuda tersedia; coverage validasi input manual lanjutan, agregasi, dan chart perlu ditambah. |

## Catatan Status Terbaru

- Phase 2A identitas Garuda selesai: konfigurasi `config/party.php` sudah menjadi sumber identitas, logo, warna, label role, dan metadata nomor historis.
- Halaman login sudah menampilkan logo Garuda dan nama aplikasi dari konfigurasi agar identitas Partai Garuda terlihat sejak layar pertama.
- Rekap non-legislatif sudah dinonaktifkan dari aplikasi dan dibersihkan lewat migration `2026_06_14_000002_remove_non_party_rekap_data`.
- Jenis rekap resmi SIMAP Garuda sekarang hanya `dpr_ri`, `dprd_prov`, dan `dprd_kab`.
- Single-party guard tahap pertama selesai: setup hanya menampilkan master Partai Garuda, admin tidak bisa menambah partai selain Garuda, dan caleg tidak bisa ditambahkan ke partai kompetitor.
- Scope Garuda sudah diterapkan ke form input Saksi TPS, rekap Kordes/Korcam/Admin, agregasi admin, chart, dan master export; request input manual yang membawa ID partai/caleg kompetitor ditolak.
- Import command legacy sudah dihapus; data suara SIMAP Garuda hanya masuk lewat form manual Saksi TPS.
- Form input Saksi TPS sudah disederhanakan: hanya suara Partai Garuda dan caleg Garuda yang diisi; data DPT, surat suara, disabilitas, serta suara tidak sah tidak lagi diminta dari saksi.
- Export rekap TPS/desa/kecamatan/kabupaten sudah dibersihkan agar hanya membawa suara Partai Garuda, suara caleg Garuda, total Garuda, dan status input TPS.
- Dashboard legislatif sudah menampilkan kartu total suara Garuda, ranking caleg Garuda, progres TPS, daftar TPS belum masuk, serta wilayah kuat/lemah.
- Next step: lanjut status internal TPS bermasalah dan export TPS belum masuk/bermasalah.
