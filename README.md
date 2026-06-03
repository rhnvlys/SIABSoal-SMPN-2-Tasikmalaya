<div align="center">

<img src="docs/images/logo-smpn2-tasikmalaya.svg" alt="Logo SMPN 2 Tasikmalaya" width="130">

# SIABSoal SMPN 2 Tasikmalaya

### Sistem Informasi Analisis Butir Soal Berbasis Web sebagai Pengganti SPSS untuk Evaluasi Kualitas Soal dan Rekap Nilai

<p>
  <img src="https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 10">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.1+">
  <img src="https://img.shields.io/badge/MySQL%20%2F%20TiDB-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL / TiDB">
  <img src="https://img.shields.io/badge/Blade-Frontend-F05340?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel Blade">
  <img src="https://img.shields.io/badge/Docker%20%2F%20Koyeb-Deployment-121212?style=for-the-badge&logo=docker&logoColor=white" alt="Docker / Koyeb">
</p>

<p>
  <b>Kerja Praktik - Program Studi Teknik Informatika</b><br>
  Studi Kasus: <b>SMPN 2 Tasikmalaya</b>
</p>

</div>

---

## Ringkasan Project

**SIABSoal SMPN 2 Tasikmalaya** adalah aplikasi web yang membantu sekolah melakukan analisis butir soal, evaluasi kualitas soal, dan rekapitulasi nilai siswa. Sistem ini mendukung guru dalam mengolah jawaban siswa, menghitung indikator kualitas soal, serta menyajikan laporan nilai secara lebih rapi dan terpusat.

Project ini dikembangkan sebagai website Kerja Praktik dengan fokus pada alur analisis T1 sampai T5: Data Mentah, Olah Data, Analisis DP dan TK, Daftar Nilai, dan Rekap Nilai.

---

## Latar Belakang

Analisis butir soal sering dilakukan secara manual menggunakan lembar kerja atau aplikasi statistik seperti SPSS. Proses tersebut kurang praktis untuk kebutuhan operasional guru karena membutuhkan pengolahan data berulang, format khusus, dan ketelitian tinggi.

SIABSoal dibuat sebagai alternatif berbasis web agar proses input jawaban, pengolahan benar/salah, analisis kualitas soal, serta rekap nilai dapat dilakukan dalam satu sistem yang lebih mudah digunakan oleh sekolah.

---

## Tujuan Sistem

- Membantu analisis butir soal secara digital.
- Mempermudah input jawaban siswa.
- Mengolah jawaban benar/salah menjadi skor `1` dan `0`.
- Mengelompokkan siswa ke kelompok atas dan kelompok bawah.
- Menghasilkan analisis Daya Pembeda (DP).
- Menghasilkan analisis Tingkat Kesukaran (TK).
- Menyediakan daftar nilai dan rekap nilai siswa.

---

## Fitur Utama

- Login dan autentikasi pengguna.
- Dashboard sesuai role.
- Manajemen data guru.
- Manajemen data siswa.
- Manajemen data kelas.
- Manajemen mata pelajaran.
- Manajemen tahun ajaran.
- Manajemen ujian.
- Pengelolaan kunci jawaban.
- Data Mentah T1.
- Olah Data T2.
- Analisis Data T3.
- Daftar Nilai T4.
- Rekap Nilai T5.
- Profil sekolah dan logo sekolah.
- Manajemen user.
- Audit trail atau riwayat aktivitas.
- Export laporan PDF dan Excel.
- Template import Excel.

---

## Hak Akses

| Role | Hak Akses Utama |
|---|---|
| Admin | Mengelola user, master data, seluruh ujian, profil sekolah, audit trail, dan laporan. |
| Guru | Mengelola ujian miliknya, kunci jawaban, data mentah, proses analisis, dan laporan ujian miliknya. |
| Kepala Sekolah | Melihat dashboard monitoring, laporan, data sekolah read-only, audit trail, dan export laporan. |

---

## Konsep Analisis

