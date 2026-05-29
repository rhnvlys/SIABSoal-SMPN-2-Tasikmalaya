<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\MapelController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\UjianController;
use App\Http\Controllers\KunciJawabanController;
use App\Http\Controllers\DataMentahController;
use App\Http\Controllers\OlahDataController;
use App\Http\Controllers\AnalisisDataController;
use App\Http\Controllers\DaftarNilaiController;
use App\Http\Controllers\RekapNilaiController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes — SIABSoal SMPN 2 Tasikmalaya
|--------------------------------------------------------------------------
*/

// ========== Public ==========
Route::view('/', 'landing')->name('landing');

// ========== Auth ==========
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ========== Authenticated Routes ==========
Route::middleware(['auth'])->group(function () {

    // Dashboard (semua role)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil Saya
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // --------------------------------------------------
    // Manajemen User (Admin only)
    // --------------------------------------------------
    Route::middleware(['role:Admin'])->group(function () {
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('users', UserController::class)->except(['show']);
    });

    // --------------------------------------------------
    // Data Guru (Admin only)
    // --------------------------------------------------
    Route::middleware(['role:Admin'])->group(function () {
        Route::resource('guru', GuruController::class)->except(['show']);
    });

    // --------------------------------------------------
    // Data Siswa (Admin, Operator)
    // --------------------------------------------------
    Route::middleware(['role:Admin,Operator'])->group(function () {
        Route::resource('siswa', SiswaController::class)->except(['show']);
    });

    // --------------------------------------------------
    // Data Kelas (Admin, Operator)
    // --------------------------------------------------
    Route::middleware(['role:Admin,Operator'])->group(function () {
        Route::resource('kelas', KelasController::class);
    });

    // --------------------------------------------------
    // Mata Pelajaran (Admin, Operator)
    // --------------------------------------------------
    Route::middleware(['role:Admin,Operator'])->group(function () {
        Route::resource('mapel', MapelController::class)->except(['show']);
    });

    // --------------------------------------------------
    // Tahun Ajaran (Admin only)
    // --------------------------------------------------
    Route::middleware(['role:Admin'])->group(function () {
        Route::resource('tahun-ajaran', TahunAjaranController::class)->except(['show']);
    });

    // --------------------------------------------------
    // Profil Sekolah (Admin only)
    // --------------------------------------------------
    Route::middleware(['role:Admin'])->group(function () {
        Route::get('/profil-sekolah', [ProfilSekolahController::class, 'edit'])->name('profil-sekolah.edit');
        Route::put('/profil-sekolah', [ProfilSekolahController::class, 'update'])->name('profil-sekolah.update');
    });

    // --------------------------------------------------
    // Data Ujian (Admin, Guru)
    // --------------------------------------------------
    Route::middleware(['role:Admin,Guru', 'ujian.access'])->group(function () {
        Route::resource('ujian', UjianController::class);
    });

    // --------------------------------------------------
    // Kunci Jawaban (Admin, Guru)
    // --------------------------------------------------
    Route::middleware(['role:Admin,Guru', 'ujian.access'])->group(function () {
        Route::get('/ujian/{ujian}/kunci-jawaban', [KunciJawabanController::class, 'index'])->name('kunci-jawaban.index');
        Route::post('/ujian/{ujian}/kunci-jawaban', [KunciJawabanController::class, 'store'])->name('kunci-jawaban.store');
        Route::get('/ujian/{ujian}/kunci-jawaban/template', [KunciJawabanController::class, 'downloadTemplate'])->name('kunci-jawaban.template');
        Route::post('/ujian/{ujian}/kunci-jawaban/import', [KunciJawabanController::class, 'import'])->name('kunci-jawaban.import');
    });

    // --------------------------------------------------
    // Data Mentah T1 (Admin, Guru, Operator)
    // --------------------------------------------------
    Route::middleware(['role:Admin,Guru,Operator', 'ujian.access'])->group(function () {
        Route::get('/ujian/{ujian}/data-mentah', [DataMentahController::class, 'index'])->name('data-mentah.index');
        Route::post('/ujian/{ujian}/data-mentah/manual', [DataMentahController::class, 'storeManual'])->name('data-mentah.manual');
        Route::post('/ujian/{ujian}/data-mentah/proses', [DataMentahController::class, 'proses'])->name('data-mentah.proses');
        Route::get('/ujian/{ujian}/data-mentah/template/{type}', [DataMentahController::class, 'downloadTemplate'])->name('data-mentah.template');
        Route::post('/ujian/{ujian}/data-mentah/import', [DataMentahController::class, 'import'])->name('data-mentah.import');
        Route::post('/ujian/{ujian}/data-mentah/preview', [DataMentahController::class, 'preview'])->name('data-mentah.preview');
        Route::post('/ujian/{ujian}/data-mentah/confirm-import', [DataMentahController::class, 'confirmImport'])->name('data-mentah.confirm-import');
    });

    // --------------------------------------------------
    // Olah Data T2 (Admin, Guru)
    // --------------------------------------------------
    Route::middleware(['role:Admin,Guru', 'ujian.access'])->group(function () {
        Route::get('/ujian/{ujian}/olah-data', [OlahDataController::class, 'index'])->name('olah-data.index');
        Route::post('/ujian/{ujian}/olah-data/proses', [OlahDataController::class, 'proses'])->name('olah-data.proses');
    });

    // --------------------------------------------------
    // Analisis Data T3 (Admin, Guru)
    // --------------------------------------------------
    Route::middleware(['role:Admin,Guru', 'ujian.access'])->group(function () {
        Route::get('/ujian/{ujian}/analisis-data', [AnalisisDataController::class, 'index'])->name('analisis-data.index');
        Route::post('/ujian/{ujian}/analisis-data/proses', [AnalisisDataController::class, 'proses'])->name('analisis-data.proses');
    });

    // --------------------------------------------------
    // Daftar Nilai T4 (semua role)
    // --------------------------------------------------
    Route::middleware(['ujian.access'])->group(function () {
        Route::get('/ujian/{ujian}/daftar-nilai', [DaftarNilaiController::class, 'index'])->name('daftar-nilai.index');
        Route::post('/ujian/{ujian}/daftar-nilai/manual', [DaftarNilaiController::class, 'updateManual'])->name('daftar-nilai.manual');
    });

    // --------------------------------------------------
    // Rekap Nilai T5 (semua role)
    // --------------------------------------------------
    Route::middleware(['ujian.access'])->group(function () {
        Route::get('/ujian/{ujian}/rekap-nilai', [RekapNilaiController::class, 'index'])->name('rekap-nilai.index');
    });

    // --------------------------------------------------
    // Export (Admin, Guru, Wakil Kurikulum)
    // --------------------------------------------------
    Route::middleware(['role:Admin,Guru,Wakil Kurikulum', 'ujian.access'])->prefix('ujian/{ujian}/export')->name('export.')->group(function () {
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
