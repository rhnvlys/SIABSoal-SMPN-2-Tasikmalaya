<?php

use App\Http\Controllers\AnalisisDataController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DaftarNilaiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataMentahController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\KunciJawabanController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MapelController;
use App\Http\Controllers\OlahDataController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\RekapNilaiController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\TemplateExcelController;
use App\Http\Controllers\UjianController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');
Route::get('/logo-sekolah/{path}', [ProfilSekolahController::class, 'logo'])
    ->where('path', '.*')
    ->name('logo-sekolah.show');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->middleware('role:Admin')->name('dashboard.admin');
    Route::get('/dashboard/guru', [DashboardController::class, 'guru'])->middleware('role:Guru')->name('dashboard.guru');
    Route::get('/dashboard/kepala-sekolah', [DashboardController::class, 'kepalaSekolah'])->middleware('role:Kepala Sekolah')->name('dashboard.kepala-sekolah');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::middleware(['role:Admin'])->group(function () {
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::get('guru', [GuruController::class, 'index'])->middleware('role:Admin,Kepala Sekolah')->name('guru.index');
    Route::middleware(['role:Admin'])->group(function () {
        Route::get('guru/create', [GuruController::class, 'create'])->name('guru.create');
        Route::post('guru', [GuruController::class, 'store'])->name('guru.store');
        Route::get('guru/{guru}/edit', [GuruController::class, 'edit'])->name('guru.edit');
        Route::put('guru/{guru}', [GuruController::class, 'update'])->name('guru.update');
        Route::patch('guru/{guru}', [GuruController::class, 'update']);
        Route::delete('guru/{guru}', [GuruController::class, 'destroy'])->name('guru.destroy');
    });

    Route::get('siswa', [SiswaController::class, 'index'])->middleware('role:Admin,Guru,Kepala Sekolah')->name('siswa.index');
    Route::middleware(['role:Admin'])->group(function () {
        Route::get('siswa/create', [SiswaController::class, 'create'])->name('siswa.create');
        Route::post('siswa', [SiswaController::class, 'store'])->name('siswa.store');
    });
    Route::middleware(['role:Admin,Guru'])->group(function () {
        Route::get('siswa/{siswa}/edit', [SiswaController::class, 'edit'])->name('siswa.edit');
        Route::put('siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
        Route::patch('siswa/{siswa}', [SiswaController::class, 'update']);
        Route::delete('siswa/{siswa}', [SiswaController::class, 'destroy'])->name('siswa.destroy');
    });

    Route::get('kelas', [KelasController::class, 'index'])->middleware('role:Admin,Guru,Kepala Sekolah')->name('kelas.index');
    Route::middleware(['role:Admin,Guru'])->group(function () {
        Route::post('kelas/{kela}/siswa', [KelasController::class, 'attachSiswa'])->name('kelas.siswa.store');
        Route::delete('kelas/{kela}/siswa/{siswa}', [KelasController::class, 'detachSiswa'])->name('kelas.siswa.destroy');
    });
    Route::middleware(['role:Admin'])->group(function () {
        Route::get('kelas/create', [KelasController::class, 'create'])->name('kelas.create');
        Route::post('kelas', [KelasController::class, 'store'])->name('kelas.store');
        Route::get('kelas/{kela}/edit', [KelasController::class, 'edit'])->name('kelas.edit');
        Route::put('kelas/{kela}', [KelasController::class, 'update'])->name('kelas.update');
        Route::patch('kelas/{kela}', [KelasController::class, 'update']);
        Route::delete('kelas/{kela}', [KelasController::class, 'destroy'])->name('kelas.destroy');
    });
    Route::get('kelas/{kela}', [KelasController::class, 'show'])->middleware('role:Admin,Guru,Kepala Sekolah')->name('kelas.show');

    Route::get('mapel', [MapelController::class, 'index'])->middleware('role:Admin,Kepala Sekolah')->name('mapel.index');
    Route::middleware(['role:Admin'])->group(function () {
        Route::get('mapel/create', [MapelController::class, 'create'])->name('mapel.create');
        Route::post('mapel', [MapelController::class, 'store'])->name('mapel.store');
        Route::get('mapel/{mapel}/edit', [MapelController::class, 'edit'])->name('mapel.edit');
        Route::put('mapel/{mapel}', [MapelController::class, 'update'])->name('mapel.update');
        Route::patch('mapel/{mapel}', [MapelController::class, 'update']);
        Route::delete('mapel/{mapel}', [MapelController::class, 'destroy'])->name('mapel.destroy');
    });

    Route::middleware(['role:Admin'])->group(function () {
        Route::resource('tahun-ajaran', TahunAjaranController::class)->except(['show']);
        Route::get('/profil-sekolah', [ProfilSekolahController::class, 'edit'])->name('profil-sekolah.edit');
        Route::put('/profil-sekolah', [ProfilSekolahController::class, 'update'])->name('profil-sekolah.update');
    });

    Route::middleware(['role:Admin,Kepala Sekolah'])->prefix('audit-log')->name('audit-log.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/export/excel', [AuditLogController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [AuditLogController::class, 'exportPdf'])->name('export.pdf');
    });
    Route::middleware(['role:Admin,Kepala Sekolah'])->prefix('audit-trail')->name('audit-trail.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/export/excel', [AuditLogController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [AuditLogController::class, 'exportPdf'])->name('export.pdf');
    });

    Route::get('ujian', [UjianController::class, 'index'])->middleware('role:Admin,Guru,Kepala Sekolah')->name('ujian.index');
    Route::middleware(['role:Admin,Guru,Kepala Sekolah'])->prefix('template-excel')->name('template-excel.')->group(function () {
        Route::get('/', [TemplateExcelController::class, 'index'])->name('index');
        Route::get('/download/{type}', [TemplateExcelController::class, 'download'])->name('download');
    });

    Route::middleware(['role:Admin,Guru'])->group(function () {
        Route::get('ujian/create', [UjianController::class, 'create'])->name('ujian.create');
        Route::post('ujian', [UjianController::class, 'store'])->name('ujian.store');
    });
    Route::get('ujian/{ujian}', [UjianController::class, 'show'])->middleware(['role:Admin,Guru,Kepala Sekolah', 'ujian.access'])->name('ujian.show');
    Route::middleware(['role:Admin,Guru', 'ujian.access'])->group(function () {
        Route::get('ujian/{ujian}/edit', [UjianController::class, 'edit'])->name('ujian.edit');
        Route::put('ujian/{ujian}', [UjianController::class, 'update'])->name('ujian.update');
        Route::patch('ujian/{ujian}', [UjianController::class, 'update']);
        Route::delete('ujian/{ujian}', [UjianController::class, 'destroy'])->name('ujian.destroy');
    });

    Route::middleware(['role:Admin,Guru', 'ujian.access'])->group(function () {
        Route::get('/ujian/{ujian}/kunci-jawaban', [KunciJawabanController::class, 'index'])->name('kunci-jawaban.index');
        Route::post('/ujian/{ujian}/kunci-jawaban', [KunciJawabanController::class, 'store'])->name('kunci-jawaban.store');
        Route::get('/ujian/{ujian}/kunci-jawaban/template', [KunciJawabanController::class, 'downloadTemplate'])->name('kunci-jawaban.template');
        Route::post('/ujian/{ujian}/kunci-jawaban/import', [KunciJawabanController::class, 'import'])->name('kunci-jawaban.import');
    });

    Route::middleware(['role:Admin,Guru', 'ujian.access'])->group(function () {
        Route::get('/ujian/{ujian}/data-mentah', [DataMentahController::class, 'index'])->name('data-mentah.index');
        Route::post('/ujian/{ujian}/data-mentah/manual', [DataMentahController::class, 'storeManual'])->name('data-mentah.manual');
        Route::post('/ujian/{ujian}/data-mentah/proses', [DataMentahController::class, 'proses'])->name('data-mentah.proses');
        Route::get('/ujian/{ujian}/data-mentah/template/{type}', [DataMentahController::class, 'downloadTemplate'])->name('data-mentah.template');
        Route::post('/ujian/{ujian}/data-mentah/import', [DataMentahController::class, 'import'])->name('data-mentah.import');
        Route::post('/ujian/{ujian}/data-mentah/preview', [DataMentahController::class, 'preview'])->name('data-mentah.preview');
        Route::post('/ujian/{ujian}/data-mentah/confirm-import', [DataMentahController::class, 'confirmImport'])->name('data-mentah.confirm-import');
    });

    Route::middleware(['role:Admin,Guru', 'ujian.access'])->group(function () {
        Route::get('/ujian/{ujian}/olah-data', [OlahDataController::class, 'index'])->name('olah-data.index');
        Route::post('/ujian/{ujian}/olah-data/proses', [OlahDataController::class, 'proses'])->name('olah-data.proses');
    });

    Route::get('/ujian/{ujian}/analisis-data', [AnalisisDataController::class, 'index'])
        ->middleware(['role:Admin,Guru,Kepala Sekolah', 'ujian.access'])
        ->name('analisis-data.index');
    Route::post('/ujian/{ujian}/analisis-data/proses', [AnalisisDataController::class, 'proses'])
        ->middleware(['role:Admin,Guru', 'ujian.access'])
        ->name('analisis-data.proses');

    Route::get('/ujian/{ujian}/daftar-nilai', [DaftarNilaiController::class, 'index'])
        ->middleware(['role:Admin,Guru,Kepala Sekolah', 'ujian.access'])
        ->name('daftar-nilai.index');
    Route::post('/ujian/{ujian}/daftar-nilai/manual', [DaftarNilaiController::class, 'updateManual'])
        ->middleware(['role:Admin,Guru', 'ujian.access'])
        ->name('daftar-nilai.manual');

    Route::get('/ujian/{ujian}/rekap-nilai', [RekapNilaiController::class, 'index'])
        ->middleware(['role:Admin,Guru,Kepala Sekolah', 'ujian.access'])
        ->name('rekap-nilai.index');

    Route::middleware(['role:Admin,Guru,Kepala Sekolah', 'ujian.access'])
        ->prefix('ujian/{ujian}/export')
        ->name('export.')
        ->group(function () {
            Route::get('/data-mentah/excel', [ExportController::class, 'dataMentahExcel'])->name('data-mentah.excel');
            Route::get('/data-mentah/pdf', [ExportController::class, 'dataMentahPdf'])->name('data-mentah.pdf');
            Route::get('/olah-data/excel', [ExportController::class, 'olahDataExcel'])->name('olah-data.excel');
            Route::get('/olah-data/pdf', [ExportController::class, 'olahDataPdf'])->name('olah-data.pdf');
            Route::get('/analisis/excel', [ExportController::class, 'analisisExcel'])->name('analisis.excel');
            Route::get('/analisis/pdf', [ExportController::class, 'analisisPdf'])->name('analisis.pdf');
            Route::get('/daftar-nilai/excel', [ExportController::class, 'daftarNilaiExcel'])->name('daftar-nilai.excel');
            Route::get('/daftar-nilai/pdf', [ExportController::class, 'daftarNilaiPdf'])->name('daftar-nilai.pdf');
            Route::get('/rekap-nilai/excel', [ExportController::class, 'rekapNilaiExcel'])->name('rekap-nilai.excel');
            Route::get('/rekap-nilai/pdf', [ExportController::class, 'rekapNilaiPdf'])->name('rekap-nilai.pdf');
        });
});
