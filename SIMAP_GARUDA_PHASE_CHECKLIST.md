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
- [x] Kolom `users.partai_id` sudah diaudit dan dihapus dari schema user SIMAP Garuda.
- [x] Method controller koreksi internal admin yang sudah tidak diroute sudah dihapus.
- [x] Setup pemilu disederhanakan ke DPR RI, DPRD Provinsi, dan DPRD Kabupaten.
- [x] Dashboard sudah fokus pada performa Partai Garuda.
- [x] Master partai sudah difilter ke Partai Garuda dan identitas permanen disimpan di `config/party.php`.
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
- [x] Buat daftar TPS bermasalah atau perlu dicek internal.
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
- [x] Tambahkan status internal: draft, perlu dicek, final.
- [x] Tambahkan catatan internal partai untuk TPS bermasalah jika diperlukan.
- [x] Izinkan Kordes menginput dan mengedit suara TPS di dalam desa yang menjadi scope-nya.
- [x] Izinkan Korcam menginput dan mengedit suara TPS di dalam kecamatan yang menjadi scope-nya.
- [x] Pastikan Admin Partai tetap bisa koreksi/input TPS lintas wilayah.
- [x] Tentukan aturan finalisasi baru untuk Saksi TPS, Kordes, Korcam, dan Admin Partai.
- [x] Tambahkan test untuk input, update, finalisasi, dan scope wilayah.

### 6. Kebijakan Input Data

- [x] Putuskan SIMAP Garuda tidak memakai import command.
- [x] Tetapkan input suara hanya melalui form manual Saksi TPS.
- [x] Perluas jalur input manual agar Kordes dan Korcam bisa ikut mengisi/mengoreksi suara TPS sesuai scope wilayah.
- [x] Hapus command import Excel legacy dari project.
- [x] Hapus file contoh import Excel legacy dari folder command.
- [x] Perkuat validasi form manual untuk suara Partai Garuda dan caleg Garuda karena form adalah satu-satunya jalur input data.
- [x] Tambahkan validasi lanjutan untuk status internal dan catatan koreksi TPS.

### 7. Export Laporan Partai

- [x] Buat export ringkasan Garuda per kabupaten/kecamatan/desa.
- [x] Buat export caleg Garuda.
- [x] Buat export TPS belum masuk.
- [x] Buat export TPS bermasalah.
- [x] Pastikan export tidak membawa data internal SIMAP utama yang tidak relevan.

### 8. Bersihkan Sisa Legacy

- [x] Hapus cabang kode PPWP, DPD, Gubernur, dan Bupati yang sekarang sudah tidak reachable.
  - [x] Bersihkan cabang non-legislatif dari alur input TPS, rekap Kordes, rekap Korcam, auto export, dan dashboard summary.
  - [x] Bersihkan sisa cabang non-legislatif di Admin rekap chart/export.
  - [x] Bersihkan setup legacy PPWP/DPD/Pilkada.
- [x] Hapus atau refactor method controller koreksi inline/unlock yang sudah tidak diroute.
- [x] Hapus kolom atau relasi user legacy yang tidak dipakai setelah audit aman.
- [x] Hapus view Laravel default yang tidak dipakai jika masih ada.
- [x] Hapus command import lama yang masih terlalu spesifik SIMAP utama.
- [x] Audit ulang string dan route legacy sebelum commit.

### 9. Sinkronisasi Dengan Roadmap Multi-Project Partai

Bagian ini menyesuaikan SIMAP Garuda dengan arah terbaru di `../simap/PARTAI_PORTAL_BRAINSTORM.md`: SIMAP utama menjadi sumber data/core, SIMAP Garuda menjadi pilot aplikasi partai mandiri, dan berikutnya perlu ada `simap-partai-template`.

- [x] Posisi SIMAP Garuda ditetapkan sebagai pilot project aplikasi partai mandiri.
- [x] Identitas Partai Garuda sudah berbasis `config/party.php`, bukan `partai_profiles`.
- [x] Nomor urut Garuda disimpan sebagai metadata historis, bukan identitas permanen.
- [x] Runtime SIMAP Garuda tidak bergantung langsung ke database SIMAP utama.
- [x] Data dan UI sudah single-party untuk Garuda.
- [ ] Audit fitur generik SIMAP Garuda yang layak dipromosikan ke `simap-partai-template`.
- [ ] Tandai fitur yang terlalu spesifik Garuda agar tidak ikut masuk template.
- [ ] Siapkan daftar file/konsep reusable untuk template: `config/party.php`, role label, scope wilayah, form input TPS, dashboard, export, status internal, dan test.
- [ ] Siapkan standar import snapshot dari SIMAP utama jika nanti SIMAP utama membuat `export:party-snapshot`.
- [ ] Siapkan dokumentasi operasional yang bisa digeneralisasi untuk project partai lain.
- [ ] Pastikan cleanup role/URI teknis dilakukan dengan mempertimbangkan template, bukan hanya kebutuhan Garuda.

