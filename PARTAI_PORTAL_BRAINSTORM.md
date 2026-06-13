# Brainstorm Portal Partai SIMAP

Catatan ini merangkum ide pengembangan portal partai di SIMAP berdasarkan diskusi perencanaan fitur.

## Catatan Keputusan Terbaru: Project Baru Khusus Partai

Setelah diskusi lanjutan, arah yang diminta adalah bukan lagi menambah fitur partai di project SIMAP utama, tetapi membuat project baru khusus partai.

Project baru ini sebaiknya diposisikan sebagai aplikasi mandiri berbasis fork dari SIMAP, bukan sekadar fitur tambahan di sistem utama. Artinya, kode awal boleh mengambil banyak pola dari SIMAP agar pengembangan cepat, tetapi database, role, istilah, akses, dan alur kerja disesuaikan untuk kebutuhan partai.

Prinsip utama keputusan ini:

- Project partai berdiri sendiri.
- Database partai terpisah dari database SIMAP utama.
- Partai tidak membaca langsung database SIMAP utama.
- Data yang masuk ke project partai berasal dari import, export, atau input mandiri.
- Struktur wilayah dan hierarki pengguna tetap bisa mirip PPK, PPS, dan KPPS, tetapi nama role dan konteksnya diganti sesuai organisasi partai.
- Fitur internal KPU/SIMAP yang tidak relevan sebaiknya dihapus, disembunyikan, atau dinonaktifkan.

Pendekatan yang paling sehat adalah membuat fork resmi, misalnya:

```text
simap-partai
```

atau nama lain sesuai identitas produk yang diinginkan.

### Phase 1 Locked: SIMAP Garuda

Project pertama yang akan dibuat adalah project khusus Partai Garuda.

Keputusan Phase 1:

```text
Project: SIMAP Garuda
Folder: simap-garuda
Database: simap_garuda
Partai: Partai Garuda
Slug permanen: garuda
Model aplikasi: satu project untuk satu partai
Role: admin_partai, korcam, kordes, saksi_tps
Data MVP: input manual TPS terlebih dahulu
Import: fase berikutnya setelah struktur role dan rekap stabil
```

Nomor urut partai tidak boleh dijadikan identitas utama Partai Garuda karena nomor urut dapat berubah pada pemilu berikutnya.

Untuk data historis Pemilu 2024, nomor urut Garuda di master SIMAP saat ini adalah:

```text
GARUDA = nomor_urut 11
```

Fungsi `nomor_urut` dalam konteks project Garuda:

- Kunci bantu untuk ekstrak atau matching data Pemilu 2024 dari database SIMAP utama.
- Referensi historis pada laporan dan arsip.
- Alat validasi saat import data suara, partai, atau caleg dari sumber eksternal.
- Bahan rekonsiliasi jika suatu hari perlu mencocokkan data Garuda dengan data SIMAP/KPU.

Yang tidak boleh dilakukan:

- Jangan memakai `nomor_urut` sebagai identitas permanen Partai Garuda.
- Jangan membuat project Garuda bergantung runtime ke database SIMAP utama.
- Jangan menganggap nomor urut 2024 akan tetap sama pada pemilu berikutnya.

Alur yang benar:

```text
SIMAP utama -> export/sanitasi data Garuda berdasarkan nomor_urut 11 -> import ke simap_garuda
```

Setelah data masuk ke database `simap_garuda`, hubungan dengan database SIMAP utama putus. Project Garuda berjalan sebagai aplikasi mandiri yang dipegang Partai Garuda. Jika ada sengketa atau selisih angka, data Garuda dapat dibandingkan dengan data SIMAP/KPU berdasarkan pemilu, jenis pemilihan, wilayah, TPS, caleg, partai, dan nomor urut pada pemilu tersebut.

### Kenapa Project Baru Masuk Akal

Arah project baru masuk akal jika kebutuhan atasan adalah membuat produk khusus partai, bukan sekadar memberi akun partai untuk melihat data di SIMAP.

Keuntungannya:

- Data internal SIMAP/KPU tetap aman karena tidak dibuka ke sistem partai.
- Partai punya aplikasi sendiri dengan database sendiri.
- Risiko akses silang ke data admin, operator, dokumen internal, catatan verifikasi, atau fitur koreksi SIMAP utama menjadi jauh lebih kecil.
- Istilah, role, menu, dashboard, dan laporan bisa disesuaikan penuh untuk kebutuhan partai.
- Tiap partai bisa memiliki data, branding, akun, dan kebijakan akses sendiri.
- Cocok jika nantinya partai membutuhkan sistem jangka panjang, bukan hanya akses sementara.

Namun project baru tidak boleh hanya berupa copy mentah yang dibiarkan berkembang tanpa kendali. Lebih aman jika dianggap sebagai fork resmi dari SIMAP dengan scope yang jelas.

