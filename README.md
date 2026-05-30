<div align="center">

<img src="docs/images/logo-smpn2-tasikmalaya.svg" alt="Logo SMPN 2 Tasikmalaya" width="130">

# SIABSoal SMPN 2 Tasikmalaya

### Sistem Informasi Analisis Butir Soal Berbasis Web
### Pengganti Pengolahan Manual/SPSS untuk Evaluasi Kualitas Soal dan Rekap Nilai

<p>
  <img src="https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 10">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.1+">
  <img src="https://img.shields.io/badge/MySQL%20%2F%20TiDB-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL / TiDB">
  <img src="https://img.shields.io/badge/Blade-Frontend-F05340?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel Blade">
</p>

<p>
  <b>Kerja Praktik - Program Studi Teknik Informatika</b><br>
  Studi Kasus: <b>SMPN 2 Tasikmalaya</b>
</p>

</div>

---

## Ringkasan Proyek

**SIABSoal SMPN 2 Tasikmalaya** adalah aplikasi web untuk membantu proses evaluasi hasil ujian melalui analisis butir soal dan rekapitulasi nilai siswa. Sistem ini dikembangkan untuk mendukung pengolahan data jawaban siswa secara lebih terstruktur, cepat, dan mudah digunakan oleh pihak sekolah.

Aplikasi ini berfokus pada alur kerja pengolahan hasil ujian, mulai dari pengelolaan data master, input data mentah, pengolahan jawaban benar/salah, analisis kualitas soal, sampai penyajian rekap nilai.

---

## Latar Belakang

Evaluasi kualitas soal merupakan bagian penting dalam proses pembelajaran karena dapat membantu guru mengetahui apakah soal yang digunakan sudah sesuai, terlalu mudah, terlalu sulit, atau perlu diperbaiki. Pada praktiknya, proses analisis butir soal sering dilakukan secara manual atau menggunakan aplikasi statistik yang kurang praktis untuk kebutuhan operasional sekolah.

Melalui aplikasi **SIABSoal**, proses tersebut dibuat berbasis web agar pengelolaan data, analisis soal, dan rekap nilai dapat dilakukan dalam satu sistem yang lebih terintegrasi.

---

## Tujuan Sistem

- Membantu sekolah melakukan analisis butir soal secara digital.
- Mempermudah input dan pengolahan data hasil ujian siswa.
- Mengurangi risiko kesalahan perhitungan manual.
- Menyediakan rekap nilai siswa secara sistematis.
- Mendukung evaluasi kualitas soal berdasarkan indikator analisis yang digunakan dalam Kerja Praktik.

---

## Fitur Utama

### Manajemen Data Master

- Data pengguna
- Data guru
- Data siswa
- Data kelas
- Data mata pelajaran
- Data tahun ajaran
- Data ujian
- Profil sekolah

### Pengolahan Ujian

- Input kunci jawaban
- Input data mentah jawaban siswa
- Pengolahan jawaban menjadi skor benar/salah
- Pengelompokan data siswa
- Analisis butir soal
- Daftar nilai hasil ujian
- Rekap nilai siswa

### Hak Akses Pengguna

- **Admin**: mengelola seluruh data sistem.
- **Guru**: mengelola data ujian, data kelas terkait, dan melihat hasil analisis.
- **Kepala Sekolah**: melihat ringkasan laporan dan hasil evaluasi.

---

## Konsep Analisis

Sistem dirancang untuk mendukung tahapan analisis sebagai berikut:

1. Input jawaban siswa.
2. Konversi jawaban menjadi nilai numerik, yaitu `1` untuk benar dan `0` untuk salah.
3. Pengelompokan siswa ke dalam kategori kelas atas dan kelas bawah berdasarkan hasil nilai.
4. Analisis indikator kualitas soal, terutama:
   - **Daya Pembeda (DP)**
   - **Tingkat Kesukaran (TK)**
5. Penyajian daftar nilai dan rekap hasil evaluasi.

---

## Teknologi yang Digunakan

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 10 |
| Bahasa | PHP 8.1+ |
| Frontend | Blade, HTML, CSS, JavaScript |
| Database | MySQL / TiDB |
| Export & Import | Laravel Excel |
| PDF Report | DomPDF |
| Local Development | Laragon / Apache |
| Version Control | Git & GitHub |
| Deployment | Docker / Koyeb |

