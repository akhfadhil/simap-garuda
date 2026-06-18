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

- [x] Nilai role teknis database sudah menjadi `admin_partai`, `korcam`, `kordes`, `saksi_tps`.
- [x] URI dan nama route publik utama sudah memakai `korcam`, `kordes`, dan `saksi`; URI/route lama `ppk`, `pps`, `kpps` masih menjadi backward redirect sementara.
- [x] Kolom `users.partai_id` sudah diaudit dan dihapus dari schema user SIMAP Garuda.
- [x] Method controller koreksi internal admin yang sudah tidak diroute sudah dihapus.
- [x] Setup pemilu disederhanakan ke DPR RI, DPRD Provinsi, dan DPRD Kabupaten.
- [x] Dashboard sudah fokus pada performa Partai Garuda.
- [x] Master partai sudah difilter ke Partai Garuda dan identitas permanen disimpan di `config/party.php`.
- [x] Logo Garuda sudah tersedia sebagai asset lokal dan dipakai lewat konfigurasi `config/party.php`.
- [x] SIMAP Garuda dinyatakan fix untuk MVP operasional setelah uji manual admin setup, rekap, TPS perlu dicek, dan grafik.

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
- [x] Tentukan apakah DB role akan tetap kompatibel (`admin/ppk/pps/kpps`) atau dimigrasi penuh.
- [x] Jika dimigrasi penuh, ubah role menjadi `admin_partai`, `korcam`, `kordes`, `saksi_tps`.
- [ ] Update middleware, route, controller, seeder, factory, dan test.
- [x] Rename URI publik ke `/korcam`, `/kordes`, dan `/saksi`.
- [x] Tambahkan backward redirect sementara jika masih ada link lama.

### 3. Sederhanakan Data Partai

- [x] Batasi jenis rekap aktif ke DPR RI, DPRD Provinsi, dan DPRD Kabupaten.
- [x] Tambahkan migration pembersih rekap PPWP, DPD, Gubernur, dan Bupati.
- [x] Tentukan mode data legislatif: single-party Partai Garuda saja.
- [x] Jika hanya Garuda, filter master `rekap_partais` ke slug/identitas Garuda di halaman setup.
- [x] Batasi input caleg hanya untuk Partai Garuda.
- [x] Batasi dashboard dan export agar fokus pada suara Partai Garuda dan calegnya.
- [x] Tambahkan guard agar admin tidak tanpa sengaja membuat data partai lain jika mode single-party aktif.
- [x] Setup data pemilu disederhanakan lagi: Admin Partai cukup menambahkan caleg, sedangkan master Partai Garuda dibuat otomatis dari `config/party.php`.
- [x] Tambah dan hapus caleg di halaman setup memakai AJAX agar tidak reload dan tidak kembali ke posisi halaman paling atas.
- [x] Hapus caleg memakai dialog konfirmasi custom; tombol batal tidak mengirim request hapus.

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
- [x] Tambahkan kontrol cepat di Admin Rekapitulasi Data untuk menandai atau clear TPS `perlu_dicek` langsung dari detail kecamatan/desa tanpa masuk lewat Kelola TPS.
- [x] Tombol `Perlu Dicek` dan `Clear` di detail rekap admin berjalan via AJAX agar halaman tidak reload dan posisi scroll tidak kembali ke atas.
- [x] Clear status mempertahankan finalisasi: TPS yang sebelumnya final kembali ke `final`, TPS yang belum final kembali ke `draft`.
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

#### Audit Legacy Model/Tabel Non-Partai - 2026-06-17

Hasil audit:

- Route dokumen, import, backup, restore, dan setup non-legislatif tidak terdaftar di runtime SIMAP Garuda.
- Command import, backup, dan restore legacy tidak tersisa.
- View dokumen dan view setup non-legislatif tidak tersisa.
- Tabel/model rekap non-legislatif sudah dibersihkan lewat migration cleanup dan penghapusan model/relasi runtime.

Sudah dihapus:

- Migration cleanup `2026_06_17_000003_drop_legacy_non_party_rekap_tables.php` untuk drop tabel legacy `rekap_ppwp_calons`, `rekap_ppwp_suaras`, `rekap_dpd_calons`, `rekap_dpd_suaras`, `rekap_gubernur_calons`, `rekap_gubernur_suaras`, `rekap_bupati_calons`, dan `rekap_bupati_suaras`.
- Model `RekapPpwpCalon`, `RekapPpwpSuara`, `RekapDpdCalon`, `RekapDpdSuara`, `RekapGubernurCalon`, `RekapGubernurSuara`, `RekapBupatiCalon`, dan `RekapBupatiSuara`.
- Relasi non-legislatif di `RekapHeader`: `ppwpSuaras`, `dpdSuaras`, `gubernurSuaras`, `bupatiSuaras`, serta cabang non-legislatif pada `getSuaraSahAttribute()`.
- Fallback `ppwp` di `RekapExport`; fallback flat export sekarang memakai `dpr_ri`.

