<div align="center">

<img src="docs/images/logo-smpn2-tasikmalaya.svg" alt="Logo SMPN 2 Tasikmalaya" width="130">

# SIABSoal SMPN 2 Tasikmalaya

### Sistem Informasi Analisis Butir Soal Berbasis Web sebagai Pengganti SPSS untuk Evaluasi Kualitas Soal dan Rekap Nilai

<p>
  <img src="https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 10">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.1+">
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Blade-Frontend-F05340?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel Blade">
  <img src="https://img.shields.io/badge/Vercel-Production-000000?style=for-the-badge&logo=vercel&logoColor=white" alt="Vercel">
</p>

<p>
  <b>Kerja Praktik - Program Studi Teknik Informatika</b><br>
  Studi Kasus: <b>SMPN 2 Tasikmalaya</b>
</p>

</div>

---

## Status Project

| Item | Keterangan |
|---|---|
| Branch utama pengembangan | `work/siabsoal-build` |
| Production | https://siabsoal-smpn2-tasikmalaya-ten.vercel.app |
| Localhost | `http://127.0.0.1:8000` |
| Framework | Laravel 10 |
| Database | MySQL untuk lokal dan production |
| Frontend build | Vite |

---

## Ringkasan Project

**SIABSoal SMPN 2 Tasikmalaya** adalah sistem informasi berbasis web untuk membantu guru dalam administrasi penilaian, pengolahan jawaban siswa, analisis butir soal, daftar nilai, dan rekap nilai. Sistem mendukung template Excel agar guru dapat mengisi data dengan format yang familiar, kemudian data diproses otomatis oleh sistem.

Project ini dikembangkan sebagai website Kerja Praktik dengan fokus pada alur analisis T1 sampai T5: Data Mentah, Olah Data, Analisis DP dan TK, Daftar Nilai, dan Rekap Nilai. Repository ini hanya menyimpan source code publik; credential, environment production, cache, log, dan file lokal tidak disimpan di GitHub.

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
- Manajemen ujian dengan jenis penilaian, tujuan pembelajaran, lingkup materi, dan KKTP/KKM.
- Pengelolaan kunci jawaban.
- Menu Template Excel untuk data siswa, kunci jawaban, jawaban A/B/C/D/E, skor 0/1, daftar nilai, dan rekap nilai.
- Data Mentah T1.
- Proses batch Data Mentah T1 agar pengolahan data besar lebih stabil di hosting.
- Olah Data T2.
- Analisis Data T3.
- Daftar Nilai T4.
- Rekap Nilai T5.
- Profil sekolah dan logo sekolah.
- Manajemen user.
- Audit trail atau riwayat aktivitas.
- Export laporan PDF dan Excel.
- Template import dan export Excel.
- Pagination pada tabel besar untuk menjaga performa.

---

## Hak Akses

| Role | Hak Akses Utama |
|---|---|
| Admin | Mengelola user, master data, seluruh ujian, profil sekolah, audit trail, dan laporan. |
| Guru | Mengelola ujian miliknya, kunci jawaban, data mentah, proses analisis, dan laporan ujian miliknya. |
| Kepala Sekolah | Melihat dashboard monitoring, laporan, data sekolah read-only, audit trail, dan export laporan. |

---

## Konsep Analisis

1. Guru membuat ujian lengkap dengan jenis penilaian, tujuan pembelajaran, lingkup materi, dan KKTP/KKM.
2. Guru mengisi kunci jawaban.
3. Guru download template Excel yang sesuai dengan ujian.
4. Jawaban siswa diinput manual atau diimport dari Excel.
5. Sistem mengonversi jawaban menjadi skor `1` untuk benar dan `0` untuk salah.
6. Sistem menghitung nilai siswa dan mengurutkan hasil.
7. Siswa dikelompokkan menjadi kelompok atas dan kelompok bawah berdasarkan nilai.
8. Sistem menghitung Daya Pembeda (DP) dan Tingkat Kesukaran (TK).
9. Hasil analisis ditampilkan sebagai Analisis Data T3.
10. Nilai siswa disajikan pada Daftar Nilai T4.
11. Ringkasan nilai dan kualitas soal disajikan pada Rekap Nilai T5.