### Risiko Yang Harus Disadari

Risiko utama dari project baru adalah maintenance menjadi dua jalur.

Hal yang perlu dijaga:

- Bug fix penting di SIMAP utama mungkin perlu diterapkan juga ke project partai.
- Perubahan format export, chart, rekap, atau struktur wilayah bisa perlu disinkronkan.
- Jika terlalu banyak modifikasi liar, project partai bisa sulit di-maintain.
- Jika database dan importer tidak dirancang jelas, angka bisa berbeda dari sumber data yang diharapkan.
- Jika role baru tidak dipetakan dengan rapi, akses wilayah bisa bocor atau membingungkan.

Karena itu, project partai sebaiknya dibuat dengan scope tegas:

- Fokus pada kebutuhan partai.
- Database sendiri.
- Read-only atau input terbatas sesuai kebutuhan partai.
- Tidak membawa fitur internal KPU yang tidak diperlukan.
- Import data harus punya format dan validasi jelas.

### Struktur Role Yang Disarankan

Role SIMAP utama seperti `admin`, `komisioner`, `partai`, `ppk`, `pps`, dan `kpps` sebaiknya tidak dipakai mentah. Untuk project partai, istilah role bisa diganti agar sesuai struktur partai.

Contoh struktur role:

```text
admin_partai
korcam
kordes
saksi_tps
```

Alternatif nama lain:

```text
operator_partai
koordinator_kecamatan
koordinator_desa
koordinator_tps
```

Pola akses yang disarankan:

- `admin_partai` melihat semua data partai.
- `korcam` melihat data di satu kecamatan.
- `kordes` melihat data di satu desa atau kelurahan.
- `saksi_tps` atau `kortps` melihat dan/atau menginput data TPS miliknya.

Hierarki ini mirip PPK, PPS, dan KPPS di SIMAP, tetapi konteksnya bukan struktur penyelenggara pemilu. Konteksnya adalah struktur internal partai atau tim saksi.

### Fitur Yang Bisa Dipertahankan Dari SIMAP

Beberapa modul SIMAP masih berguna untuk project partai:

- Login dan session auth.
- Manajemen user.
- Master kecamatan, desa, dan TPS.
- Relasi user ke wilayah.
- Rekap suara per TPS.
- Agregasi desa, kecamatan, dan kabupaten.
- Dashboard ringkasan.
- Grafik dan peta.
- Export Excel/PDF.
- Import data dari Excel/CSV jika diperlukan.

Modul ini bisa dipakai sebagai fondasi, tetapi perlu disesuaikan agar tidak membawa istilah dan akses KPU yang tidak relevan.

### Fitur Yang Sebaiknya Dihapus atau Dinonaktifkan

Fitur internal SIMAP utama yang sebaiknya tidak ikut ke project partai:

- Role `komisioner` jika tidak dibutuhkan.
- Login partai sebagai fitur tambahan, karena project ini sendiri sudah khusus partai.
- Fitur verifikasi dokumen internal KPU jika tidak relevan.
- Catatan penolakan/verifikasi dokumen petugas.
- Unlock rekap final oleh admin KPU.
- Koreksi inline internal admin jika tidak dibutuhkan.
- Penanda cell koreksi manual internal.
- Backup/restore dokumen internal SIMAP.
- Tool setup pemilu yang terlalu luas jika data partai bersifat snapshot atau import.
- Akses ke data partai lain jika project dibuat untuk satu partai.

Jika ada fitur yang tetap diperlukan, fitur tersebut harus diberi konteks baru untuk partai, bukan dibawa apa adanya.

### Model Data Project Partai

Karena database berdiri sendiri, project partai bisa memiliki struktur data yang lebih sederhana.

Data minimal:

- Wilayah: kecamatan, desa/kelurahan, TPS.
- User dan role hierarchy.
- Master partai jika project mendukung lebih dari satu partai, atau konfigurasi identitas partai jika hanya satu partai.
- Caleg partai.
- Rekap suara partai.
- Rekap suara caleg.
- Status data masuk per TPS.
- Riwayat import atau sinkronisasi data jika diperlukan.

Jika project hanya untuk satu partai, tabel master partai bisa dibuat sebagai konfigurasi aplikasi, bukan data utama yang kompleks.

Jika project akan dipakai banyak partai dengan database masing-masing, struktur tetap bisa sama, tetapi setiap deployment hanya berisi data partai tersebut.

### Alur Data Yang Disarankan

Karena project partai tidak mengambil langsung database SIMAP utama, alur data harus dibuat eksplisit.

Opsi alur data:

1. Import dari Excel atau CSV

   Admin partai mengupload file hasil rekap, lalu sistem membaca data TPS, desa, kecamatan, partai, dan caleg.

