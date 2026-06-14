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
- [ ] Setup pemilu masih luas dan masih memuat banyak jenis pemilihan; belum disederhanakan khusus kebutuhan Partai Garuda.
- [ ] Dashboard masih memakai fondasi dashboard SIMAP dan belum fokus penuh pada performa Partai Garuda saja.
- [ ] Master partai masih multi-partai; belum difilter/diubah menjadi konfigurasi Partai Garuda permanen dengan slug `garuda`.
- [ ] Logo saat ini adalah placeholder lokal, belum asset resmi Partai Garuda.

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

- [ ] Buat konfigurasi aplikasi untuk identitas Partai Garuda.
- [ ] Simpan slug permanen `garuda`.
- [ ] Simpan nomor urut historis 2024 sebagai metadata, bukan identitas utama.
- [ ] Ganti placeholder logo dengan logo resmi.
- [ ] Jadikan warna utama/aksen Partai Garuda sebagai konfigurasi UI.

### 2. Rapikan Role Teknis

- [ ] Tentukan apakah DB role akan tetap kompatibel (`admin/ppk/pps/kpps`) atau dimigrasi penuh.
- [ ] Jika dimigrasi penuh, ubah role menjadi `admin_partai`, `korcam`, `kordes`, `saksi_tps`.
- [ ] Update middleware, route, controller, seeder, factory, dan test.
- [ ] Rename URI publik ke `/korcam`, `/kordes`, dan `/saksi`.
- [ ] Tambahkan backward redirect sementara jika masih ada link lama.

### 3. Sederhanakan Data Partai

- [ ] Tentukan mode data legislatif: hanya Partai Garuda atau tetap menyimpan kompetitor untuk pembanding.
- [ ] Jika hanya Garuda, filter master `rekap_partais` ke slug/identitas Garuda.
- [ ] Batasi input caleg hanya untuk Partai Garuda.
- [ ] Batasi dashboard dan export agar fokus pada suara Partai Garuda dan calegnya.
- [ ] Tambahkan guard agar admin tidak tanpa sengaja membuat data partai lain jika mode single-party aktif.

### 4. Dashboard Khusus Partai

- [ ] Buat kartu total suara Partai Garuda.
- [ ] Buat ranking caleg Garuda.
- [ ] Buat progres TPS masuk/final.
- [ ] Buat wilayah kuat dan lemah per kecamatan/desa.
- [ ] Buat daftar TPS belum masuk.
- [ ] Buat daftar TPS bermasalah atau perlu dicek internal.
- [ ] Sesuaikan chart agar default menampilkan Garuda, bukan ranking semua partai.

### 5. Validasi Input Manual TPS

- [ ] Perkuat validasi total suara sah, tidak sah, dan total pengguna.
- [ ] Tambahkan status internal: draft, perlu dicek, final.
- [ ] Tambahkan catatan internal partai untuk TPS bermasalah jika diperlukan.
- [ ] Tentukan siapa yang boleh finalisasi: Saksi TPS saja atau juga Kordes/Korcam/Admin Partai.
- [ ] Tambahkan test untuk input, update, finalisasi, dan scope wilayah.

### 6. Import Data Fase Berikutnya

- [ ] Tentukan format import awal: Excel/CSV atau JSON sanitasi dari SIMAP utama.
- [ ] Buat template import resmi.
- [ ] Buat validasi nomor urut historis Garuda untuk data Pemilu 2024.
- [ ] Pastikan import tidak konek runtime ke database SIMAP utama.
- [ ] Simpan riwayat import: waktu, operator, file, jumlah TPS, sukses, gagal.
- [ ] Buat preview sebelum import final.

### 7. Export Laporan Partai

- [ ] Buat export ringkasan Garuda per kabupaten/kecamatan/desa.
- [ ] Buat export caleg Garuda.
- [ ] Buat export TPS belum masuk.
- [ ] Buat export TPS bermasalah.
- [ ] Pastikan export tidak membawa data internal SIMAP utama yang tidak relevan.

### 8. Bersihkan Sisa Legacy

- [ ] Hapus atau refactor method controller koreksi inline/unlock yang sudah tidak diroute.
- [ ] Hapus kolom atau relasi user legacy yang tidak dipakai setelah audit aman.
- [ ] Hapus view Laravel default yang tidak dipakai jika masih ada.
- [ ] Review command import lama yang masih terlalu spesifik SIMAP utama.
- [ ] Audit ulang string dan route legacy sebelum commit.

## Rekomendasi Urutan Kerja

1. Selesaikan commit Phase 1 cleanup dulu.
2. Kerjakan Phase 2A: identitas Garuda + dashboard fokus Garuda.
3. Kerjakan Phase 2B: role teknis dan URI publik.
4. Kerjakan Phase 2C: single-party data guard.
5. Kerjakan Phase 2D: import/export resmi.
6. Kerjakan Phase 2E: bersih-bersih legacy lanjutan dan test penuh.

