# Deploy Gratis SIABSoal ke Koyeb + TiDB Cloud

Dokumen ini menyiapkan jalur hosting gratis untuk demo KP SIABSoal tanpa mengganti stack utama Laravel dan MySQL.

## Pilihan hosting

Rekomendasi utama:

- Web app: Koyeb Free Instance, karena mendukung PHP melalui Git/Docker dan memberi URL `*.koyeb.app`.
- Database: TiDB Cloud Starter/Serverless, karena kompatibel dengan protokol MySQL sehingga Laravel tetap memakai `DB_CONNECTION=mysql`.
- Builder: Dockerfile project ini. Jalur ini lebih terkendali untuk Laravel karena extension PHP, Apache document root, migration, dan cache production diatur eksplisit.

Alternatif:

- InfinityFree: gratis PHP + MySQL, tetapi tidak ada SSH. Cocok jika ingin upload manual via FTP, tetapi kurang nyaman untuk Laravel karena Composer dan Artisan dijalankan dari lokal.
- Render: bisa menjalankan PHP via Docker, tetapi database managed gratisnya lebih kuat ke PostgreSQL. Untuk project ini tetap butuh database MySQL-compatible eksternal.

## 1. Buat database TiDB Cloud

1. Daftar atau login ke TiDB Cloud.
2. Buat cluster Serverless/Starter.
3. Buka menu Connect.
4. Pilih koneksi MySQL compatible.
5. Catat host, port, database, username, dan password.
6. Pastikan koneksi menggunakan TLS/SSL. TiDB Serverless mewajibkan koneksi TLS.

Nilai environment Laravel yang dipakai:

```txt
DB_CONNECTION=mysql
DB_HOST=<host TiDB>
DB_PORT=4000
DB_DATABASE=<nama database>
DB_USERNAME=<username>
DB_PASSWORD=<password>
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
```

Gunakan plan Starter/Serverless dan set spend limit supaya tetap di jalur gratis.

## 2. Siapkan APP_KEY

Jalankan di lokal:

```powershell
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Simpan hasilnya sebagai `APP_KEY` di Koyeb. Jangan commit nilai tersebut ke GitHub.

## 3. Deploy web app di Koyeb

1. Login ke Koyeb.
2. Pilih Create Web Service.
3. Pilih GitHub repository:

```txt
rhnvlys/SIABSoal-SMPN-2-Tasikmalaya
```

4. Pilih branch:

```txt
work/siabsoal-build
```

5. Pilih Dockerfile sebagai builder.
6. Set port service ke:

```txt
8000
```

7. Isi environment variables mengikuti `.env.koyeb.example`.
8. Deploy.

Catatan upload:

- File runtime yang dibutuhkan ada di `app`, `bootstrap`, `config`, `database`, `docker`, `public`, `resources/views`, `routes`, `artisan`, `composer.json`, `composer.lock`, `Dockerfile`, dan file konfigurasi deploy.
- File internal `.agents`, `AGENTS.md`, `docs/superpowers`, `.env`, `vendor`, `node_modules`, `tests`, cache, dan dokumen non-runtime tidak dimasukkan ke image Docker.
- `.koyebignore` hanya mencegah redeploy otomatis karena perubahan dokumen. Koyeb tetap menyalin repository saat build, jadi file internal harus benar-benar tidak ter-track Git atau harus dikecualikan oleh `.dockerignore`.

## 4. Environment production minimal

Gunakan nilai berikut di Koyeb:

```txt
APP_NAME=SIABSoal SMPN 2 Tasikmalaya
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<url-app-koyeb>
LOG_CHANNEL=stderr
DB_CONNECTION=mysql
DB_HOST=<host TiDB>
DB_PORT=4000
DB_DATABASE=<database TiDB>
DB_USERNAME=<username TiDB>
DB_PASSWORD=<password TiDB>
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
PORT=8000
SESSION_SECURE_COOKIE=true
RUN_MIGRATIONS=true
RUN_SEEDERS=true
```

Setelah database sudah berisi data tetap, ubah:

```txt
RUN_SEEDERS=false
```

Seeder project ini dibuat idempotent, tetapi untuk data demo yang sudah diedit langsung di production lebih aman tidak menjalankan seeder ulang.

## 5. Akun login demo

Jika `RUN_SEEDERS=true`, akun awal:

```txt
Admin
username: admin
password: admin123

Guru
username: guru
password: guru123
```

Ganti password setelah deploy jika link akan dibagikan.

## 6. Checklist verifikasi

- Halaman landing terbuka dari URL Koyeb.
- Login admin berhasil.
- Dashboard terbuka tanpa error 500.
- `APP_DEBUG=false` di environment Koyeb.
- Database TiDB berisi tabel hasil migration.
- Import Excel diuji dengan file valid dan file salah format.
- Export PDF/Excel diuji minimal satu ujian.
- Setelah demo awal, `RUN_SEEDERS=false`.