2. Import dari JSON hasil export SIMAP

   SIMAP utama menghasilkan file export yang sudah disanitasi, lalu project partai mengimport file tersebut.

3. Input manual oleh saksi atau koordinator

   `saksi_tps` menginput data TPS, lalu `kordes`, `korcam`, dan `admin_partai` memantau progres.

4. Snapshot final

   Setelah data final, project partai hanya berisi data akhir untuk dashboard, grafik, dan laporan.

Untuk tahap awal, opsi paling aman adalah import file yang sudah jelas formatnya. Jangan langsung koneksi antar database agar batas keamanan tetap bersih.

### Dashboard Project Partai

Dashboard project partai sebaiknya fokus pada kebutuhan partai, bukan kebutuhan operator KPU.

Isi dashboard yang relevan:

- Total suara partai.
- Total suara caleg partai.
- Ranking caleg internal.
- Wilayah kuat dan lemah.
- Perbandingan suara per kecamatan.
- Perbandingan suara per desa.
- Progress data TPS masuk.
- TPS belum masuk.
- TPS bermasalah atau perlu verifikasi internal.
- Peta kekuatan suara.
- Export laporan untuk pengurus partai.

Jika project hanya untuk satu partai, dashboard tidak perlu terlalu banyak menampilkan data partai lain. Data kompetitor hanya ditampilkan jika memang dibutuhkan dan diizinkan oleh kebijakan data.

### Rekomendasi Eksekusi Besok

Tahapan eksekusi yang disarankan:

1. Duplikasi/fork project SIMAP ke folder project baru.
2. Tentukan nama project, nama database, dan identitas aplikasi partai.
3. Tentukan istilah role final: misalnya `admin_partai`, `korcam`, `kordes`, dan `saksi_tps`.
4. Bersihkan role lama yang tidak diperlukan.
5. Sesuaikan middleware role dan redirect dashboard.
6. Sesuaikan menu sidebar/topbar agar hanya menampilkan fitur partai.
7. Pertahankan struktur wilayah kecamatan, desa, dan TPS.
8. Sesuaikan manajemen user agar mengikuti hierarchy partai.
9. Tentukan apakah data akan diinput manual, diimport dari Excel/CSV, atau diimport dari export JSON.
10. Buat dashboard awal khusus partai.
11. Sesuaikan grafik dan export agar fokus pada suara partai/caleg.
12. Jalankan test dasar login, akses role, scope wilayah, input/import data, agregasi, dan export.

Keputusan penting: setuju dengan arah project baru, tetapi implementasinya sebaiknya berupa fork SIMAP yang dirapikan, bukan copy-paste total tanpa batas. Project baru harus punya database sendiri, role hierarchy baru, dan hanya membawa fitur yang memang berguna untuk kebutuhan partai.

## Kesimpulan Utama

SIMAP bisa mendukung login khusus untuk setiap partai. Pendekatan yang paling efektif untuk masa pemilu berjalan adalah tetap memakai satu sistem SIMAP multi-partai, lalu membatasi tampilan berdasarkan akun partai yang login.

Partai cukup menerima:

```text
URL login partai
username
password
```

Contoh:

```text
https://simap.example.com/partai/golkar/login
username: golkar
password: ********
```

Setelah login, dashboard otomatis mengikuti partai tersebut. Golkar melihat dashboard Golkar, PKB melihat dashboard PKB, dan seterusnya.

## Login Page Per Partai

Secara teknis bisa dibuat satu template login yang dinamis, bukan banyak file login terpisah.

Contoh pola URL:

```text
/partai/{slug}/login
```

Contoh:

```text
/partai/golkar/login
/partai/pkb/login
/partai/gerindra/login
/partai/pdip/login
```

Halaman login mengambil profil partai dari `slug`, lalu menampilkan logo, nama, dan warna visual partai.

## Perubahan Yang Disarankan

Supaya lebih rapi dan mudah dikelola, perlu identitas partai yang tidak bergantung langsung pada baris `rekap_partais`, karena data partai bisa berulang per jenis pemilu atau dapil.

Opsi yang disarankan adalah membuat profil partai tersendiri:

```text
partai_profiles
- id
- nomor_urut
- nama
- slug
- logo_path
- warna_utama
```

Akun `users` role `partai` dapat dihubungkan ke profil partai tersebut. Filter data rekap tetap bisa memakai `nomor_urut` atau mapping yang sesuai.

## Dashboard Partai Yang Disarankan

Dashboard partai sebaiknya read-only dan scoped ketat ke partai yang login.

Isi yang aman dan menarik untuk partai:

