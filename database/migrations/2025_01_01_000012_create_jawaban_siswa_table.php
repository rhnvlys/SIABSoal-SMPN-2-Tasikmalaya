<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jawaban_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_ujian_id')->constrained('peserta_ujian')->onDelete('cascade');
            $table->foreignId('soal_id')->constrained('soal')->onDelete('cascade');
            $table->enum('jawaban', ['A', 'B', 'C', 'D', 'E'])->nullable();
            $table->tinyInteger('skor_biner')->default(0);
            $table->boolean('is_benar')->default(false);
            $table->timestamps();

            // Kombinasi peserta_ujian_id dan soal_id harus unique
            $table->unique(['peserta_ujian_id', 'soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jawaban_siswa');
    }
};
