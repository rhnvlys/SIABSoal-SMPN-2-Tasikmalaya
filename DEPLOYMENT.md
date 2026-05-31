# Deployment Vercel SIABSoal SMPN 2 Tasikmalaya

## Ringkasan

- Project: SIABSoal SMPN 2 Tasikmalaya
- URL production: https://siabsoal-smpn2-kp.vercel.app/
- Platform: Vercel
- Repository: https://github.com/rhnvlys/SIABSoal-SMPN-2-Tasikmalaya
- Branch production: `work/siabsoal-build`
- Mode deployment: Laravel/PHP app di Vercel menggunakan PHP Community Runtime.

## Alur Auto Deploy

1. Perbaiki kode di local.
2. Jalankan test dan build local.
3. Commit perubahan.
4. Push ke branch `work/siabsoal-build`.
5. Vercel otomatis build dan deploy dari branch tersebut.

## Konfigurasi Vercel

File `vercel.json` memakai:

- Function entry point: `api/index.php`
- Runtime: `vercel-php@0.7.4`
- Route Laravel: semua request non-static diarahkan ke `/api/index.php`
- Route static asset: `/build`, `/assets`, `/css`, `/js`, `/storage`, `/favicon.ico`, dan `/robots.txt` diarahkan ke folder `public`

Build command yang disarankan di Vercel Project Settings jika perlu override:

```bash
composer install --no-dev --optimize-autoloader && npm install && npm run build
```

Catatan: Blade project saat ini masih memakai asset dari `public/css/app.css` dan `public/js/app.js`. `npm run build` tetap aman dijalankan karena project memiliki konfigurasi Vite.

Output directory: `public`, karena Vercel mendeteksi build Vite dan perlu mengambil asset hasil build dari folder public Laravel.

## Environment Variables Vercel

Isi lewat Vercel Project Settings -> Environment Variables. Jangan commit `.env` atau credential asli.

Minimal wajib:

```txt
APP_NAME="SIABSoal SMPN 2 Tasikmalaya"
APP_ENV=production
APP_KEY=base64:ISI_APP_KEY_PRODUCTION
APP_DEBUG=false
APP_URL=https://siabsoal-smpn2-kp.vercel.app

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=ISI_HOST_DATABASE
DB_PORT=3306
DB_DATABASE=ISI_NAMA_DATABASE
DB_USERNAME=ISI_USERNAME_DATABASE
DB_PASSWORD=ISI_PASSWORD_DATABASE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

MAIL_MAILER=log
VITE_APP_NAME="SIABSoal SMPN 2 Tasikmalaya"
```

Jika memakai TiDB, gunakan `DB_PORT=4000`. Jika database membutuhkan SSL CA, simpan CA di jalur yang tersedia saat build/runtime lalu isi `DB_SSL_CA`.

## APP_KEY Production

Buat APP_KEY satu kali di local:

```bash
php artisan key:generate --show
```

Copy hasilnya ke Vercel Environment Variables:

```txt
APP_KEY=base64:xxxxxxxx
```

Jangan generate APP_KEY otomatis setiap deploy dan jangan commit nilai APP_KEY production.

## Database dan Migration

Vercel deployment tidak boleh mereset database.

Aturan:

- Jangan jalankan `php artisan migrate:fresh` di production.
- Jangan jalankan seeder otomatis yang bisa membuat data dobel.
- Jika migration harus dijalankan, gunakan:

```bash
php artisan migrate --force
```

Untuk production yang sudah berisi data, migration lebih aman dijalankan manual dari environment yang terhubung ke database production.

Seeder wajib idempotent dengan `firstOrCreate` atau `updateOrCreate`. Jangan hapus data siswa, kelas, ujian, jawaban, analisis, nilai, atau audit trail production.

Migration tambahan deployment membuat tabel `sessions` untuk `SESSION_DRIVER=database` dan kolom `logo_url` untuk opsi logo eksternal.

## Logo Sekolah di Vercel

Vercel bukan storage permanen untuk upload file lokal. Project mendukung dua mode logo:

- Upload file ke disk `public` untuk lingkungan yang mendukung storage lokal.
- URL logo eksternal lewat Profil Sekolah untuk Cloudinary, Supabase Storage, atau URL publik lain.

Logo ditampilkan di login, navbar, sidebar, landing page, profil sekolah, dan export PDF jika file lokal tersedia. Jika memakai URL eksternal, tampilan web memakai URL tersebut; PDF bergantung dukungan remote image DomPDF.

Setiap upload, ganti, atau perubahan URL logo dicatat ke audit trail.

## Checklist Sebelum Push

Jalankan:

```bash
composer install
php artisan optimize:clear
php artisan route:list
php artisan migrate
npm install
npm run build
```

Pastikan:

- Route tidak error.
- Build frontend tidak error.
- Landing page bisa dibuka.
- Login bisa dipakai.
- Dashboard sesuai role.
- Asset CSS/JS/logo/favicon tidak 404.
- Fitur T1 sampai T5 tetap berjalan.
- Export/import dan audit trail tidak error.

## Checklist Setelah Deploy

1. Buka https://siabsoal-smpn2-kp.vercel.app/
2. Pastikan landing page tampil.
3. Klik Login.
4. Login role Admin, Guru, dan Kepala Sekolah.
5. Pastikan dashboard sesuai role.
6. Pastikan CSS, JS, logo, dan favicon tidak 404.
7. Pastikan tidak ada error 500.
8. Cek fitur Data Ujian, T1, T2, T3, T4, T5, Export, Audit Trail, dan Upload Logo.
9. Jika ada error, cek Vercel Deployment Logs.

## File yang Tidak Boleh Di-commit

- `.env` dan semua credential asli
- `vendor`
- `node_modules`
- `.codex`
- `.agents`
- `skills`
- `docs/superpowers`
- file backup/zip/dummy/screenshot testing
- file prompt internal