1. Guru membuat ujian dan mengisi kunci jawaban.
2. Jawaban siswa diinput manual atau diimport dari Excel.
3. Sistem mengonversi jawaban menjadi skor `1` untuk benar dan `0` untuk salah.
4. Sistem menghitung nilai siswa dan mengurutkan hasil.
5. Siswa dikelompokkan menjadi kelompok atas dan kelompok bawah berdasarkan nilai.
6. Sistem menghitung Daya Pembeda (DP).
7. Sistem menghitung Tingkat Kesukaran (TK).
8. Hasil analisis ditampilkan sebagai Analisis Data T3.
9. Nilai siswa disajikan pada Daftar Nilai T4.
10. Ringkasan nilai dan kualitas soal disajikan pada Rekap Nilai T5.

---

## Teknologi

| Bagian | Teknologi |
|---|---|
| Framework | Laravel 10 |
| Bahasa | PHP 8.1+ |
| Template | Blade |
| Frontend | HTML, CSS, JavaScript |
| Database | MySQL / TiDB |
| Import dan Export Excel | Laravel Excel |
| Export PDF | DomPDF |
| Container | Docker |
| Hosting | Koyeb |
| Version Control | Git dan GitHub |

---

## Instalasi Lokal

```bash
git clone https://github.com/rhnvlys/SIABSoal-SMPN-2-Tasikmalaya.git
cd SIABSoal-SMPN-2-Tasikmalaya
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Untuk Windows PowerShell, gunakan perintah berikut untuk menyalin file environment:

```powershell
copy .env.example .env
```

Setelah server berjalan, akses aplikasi melalui:

```text
http://127.0.0.1:8000
```

---

## Deployment Docker/Koyeb

Project ini disiapkan untuk deployment menggunakan Docker di Koyeb. File utama deployment:

```text
Dockerfile
docker/koyeb-start.sh
.env.koyeb.example
```

Gunakan branch:

```text
work/siabsoal-build
```

Environment production harus diatur melalui dashboard hosting, bukan disimpan di repository.

Contoh placeholder environment:

```env
APP_NAME="SIABSoal SMPN 2 Tasikmalaya"
APP_ENV=production
APP_KEY=base64:GENERATE_DI_LOKAL_LALU_INPUT_DI_DASHBOARD
APP_DEBUG=false
APP_URL=https://domain-hosting-kamu

DB_CONNECTION=mysql
DB_HOST=ISI_DARI_DASHBOARD_DATABASE
DB_PORT=4000
DB_DATABASE=ISI_DARI_DASHBOARD_DATABASE
DB_USERNAME=ISI_DARI_DASHBOARD_DATABASE
DB_PASSWORD=ISI_DARI_DASHBOARD_DATABASE

RUN_MIGRATIONS=true
RUN_SEEDERS=false
CACHE_CONFIG=true
```

Jangan menaruh password, token, APP_KEY production, credential database, atau secret lain ke dalam repository.

---

## Keamanan Repository

File berikut tidak boleh dipush ke GitHub:

- `.env` dan semua environment lokal.
- Password database, token hosting, API key, OAuth secret, dan APP_KEY production.
- Database dump seperti `.sql`, `.sqlite`, dan `.dump`.
- File backup seperti `.zip`, `.rar`, `.7z`, `.bak`, dan `.backup`.
- Folder dependency seperti `vendor/` dan `node_modules/`.
- File runtime seperti `storage/logs/`, cache, session, dan `public/storage/`.


Seluruh credential production harus disimpan melalui dashboard hosting atau environment variable yang aman.

---

## Screenshot

Screenshot aplikasi dapat ditambahkan ke folder berikut jika sudah dipastikan tidak mengandung data sensitif:

```text
docs/images/dashboard.png
docs/images/data-mentah.png
docs/images/analisis-data.png
docs/images/rekap-nilai.png
docs/images/daftar-nilai.png
```

Contoh penggunaan setelah screenshot tersedia:

```md
![Dashboard](docs/images/dashboard.png)
![Data Mentah](docs/images/data-mentah.png)
![Analisis Data](docs/images/analisis-data.png)
![Rekap Nilai](docs/images/rekap-nilai.png)
![Daftar Nilai](docs/images/daftar-nilai.png)
```

---

## Pengembang

**Raihan Nouval Yashir**  
Mahasiswa Teknik Informatika  
Kerja Praktik di SMPN 2 Tasikmalaya

---

## Catatan Akademik

Aplikasi ini dikembangkan untuk kebutuhan Kerja Praktik. Penggunaan data siswa, data guru, dan data akademik harus memperhatikan keamanan data serta privasi pihak sekolah.