## Rekomendasi Urutan Kerja

1. Putuskan strategi role teknis: tetap kompatibel atau migrasi penuh ke `admin_partai/korcam/kordes/saksi_tps`.
2. Jika migrasi role dilakukan, rename URI publik ke `/korcam`, `/kordes`, dan `/saksi` dengan backward redirect.
3. Audit model/tabel legacy non-partai yang masih tersisa sebelum migration cleanup berikutnya.
4. Siapkan dokumentasi operasional SIMAP Garuda.
5. Audit fitur generik yang layak dipromosikan ke `simap-partai-template`.
6. Setelah SIMAP utama punya format snapshot, tambahkan import snapshot partai jika masih dibutuhkan.

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
| 10 | Buat dashboard awal khusus partai | Selesai untuk kebutuhan Phase 2 saat ini: identitas, kartu total suara Garuda, ranking caleg Garuda, progres TPS, TPS belum masuk, wilayah kuat/lemah, dan TPS perlu dicek tersedia. |
| 11 | Sesuaikan grafik dan export agar fokus pada suara partai/caleg | Selesai untuk kebutuhan Phase 2 saat ini: dashboard legislatif, chart default Garuda, export rekap Garuda, export TPS belum masuk, dan export TPS perlu dicek tersedia. |
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
- Status internal `perlu_dicek` dan catatan internal TPS sudah tersedia untuk koreksi Admin Partai.
- Dashboard sudah menampilkan kartu dan daftar TPS perlu dicek internal, plus export TPS belum masuk dan TPS perlu dicek untuk Admin Partai.
- Kebijakan input berikutnya berubah: Kordes dan Korcam perlu bisa ikut input/edit suara TPS sesuai scope wilayah, tidak hanya Saksi TPS dan Admin Partai.
- Akses edit suara TPS sudah diperluas: Saksi TPS, Kordes, dan Korcam bisa input/finalisasi sesuai scope; Admin Partai bisa koreksi lintas wilayah lewat mode status internal.
- Test input, update draft, finalisasi, proteksi final non-admin, dan scope wilayah sudah diperluas untuk Saksi TPS, Kordes, Korcam, dan Admin Partai.
- Cabang non-legislatif sudah dibersihkan dari alur runtime input TPS, rekap Kordes/Korcam, auto export, dan dashboard summary.
- Cabang non-legislatif sudah dibersihkan dari Admin rekap chart/export; endpoint admin rekap sekarang hanya menerima jenis legislatif aktif.
- Setup legacy PPWP/DPD/Pilkada sudah dihapus dari halaman setup, route, dan controller.
- Method koreksi admin yang tidak diroute (`editTps`, `inlineUpdate`, `applyInlineRekapChange`, dan `unlock`) sudah dihapus dari Admin rekap controller.
- Audit route/string legacy selesai: route dokumen/verifikasi dan setup non-legislatif tidak terdaftar; `ppk/pps/kpps` masih sengaja dipertahankan sebagai role/URI teknis sampai fase rename role.
- View Laravel default `welcome.blade.php` sudah dihapus karena tidak dipakai route mana pun.
- Roadmap SIMAP utama sudah berubah ke pola multi-project partai: SIMAP utama sebagai core/sumber snapshot, SIMAP Garuda sebagai pilot, dan `simap-partai-template` sebagai target standardisasi berikutnya.
- Checklist SIMAP Garuda sudah diselaraskan dengan roadmap tersebut; pekerjaan template dicatat sebagai audit/generalisasi, bukan eksekusi langsung hari ini.
- Audit aman `users.partai_id` selesai: kolom ini hanya sisa schema user multi-partai lama, tidak dipakai runtime SIMAP Garuda, dan sudah dihapus lewat migration `2026_06_17_000001_drop_legacy_partai_id_from_users_table`.
- Next step untuk eksekusi berikutnya: putuskan strategi role teknis, tetap kompatibel dengan `admin/ppk/pps/kpps` atau migrasi penuh ke `admin_partai/korcam/kordes/saksi_tps`.