---

## Teknologi

| Bagian | Teknologi |
|---|---|
| Framework | Laravel 10 |
| Bahasa | PHP 8.1+ |
| Template | Blade |
| Frontend | HTML, CSS, JavaScript |
| Database | MySQL |
| Import dan Export Excel | Laravel Excel |
| Export PDF | DomPDF |
| Frontend Build | Vite |
| Hosting | Vercel |
| Deployment alternatif | Docker/Koyeb jika dibutuhkan |
| Version Control | Git dan GitHub |

---

## Panduan Instalasi di Laptop Baru

Ikuti langkah-langkah di bawah ini untuk memasang dan menjalankan aplikasi **SIABSoal** di laptop yang baru dari awal:

### 1. Prasyarat Sistem (Prerequisites)
Pastikan laptop Anda sudah memiliki software berikut terpasang:
- **PHP** versi 8.1 atau 8.2.
- **Composer** (Dependency Manager untuk PHP).
- **Node.js** (versi LTS terbaru) & **NPM**.
- **Laragon** (sangat direkomendasikan untuk Windows) atau **XAMPP** sebagai server web dan basis data MySQL lokal.

### 2. Kloning Repository
Buka terminal (Git Bash, Command Prompt, atau PowerShell), arahkan ke folder direktori kerja Anda, lalu jalankan perintah:
```bash
git clone https://github.com/rhnvlys/SIABSoal-SMPN-2-Tasikmalaya.git
cd SIABSoal-SMPN-2-Tasikmalaya
```

### 3. Mengatur Environment File (`.env`)
Salin file `.env.example` menjadi `.env`:
* **Melalui Git Bash / Linux / macOS:**
  ```bash
  cp .env.example .env
  ```
* **Melalui Windows PowerShell:**
  ```powershell
  copy .env.example .env
  ```
* **Melalui Command Prompt (CMD):**
  ```cmd
  copy .env.example .env
  ```

Buka file `.env` baru tersebut menggunakan text editor, lalu pastikan pengaturan database Anda sudah sesuai (secara default menggunakan Laragon/XAMPP):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siabsoal_smpn2
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Membuat Database Baru
1. Jalankan MySQL di Laragon atau XAMPP Anda.
2. Buka **phpMyAdmin** atau tool database client seperti **HeidiSQL** / **DBeaver**.
3. Buat database baru dengan nama `siabsoal_smpn2`.

### 5. Mengunduh Dependencies
Instal seluruh package PHP dan Javascript yang dibutuhkan oleh aplikasi:
```bash
composer install
npm install
```

### 6. Menghasilkan Key Aplikasi
Jalankan perintah berikut untuk menghasilkan security key unik untuk aplikasi Anda:
```bash
php artisan key:generate
```

### 7. Migrasi Database & Seeding Data
Jalankan migrasi untuk membuat seluruh tabel database beserta data master awal, konfigurasi sekolah, dan data simulasi ujian lengkap (Matematika kelas IX A):
```bash
php artisan migrate:fresh --seed
```

### 8. Kompilasi Aset Frontend & Menjalankan Server
Kompilasi asset frontend menggunakan Vite dan jalankan server pengembangan lokal Laravel:

* **Kompilasi aset (sekali jalankan):**
  ```bash
  npm run build
  ```
* **Menjalankan server Laravel:**
  ```bash
  php artisan serve
  ```

Setelah server berjalan, akses aplikasi melalui web browser di:
```text
http://127.0.0.1:8000
```

---

## Akun Demo & Uji Coba

Gunakan akun-akun di bawah ini untuk menguji coba hak akses role sistem:

| Role | Username | Password | Deskripsi Akses |
|---|---|---|---|
| **Admin** | `admin` | `admin123` | Akses penuh pengelolaan master data, log aktivitas, dan user management. |
| **Guru** | `guru` | `guru123` | Akses manajemen ujian miliknya, entry jawaban, dan proses analisis butir soal (T1-T5). |
| **Kepala Sekolah** | `kepsek` | `kepsek123` | Akses monitoring dashboard, read-only laporan, audit log, dan ekspor. |

---

## Data Dummy Excel untuk Presentasi/Uji Coba

Untuk keperluan demonstrasi kepada Dosen Pembimbing, kami telah menyediakan template data dummy ujian lengkap yang sudah berisi data siswa dan format isian jawaban/skor:
- File ini dapat diunduh langsung di root project dengan nama: **`Template_Lengkap_Dummy_Ujian.xlsx`** (atau diakses di browser pada url **`http://localhost:8000/Template_Lengkap_Dummy_Ujian.xlsx`** saat server lokal Anda berjalan).
- File ini berisi **16 sheet** (Identitas, Kelas, Siswa, Kunci Jawaban, Jawaban Siswa, Skor, T1-T5, dan referensi parameter) yang telah disesuaikan dengan kurikulum dan struktur administrasi sekolah.

---

## Deployment Vercel

Project ini sudah disiapkan untuk deployment production di Vercel menggunakan konfigurasi berikut:

```text
vercel.json
api/index.php
.vercelignore
.env.production.example
```

Perintah deploy dari root project:

```bash
vercel deploy --prod --yes
```

URL production aktif:

```text
https://siabsoal-smpn2-tasikmalaya-ten.vercel.app
```

Environment production harus diatur melalui dashboard Vercel, bukan disimpan di repository.

Contoh placeholder environment production:

```env
APP_NAME="SIABSoal SMPN 2 Tasikmalaya"
APP_ENV=production
APP_KEY=base64:GENERATE_DI_LOKAL_LALU_INPUT_DI_DASHBOARD
APP_DEBUG=false
APP_URL=https://siabsoal-smpn2-tasikmalaya-ten.vercel.app

DB_CONNECTION=mysql
DB_HOST=ISI_DARI_DASHBOARD_DATABASE
DB_PORT=3306
DB_DATABASE=ISI_DARI_DASHBOARD_DATABASE
DB_USERNAME=ISI_DARI_DASHBOARD_DATABASE
DB_PASSWORD=ISI_DARI_DASHBOARD_DATABASE

RUN_MIGRATIONS=true
RUN_SEEDERS=false
CACHE_CONFIG=true
```

Jangan menaruh password, token, APP_KEY production, credential database, atau secret lain ke dalam repository.

---

## Deployment Alternatif Docker/Koyeb

File berikut masih tersedia jika project perlu dijalankan melalui Docker atau Koyeb:

```text
Dockerfile
docker/koyeb-start.sh
.env.koyeb.example
```

Gunakan environment variable dari dashboard hosting. Jangan commit credential production.

---

## Keamanan Repository

File berikut tidak boleh dipush ke GitHub:

- `.env` dan semua environment lokal.
- Password database, token hosting, API key, OAuth secret, dan APP_KEY production.
- Database dump seperti `.sql`, `.sqlite`, dan `.dump`.
- File backup seperti `.zip`, `.rar`, `.7z`, `.bak`, dan `.backup`.
- Folder dependency seperti `vendor/` dan `node_modules/`.
- File runtime seperti `storage/logs/`, cache, session, dan `public/storage/`.
- File kerja lokal seperti `.agents/`, `.codegraph/`, `.vercel/`, file AI, dan dokumen lokal yang tidak diperlukan publik.


Seluruh credential production harus disimpan melalui dashboard hosting atau environment variable yang aman.

Catatan: `README.md` adalah dokumen publik repository dan menjadi pengecualian karena ditampilkan di halaman GitHub.

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