Ditunda:

- Mengubah migration lama pembuat tabel non-legislatif; lebih aman lewat migration drop baru dulu, lalu squash saat template/fresh schema.
- File migration no-op `2026_03_02_000006_create_dokumens_table.php`; biarkan sampai migration squash agar histori migration existing DB tetap jelas.
- Migration role legacy `2026_05_24_*` dan `2026_06_14_000001_*`; biarkan sampai migration squash karena masih menjadi jalur upgrade existing DB.
- Backward route/URI `ppk`, `pps`, dan `kpps`; tunda sampai masa kompatibilitas lama selesai. Nama class/view internal sudah direname ke istilah final Korcam/Kordes/Saksi.
- `config.filesystems.backup_path` dan env `BACKUP_DOKUMEN_PATH`; tunda sampai audit config menyeluruh karena saat ini hanya tersisa sebagai config, bukan route runtime.
- Enum historis jenis non-partai di migration/schema `rekap_headers`; tunda sampai migration squash atau migration enum khusus karena test guard masih butuh memastikan jenis non-partai tidak accessible.
- Test guard non-party `ppwp`; tetap dipertahankan sebagai bukti jenis non-partai tidak bisa diakses.

### 9. Sinkronisasi Dengan Roadmap Multi-Project Partai

Bagian ini menyesuaikan SIMAP Garuda dengan arah terbaru di `../simap/PARTAI_PORTAL_BRAINSTORM.md`: SIMAP utama menjadi sumber data/core, SIMAP Garuda menjadi pilot aplikasi partai mandiri, dan berikutnya perlu ada `simap-partai-template`.

- [x] Posisi SIMAP Garuda ditetapkan sebagai pilot project aplikasi partai mandiri.
- [x] Identitas Partai Garuda sudah berbasis `config/party.php`, bukan `partai_profiles`.
- [x] Nomor urut Garuda disimpan sebagai metadata historis, bukan identitas permanen.
- [x] Runtime SIMAP Garuda tidak bergantung langsung ke database SIMAP utama.
- [x] Data dan UI sudah single-party untuk Garuda.
- [x] Audit fitur generik SIMAP Garuda yang layak dipromosikan ke `simap-partai-template`.
- [x] Tandai fitur yang terlalu spesifik Garuda agar tidak ikut masuk template.
- [x] Siapkan daftar file/konsep reusable untuk template: `config/party.php`, role label, scope wilayah, form input TPS, dashboard, export, status internal, dan test.
- [x] Generalisasi hardcode Garuda tahap pertama ke helper party generik tanpa mengubah behavior runtime.
- [x] Ekstrak aturan scope wilayah ke `PartyScopeService` agar akses kecamatan/desa/TPS terpusat untuk template.
- [x] Rename controller, method, folder view, dan DOM/helper internal legacy `Ppk/Pps/Kpps` menjadi `Korcam/Kordes/Saksi`.
- [x] Generalisasi test fixture dari Garuda ke party config agar template tidak membawa data contoh Garuda.
- [x] Generalisasi sisa identifier/key internal Garuda di dashboard, model, dan view rekap ke istilah party/configured party.
- [x] Audit backward route legacy `ppk/pps/kpps` dan tandai semuanya sebagai redirect kompatibilitas yang tidak boleh masuk template.
- [x] Audit fresh schema/migration template dan tentukan tabel/kolom yang wajib masuk atau wajib ditinggal.
- [x] Perbaiki halaman grafik admin agar role label tersedia stabil dan halaman chart bisa dirender oleh test.
- [ ] Siapkan standar import snapshot dari SIMAP utama jika nanti SIMAP utama membuat `export:party-snapshot`.
- [x] Siapkan dokumentasi operasional yang bisa digeneralisasi untuk project partai lain.
- [x] Pastikan cleanup role/URI teknis dilakukan dengan mempertimbangkan template, bukan hanya kebutuhan Garuda.

## Rekomendasi Urutan Kerja

1. Mulai task `simap-partai-template`: buat rencana file yang dicopy dari SIMAP Garuda, file yang disanitasi dari identitas Garuda, dan migration fresh hasil audit schema.
2. Tentukan format konfigurasi partai di template: `config/party.php`, logo, warna, nama role, nomor historis per pemilu, dan data awal caleg/partai.
3. Siapkan migration fresh template yang hanya membawa fitur legislatif single-party, status internal, wilayah, dapil, user role final, export, dan rekap manual.
4. Siapkan opsi pelepasan backward route `ppk/pps/kpps` di SIMAP Garuda saat masa kompatibilitas dianggap selesai.
5. Setelah SIMAP utama punya format snapshot, tambahkan import snapshot partai jika masih dibutuhkan.

