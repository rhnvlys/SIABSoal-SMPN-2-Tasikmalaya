<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peserta_ujian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujian')->onDelete('cascade');
            $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('restrict');
            $table->enum('status_kehadiran', ['hadir', 'tidak_hadir'])->default('hadir');
            $table->integer('jumlah_benar')->default(0);
            $table->integer('jumlah_salah')->default(0);
            $table->decimal('total_skor', 8, 2)->default(0);
            $table->decimal('nilai', 8, 2)->default(0);
            $table->integer('ranking')->nullable();
            $table->enum('kelompok', ['atas', 'bawah', 'tengah'])->nullable();
            $table->enum('keterangan', ['tercapai', 'perlu_peningkatan'])->nullable();
            $table->timestamps();

            // Kombinasi ujian_id dan siswa_id harus unique
            $table->unique(['ujian_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peserta_ujian');
    }
};
