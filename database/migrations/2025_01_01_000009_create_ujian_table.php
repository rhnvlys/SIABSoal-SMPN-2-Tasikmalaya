<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('guru')->onDelete('restrict');
            $table->foreignId('mapel_id')->constrained('mapel')->onDelete('restrict');
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->onDelete('restrict');
            $table->string('nama_ujian');
            $table->enum('jenis_ujian', ['UH', 'STS', 'SAS', 'ASAJ', 'PAS', 'PAT', 'UTS', 'UAS', 'Lainnya']);
            $table->date('tanggal_ujian');
            $table->integer('jumlah_soal');
            $table->decimal('kkm', 5, 2)->default(80.00);
            $table->enum('metode_kelompok', ['persen_50', 'manual'])->default('persen_50');
            $table->integer('jumlah_kelompok_manual')->nullable();
            $table->enum('status', ['draft', 'kunci_lengkap', 'data_mentah', 'olah_data', 'dianalisis', 'selesai'])->default('draft');
            $table->timestamps();
        });

        // Ujian bisa digunakan untuk satu atau lebih kelas
        Schema::create('ujian_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujian')->onDelete('cascade');
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['ujian_id', 'kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujian_kelas');
        Schema::dropIfExists('ujian');
    }
};