## State Setelah Restart

Jika sesi dilanjutkan setelah restart, jangan mulai dari SIMAP Garuda lagi kecuali ada bug baru. Status terakhir:

- SIMAP Garuda sudah fix untuk MVP operasional.
- Perubahan kode terakhir sudah dicommit di `simap-garuda` dan `../simap`.
- Dokumentasi finalisasi sudah dicommit.
- Next task utama adalah menyusun dan mengeksekusi `simap-partai-template`.

Langkah pertama setelah restart:

1. Baca ulang `SIMAP_GARUDA_TEMPLATE_AUDIT.md`.
2. Baca bagian `Rekomendasi Urutan Kerja` di file ini.
3. Buat rencana eksekusi `simap-partai-template`: file yang dicopy, file yang disanitasi, migration fresh, config partai, dan fitur yang tidak boleh ikut dari legacy.
4. Setelah rencana disetujui, baru buat folder/project template.

## Catatan Commit Sesi Finalisasi SIMAP Garuda - 2026-06-18 sampai 2026-06-19

- `1ef21b2 Improve party rekap review workflow`
  - Setup data pemilu disederhanakan agar Admin Partai cukup menambah caleg; master Partai Garuda dibuat otomatis berdasarkan `config/party.php`.
  - Admin Rekapitulasi Data mendapat kontrol cepat untuk menandai TPS `perlu_dicek` dari detail kecamatan/desa.
  - Dropdown desa pada detail rekap admin otomatis mengikuti kecamatan yang dipilih.
  - Aksi `Perlu Dicek` dan `Clear` memakai AJAX dengan CSRF token sehingga halaman tidak reload dan posisi scroll tetap.
  - Clear status menjaga status final jika TPS sebelumnya sudah final, dan kembali ke draft jika belum final.
  - Cache Laravel Excel diabaikan lewat `.gitignore`.
- `4d27144 Fix admin chart page role label`
  - Halaman Grafik & Statistik admin diperbaiki agar `$roleLabel`, `$homeRoute`, dan menu admin tersedia stabil.
  - Test render halaman grafik admin ditambahkan.
- `4134ec3 Improve setup caleg ajax workflow`
  - Tambah caleg di halaman setup SIMAP Garuda berjalan via AJAX sehingga tidak reload dan tidak kembali ke atas halaman.
  - Hapus caleg memakai dialog konfirmasi custom dan AJAX; batal tidak mengirim request hapus.
  - Counter jumlah caleg diperbarui otomatis saat tambah/hapus.
  - Response JSON untuk store/destroy caleg dan test AJAX setup caleg ditambahkan.
- `e0affdb Improve setup caleg ajax workflow` di project `../simap`
  - Perilaku tambah/hapus caleg tanpa reload diterapkan juga di SIMAP utama.
  - Layout SIMAP utama mendapat CSRF meta token untuk kebutuhan AJAX setup.
  - Test AJAX tambah caleg di SIMAP utama ditambahkan.

## Mapping ke PARTAI_PORTAL_BRAINSTORM.md

Bagian ini memetakan 12 tahapan eksekusi awal di `PARTAI_PORTAL_BRAINSTORM.md` ke status SIMAP Garuda saat ini. Mapping ini dipakai sebagai audit agar roadmap brainstorming tidak terlewat, sementara struktur kerja utama tetap memakai Phase 1 dan Phase 2A-E di atas.

