# AGENTS.md — SIABSoal SMPN 2 Tasikmalaya

## Scope Project

Instruksi ini berlaku hanya untuk folder kerja berikut dan seluruh subfoldernya:

```txt
D:\KP\SIABSoal SMPN 2 Tasikmalaya
```

Jangan membaca, membuat, mengubah, memindahkan, atau menghapus file di luar folder project tersebut tanpa instruksi eksplisit dari user. Jangan mengubah folder `D:\KP\Ref KP` karena folder itu berisi referensi KP, bukan source code utama.

## Skill Wajib

Untuk semua task di project ini, gunakan skill lokal:

```txt
.agents/skills/siabsoal-kp-professional/SKILL.md
```

Jika sistem skill Codex tidak aktif otomatis, baca file skill tersebut terlebih dahulu sebelum membuat analisis, kode, migrasi, controller, view, atau perubahan database.

## Identitas Project

- Nama project: SIABSoal SMPN 2 Tasikmalaya
- Jenis: Website Kerja Praktik
- Judul acuan: Pengembangan Sistem Analisis Butir Soal Berbasis Web sebagai Pengganti SPSS untuk Evaluasi Kualitas Soal dan Rekap Nilai
- Stack utama: Laravel, MySQL, Laragon, Blade, Tailwind CSS atau Bootstrap sesuai project berjalan
- Fokus utama: analisis butir soal, import Excel, rekap nilai, dashboard, export laporan, UI/UX sekolah, dan keamanan role user

## Aturan Kerja Codex

1. Analisis kebutuhan sebelum mengubah file.
2. Prioritaskan solusi sederhana, stabil, aman, dan mudah dijelaskan di laporan KP.
3. Jangan mengganti stack ke Next.js, React SPA, Node backend, atau database lain kecuali diminta eksplisit.
4. Jangan menghapus fitur lama tanpa menjelaskan dampaknya.
5. Jangan membuat dependency berat jika fitur bisa dibuat dengan Laravel bawaan, Blade, JavaScript ringan, Chart.js, Laravel Excel, atau DomPDF.
6. Gunakan validasi request, middleware role, foreign key, dan transaksi database untuk proses penting.
7. Untuk import Excel, selalu validasi format kolom, duplikasi data, relasi kelas/mapel/ujian, dan error row.
8. Untuk perhitungan analisis butir soal, jelaskan rumus, asumsi, dan hasil interpretasi.
9. Setelah mengubah kode, berikan daftar file yang diubah, alasan perubahan, dan checklist pengujian.
10. Jika informasi kurang, gunakan asumsi eksplisit. Jangan menebak struktur database atau nama file tanpa memeriksa repository.

## Perintah Lokal Umum

Gunakan perintah sesuai kondisi project:

```powershell
php artisan serve
php artisan migrate
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
composer install
composer update
npm install
npm run dev
```

Jangan menjalankan `migrate:fresh` pada database berisi data penting tanpa izin eksplisit.

## Definition of Done

Perubahan dianggap selesai jika:

1. Fitur sesuai kebutuhan KP.
2. Route, controller, model, migration, view, dan relasi database konsisten.
3. Tidak ada error validasi dasar.
4. Hak akses role tidak bocor.
5. UI rapi, ringan, responsive, dan nyaman untuk admin/guru sekolah.
6. Perhitungan analisis bisa ditelusuri dari data input sampai output.
7. Ada catatan pengujian manual atau otomatis.
