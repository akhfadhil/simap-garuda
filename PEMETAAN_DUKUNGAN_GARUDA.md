# Modul Pemetaan Dukungan Garuda

## Deskripsi

Pemetaan Dukungan Garuda adalah modul untuk mencatat dan mengelola data
pendukung Partai Garuda berdasarkan hasil pendataan/survei internal.

Modul ini **bukan bagian dari sistem rekap suara** dan data yang
tersimpan tidak memiliki akses login, role, atau keterlibatan dalam
proses input suara.

Tujuan modul: - Pendataan basis dukungan. - Monitoring sebaran pendukung
berdasarkan wilayah. - Analisis jumlah pendukung per kecamatan, desa,
dan TPS. - Membantu pemetaan wilayah dukungan Partai Garuda.

------------------------------------------------------------------------

# Struktur Menu

## Pemetaan Dukungan

### Data Pendukung

Fitur utama untuk mengelola data pendukung.

Data yang dicatat:

  Field              Keterangan
  ------------------ ---------------------------
  Nama               Nama pendukung
  NIK                Nomor identitas pendukung
  No HP / WhatsApp   Kontak pendukung
  Alamat             Alamat pendukung
  Kecamatan          Lokasi pendukung
  Desa               Lokasi pendukung
  TPS                TPS terkait (opsional)
  File KTP           Upload dokumen KTP
  Catatan            Informasi tambahan

------------------------------------------------------------------------

# Hak Akses

## Admin Partai

Memiliki akses penuh:

-   Melihat seluruh data pendukung.
-   Menambah data.
-   Mengubah data.
-   Menghapus data.
-   Melihat statistik.
-   Export data.

------------------------------------------------------------------------

## Korcam

Scope berdasarkan kecamatan:

-   Melihat data pendukung dalam wilayah kecamatan.
-   Menambah data pendukung.
-   Mengubah data pendukung dalam wilayahnya.
-   Melihat statistik kecamatan.

------------------------------------------------------------------------

## Kordes

Scope berdasarkan desa:

-   Melihat data pendukung dalam desa.
-   Menambah data pendukung.
-   Mengubah data pendukung dalam desa.

------------------------------------------------------------------------

## Saksi TPS

Tidak memiliki akses ke modul Pemetaan Dukungan.

Alasan: - Fokus saksi hanya pada input rekap suara TPS. - Data pendukung
bukan bagian dari proses rekap suara.

------------------------------------------------------------------------

# Fitur Pendataan

## Tambah Pendukung

Form input:

-   Nama
-   NIK
-   Nomor HP/WhatsApp
-   Alamat
-   Wilayah
-   Upload KTP
-   Catatan

------------------------------------------------------------------------

## Validasi Data

Sistem melakukan:

-   Validasi format NIK 16 digit.
-   Pengecekan data ganda berdasarkan NIK.
-   Validasi file upload KTP.

------------------------------------------------------------------------

# Upload Dokumen KTP

Dokumen KTP disimpan secara aman dan tidak menggunakan akses publik
langsung.

Akses file melalui sistem dengan pengecekan hak akses.

Contoh:

Tidak:

    /storage/ktp/nama.jpg

Menggunakan:

    /pemetaan-dukungan/{id}/ktp

------------------------------------------------------------------------

# Statistik Pemetaan Dukungan

Dashboard modul menampilkan:

## Total Pendukung

Contoh:

    Total Pendukung:
    15.240 orang

## Sebaran Wilayah

Contoh:

    Kecamatan A : 2.300
    Kecamatan B : 1.850
    Kecamatan C : 1.200

## Sebaran TPS

Contoh:

    TPS 001 : 40 pendukung
    TPS 002 : 35 pendukung

------------------------------------------------------------------------

# Filter Data

Tersedia pencarian:

-   Nama
-   NIK
-   Nomor HP
-   Kecamatan
-   Desa
-   TPS
-   Tanggal input

------------------------------------------------------------------------

# Export Data

Export tersedia untuk:

-   Data seluruh pendukung.
-   Data berdasarkan kecamatan.
-   Data berdasarkan desa.
-   Data berdasarkan TPS.

Format:

-   Excel

------------------------------------------------------------------------

# Struktur Database

Tabel utama:

`pendukungs`

  Kolom          Fungsi
  -------------- --------------------
  id             Primary key
  nama           Nama pendukung
  nik            NIK
  no_hp          Kontak
  alamat         Alamat
  kecamatan_id   Relasi wilayah
  desa_id        Relasi wilayah
  tps_id         Relasi TPS
  ktp_path       Lokasi file KTP
  catatan        Informasi tambahan
  created_by     User yang mencatat
  timestamps     Waktu data

------------------------------------------------------------------------

# Hubungan Dengan Sistem

Modul Pemetaan Dukungan berdiri sendiri.

Tidak terhubung dengan:

-   users sebagai akun pendukung.
-   rekap suara.
-   hasil pemilu.
-   data caleg.

Relasi hanya:

    User SIMAP
          |
          |
     mencatat
          |
          v
    Pendukung

------------------------------------------------------------------------

# Struktur Menu Akhir

    SIMAP Garuda

    ├── Dashboard
    │
    ├── Rekap Suara
    │
    ├── Grafik
    │
    ├── Export
    │
    ├── Pemetaan Dukungan
    │    ├── Data Pendukung
    │    ├── Statistik Dukungan
    │    └── Export Pendukung
    │
    └── Pengaturan
