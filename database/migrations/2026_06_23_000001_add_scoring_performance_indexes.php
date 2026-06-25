<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peserta_ujian', function (Blueprint $table): void {
            // Mendukung filter T2/T3 dan urutan nilai tanpa menggandakan index FK ujian_id.
            $table->index(
                ['ujian_id', 'status_kehadiran', 'kelompok'],
                'idx_peserta_ujian_status_kelompok'
            );
            $table->index(['ujian_id', 'nilai'], 'idx_peserta_ujian_nilai');
        });

        Schema::table('jawaban_siswa', function (Blueprint $table): void {
            // Unique peserta_ujian_id + soal_id sudah ada; index ini mempercepat agregasi jawaban benar.
            $table->index(
                ['peserta_ujian_id', 'is_benar', 'soal_id'],
                'idx_jawaban_peserta_benar_soal'
            );
        });
    }

    public function down(): void
    {
        Schema::table('jawaban_siswa', function (Blueprint $table): void {
            $table->dropIndex('idx_jawaban_peserta_benar_soal');
        });

        Schema::table('peserta_ujian', function (Blueprint $table): void {
            $table->dropIndex('idx_peserta_ujian_status_kelompok');
            $table->dropIndex('idx_peserta_ujian_nilai');
        });
    }
};