| No | Tahapan Brainstorming | Status SIMAP Garuda |
| --- | --- | --- |
| 1 | Duplikasi/fork project SIMAP ke folder project baru | Selesai: project berjalan di folder `simap-garuda`. |
| 2 | Tentukan nama project, nama database, dan identitas aplikasi partai | Selesai: `SIMAP Garuda`, database `simap_garuda`, identitas di `config/party.php`. |
| 3 | Tentukan istilah role final | Selesai: role DB memakai `admin_partai`, `korcam`, `kordes`, dan `saksi_tps`; URI publik utama memakai istilah partai. |
| 4 | Bersihkan role lama yang tidak diperlukan | Sebagian selesai: `komisioner` dan `partai` ditolak saat login dan ada migration pembersih; audit schema/kolom legacy masih tersisa. |
| 5 | Sesuaikan middleware role dan redirect dashboard | Selesai: middleware, redirect dashboard, route publik, dan test sudah memakai role partai final dengan backward redirect untuk URI lama. |
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
- Role teknis final selesai: database, middleware, redirect dashboard, user management, rekap, export, dan test memakai `admin_partai`, `korcam`, `kordes`, dan `saksi_tps`.
- URI publik final tersedia: `/dashboard/admin-partai`, `/dashboard/korcam`, `/dashboard/kordes`, `/dashboard/saksi`, `/korcam/...`, dan `/kordes/...`; URI lama `ppk/pps/kpps` masih diarahkan sebagai backward redirect sementara.
- View Laravel default `welcome.blade.php` sudah dihapus karena tidak dipakai route mana pun.
- Roadmap SIMAP utama sudah berubah ke pola multi-project partai: SIMAP utama sebagai core/sumber snapshot, SIMAP Garuda sebagai pilot, dan `simap-partai-template` sebagai target standardisasi berikutnya.
- Checklist SIMAP Garuda sudah diselaraskan dengan roadmap tersebut; pekerjaan template dicatat sebagai audit/generalisasi, bukan eksekusi langsung hari ini.
- Audit aman `users.partai_id` selesai: kolom ini hanya sisa schema user multi-partai lama, tidak dipakai runtime SIMAP Garuda, dan sudah dihapus lewat migration `2026_06_17_000001_drop_legacy_partai_id_from_users_table`.
- Cleanup legacy model/tabel non-partai selesai di kode: migration drop tabel rekap non-legislatif sudah dibuat, model non-legislatif sudah dihapus, relasi `RekapHeader` sudah dibersihkan, dan fallback export sudah memakai `dpr_ri`.
- `php artisan test` lulus setelah cleanup legacy model/tabel non-partai.
- Migration cleanup legacy sudah berhasil diterapkan ke database MySQL lokal; tabel rekap non-legislatif legacy sudah tidak ada.
- Dokumentasi operasional lengkap tersedia di `SIMAP_GARUDA_OPERASIONAL.md`: setup fresh clone, konfigurasi, role/scope, alur input, export, deployment, backup, dan troubleshooting.
- Audit template selesai di `SIMAP_GARUDA_TEMPLATE_AUDIT.md`: daftar fitur reusable, hardcode Garuda yang harus diparameterkan, legacy yang tidak boleh masuk template, dan urutan ekstraksi sudah dicatat.
- Generalisasi hardcode Garuda tahap pertama selesai: `app/Support/PartyConfig.php` menjadi helper identitas/matching partai, `RekapPartai` punya scope `configuredParty()`, query utama memakai helper generik, dan label export/UI utama memakai `config('party.*')`.
- `php artisan test` lulus setelah generalisasi helper party.
- Aturan scope wilayah sudah diekstrak ke `app/Services/PartyScopeService.php`; dashboard summary, dashboard role, Korcam/Kordes controller, dan input TPS memakai service yang sama untuk akses kecamatan/desa/TPS.
- `php artisan test` lulus setelah ekstraksi `PartyScopeService`.
- Rename controller/view legacy selesai: `PpkController`, `PpsController`, dan `KppsController` sudah menjadi `KorcamController`, `KordesController`, dan `SaksiController`; folder view dashboard/rekap/data juga sudah memakai istilah final.
- Helper method dan DOM id manajemen user yang masih memakai `ppk/pps/kpps` sudah direname ke `korcam/kordes/saksi`.
- Backward route `ppk/pps/kpps` masih dipertahankan sebagai redirect sementara untuk kompatibilitas link lama, dan tidak boleh ikut ke template baru.
- `php artisan test` dan `npm.cmd run build` lulus setelah rename controller/view legacy.
- Generalisasi fixture test selesai: `tests/Feature/GarudaRoleAccessTest.php` sudah menjadi `tests/Feature/PartyRoleAccessTest.php`, nama test memakai istilah party/configured party, dan nomor/nama partai/caleg fixture diambil dari `config('party.*')`.
- `php artisan test` lulus setelah generalisasi fixture test.
- Generalisasi identifier internal selesai: alias model `scopeGaruda()`/`isGaruda()` dihapus, key fallback `total_suara_garuda` dihapus dari dashboard summary, DOM id form input memakai `display-suara-partai`, dan variabel Blade rekap memakai istilah party.
- Sisa kata `Garuda` di kode runtime hanya berada di `config/party.php` sebagai identitas deployment SIMAP Garuda.
- `php artisan test` dan `npm.cmd run build` lulus setelah generalisasi identifier internal.
- Audit backward route legacy selesai: route `dashboard.ppk`, `dashboard.pps`, `dashboard.kpps`, `ppk.*`, dan `pps.*` hanya redirect/link kompatibilitas di `routes/web.php`; controller, view, service, dan test aktif sudah memakai istilah final.
- Audit fresh schema template selesai: template perlu migration fresh/squashed untuk role final, wilayah, dapil, legislatif-only rekap, status internal, flag internal, dan index final; migration dokumen, non-legislatif, role compatibility, dan cleanup legacy tidak ikut template.
- Finalisasi UX setup caleg selesai: tambah/hapus caleg tidak reload, konfirmasi hapus tidak tembus saat batal, dan perilaku yang sama sudah diterapkan ke SIMAP utama.