---

## Struktur Modul

```text
resources/views/
├── analisis_data/
├── auth/
├── components/
├── daftar_nilai/
├── dashboard/
├── data_mentah/
├── exports/
├── guru/
├── kelas/
├── kunci_jawaban/
├── layouts/
├── mapel/
├── olah_data/
├── profil_sekolah/
├── rekap_nilai/
├── siswa/
├── tahun_ajaran/
├── ujian/
└── users/
```

---

## Instalasi Lokal

### 1. Clone repository

```bash
git clone https://github.com/rhnvlys/SIABSoal-SMPN-2-Tasikmalaya.git
cd SIABSoal-SMPN-2-Tasikmalaya
```

### 2. Install dependency PHP

```bash
composer install
```

### 3. Install dependency frontend

```bash
npm install
npm run build
```

### 4. Salin konfigurasi environment

```bash
cp .env.example .env
```

Untuk Windows PowerShell:

```powershell
copy .env.example .env
```

### 5. Generate application key

```bash
php artisan key:generate
```

### 6. Atur database

Sesuaikan konfigurasi database pada file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siabsoal
DB_USERNAME=root
DB_PASSWORD=
```

### 7. Jalankan migrasi dan seeder

```bash
php artisan migrate --seed
```

### 8. Jalankan aplikasi

```bash
php artisan serve
```

Akses aplikasi melalui:

```text
http://127.0.0.1:8000
```

---

## Deployment

Repository ini sudah disiapkan untuk deployment berbasis Docker melalui file:

```text
Dockerfile
docker/koyeb-start.sh
.env.koyeb.example
```

Untuk deployment, gunakan environment variable dari `.env.koyeb.example`, lalu isi nilai sensitif langsung di dashboard hosting, bukan di repository.

Environment yang wajib disiapkan pada hosting:

```env
APP_NAME="SIABSoal SMPN 2 Tasikmalaya"
APP_ENV=production
APP_KEY=base64:ISI_APP_KEY_PRODUCTION
APP_DEBUG=false
APP_URL=https://domain-hosting-kamu

DB_CONNECTION=mysql
DB_HOST=ISI_HOST_DATABASE
DB_PORT=4000
DB_DATABASE=ISI_NAMA_DATABASE
DB_USERNAME=ISI_USERNAME_DATABASE
DB_PASSWORD=ISI_PASSWORD_DATABASE
```

> Jangan commit file `.env`, password database, token hosting, API key, atau kredensial lain ke GitHub.

---

## Keamanan Repository

Repository ini tidak menyimpan file sensitif seperti:

- `.env`
- `.env.production`
- database dump `.sql`
- file backup
- token API
- kredensial hosting
- folder `vendor/`
- folder `node_modules/`
- file agent/AI lokal
- file skill lokal
- prompt sementara

Pastikan seluruh konfigurasi rahasia hanya disimpan di environment hosting atau file lokal yang masuk `.gitignore`.

---

## Screenshot Sistem

Tambahkan screenshot aplikasi ke folder berikut agar dokumentasi semakin lengkap:

```text
docs/images/
```

Rekomendasi screenshot:

```text
dashboard.png
data-mentah.png
analisis-data.png
rekap-nilai.png
daftar-nilai.png
```

Contoh penulisan setelah screenshot tersedia:

```md
![Dashboard](docs/images/dashboard.png)
![Analisis Data](docs/images/analisis-data.png)
![Rekap Nilai](docs/images/rekap-nilai.png)
```

---

## Pengembang

**Kaka**  
Mahasiswa Teknik Informatika  
Kerja Praktik di SMPN 2 Tasikmalaya

---

## Catatan Akademik

Aplikasi ini dikembangkan untuk kebutuhan Kerja Praktik dan dapat dikembangkan lebih lanjut sesuai kebutuhan sekolah. Setiap penggunaan data siswa, data guru, dan data akademik harus memperhatikan keamanan data dan privasi pihak terkait.

---

## Lisensi

Proyek ini digunakan untuk kebutuhan akademik/Kerja Praktik. Pengembangan lanjutan dapat dilakukan dengan tetap mencantumkan atribusi yang sesuai.