- Identitas partai: logo, nama, nomor urut, dan sapaan khusus.
- Ringkasan suara partai untuk DPR RI, DPRD Provinsi, dan DPRD Kabupaten.
- Total suara partai per kabupaten, kecamatan, desa, dan TPS jika data TPS memang boleh dibuka.
- Ranking partai di tiap jenis legislatif.
- Daftar caleg partai sendiri beserta suara.
- Top caleg partai sendiri.
- Peta atau grafik kekuatan suara partai per kecamatan/desa.
- Progress data masuk: TPS final, TPS belum final, dan persentase data masuk.
- Export Excel/PDF untuk data partai sendiri.
- Dokumen atau laporan final yang sudah diverifikasi dan memang boleh diakses partai.

Struktur dashboard ideal:

1. Ringkasan suara, ranking, dan progress data.
2. Tab legislatif: DPR RI, DPRD Provinsi, DPRD Kabupaten.
3. Tabel wilayah: kecamatan/desa/TPS, suara, persentase, TPS masuk, dan status finalisasi.
4. Grafik dan peta kekuatan suara.
5. Unduhan laporan.

## Data Yang Sebaiknya Tidak Dibagikan Ke Partai

Untuk menjaga keamanan dan batas akses, data berikut sebaiknya tidak dipublish ke dashboard partai:

- Data akun admin, PPK, PPS, KPPS, atau user partai lain.
- Nomor HP, email internal, atau identitas operator.
- Dokumen mentah yang belum diverifikasi.
- Catatan verifikasi internal atau komentar operasional petugas.
- Fitur koreksi, unlock, inline update, atau penanda cell internal.
- Log import, backup, error, cache, atau audit internal.
- Data draft yang belum final, kecuali ada kebijakan eksplisit bahwa partai boleh melihat data sementara.
- Data partai lain sampai level terlalu granular jika belum final atau belum disepakati.

## Satu Sistem Multi-Partai Saat Pemilu Berjalan

Untuk hari pemilu dan masa rekap berjalan, satu sistem multi-partai lebih efektif daripada membuat project terpisah per partai.

Alasannya:

- Data tetap satu sumber.
- Perbaikan bug cukup dilakukan sekali.
- Perubahan aturan, tampilan, grafik, atau export cepat diterapkan ke semua partai.
- Kontrol akses lebih mudah diaudit.
- Risiko angka berbeda antar versi lebih kecil.
- Maintenance server, cache, backup, dan deployment lebih sederhana.

Model ini tetap bisa terasa personal untuk partai lewat URL, logo, warna, dan dashboard khusus.

Contoh:

```text
simap.example.com/partai/golkar/login
golkar.simap.example.com
```

Keduanya tetap bisa memakai project dan database SIMAP yang sama.

## Ekstrak Project Khusus Partai Setelah Data Final

Setelah pemilu selesai dan data sudah final, ekstrak khusus satu partai bisa dilakukan dengan lebih aman.

Namun ekstrak sebaiknya berbentuk snapshot read-only, bukan clone penuh sistem operasional.

Yang aman diekstrak:

- Data suara partai tersebut.
- Data caleg partai tersebut.
- Ranking dan agregasi wilayah yang memang boleh dibuka.
- Grafik dan peta.
- Export laporan.
- Status final atau data masuk sebagai konteks historis.

Yang tidak sebaiknya ikut diekstrak:

- User admin, PPK, PPS, KPPS.
- Password atau hash user dari sistem utama.
- Dokumen mentah/internal.
- Log import, backup, cache, error, atau audit.
- Fitur setup, import, verifikasi, koreksi, unlock, dan inline update.
- Data draft atau data yang belum final.

## Bentuk Ekstrak Yang Disarankan

Ada dua opsi aman:

1. Mini dashboard statis

   Data final diexport ke JSON, SQLite, atau CSV, lalu dibuat dashboard read-only. Ini paling aman untuk arsip, presentasi, atau portal privat sederhana.

2. Project Laravel ringan khusus partai

   Tetap ada login dan dashboard, tetapi database hanya berisi subset data final untuk satu partai. Cocok jika partai membutuhkan akses privat jangka panjang.

## Rekomendasi Arah Implementasi

Tahap awal yang paling masuk akal:

1. Buat profil partai berisi slug, logo, warna, dan nomor urut.
2. Tambah login dinamis `/partai/{slug}/login`.
3. Pastikan login role `partai` hanya bisa masuk ke partai yang cocok.
4. Bangun dashboard partai read-only dengan scope ketat.
5. Tambah export laporan khusus partai.
6. Setelah data final, siapkan tool export snapshot per partai jika diperlukan.

Prinsip utamanya: saat pemilu berjalan, gunakan satu SIMAP multi-partai. Setelah data final, ekstrak per partai boleh dilakukan sebagai snapshot read-only yang sudah disanitasi.
