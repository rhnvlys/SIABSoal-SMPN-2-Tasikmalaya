<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analisis_butir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujian')->onDelete('cascade');
            $table->foreignId('soal_id')->constrained('soal')->onDelete('cascade');
            $table->integer('nomor_soal');
            $table->integer('ba')->default(0); // Benar kelompok Atas
            $table->integer('bb')->default(0); // Benar kelompok Bawah
            $table->integer('ja')->default(0); // Jumlah kelompok Atas
            $table->integer('jb')->default(0); // Jumlah kelompok Bawah
            $table->integer('n_analisis')->default(0); // JA + JB
            $table->decimal('dp', 8, 3)->default(0); // Daya Pembeda
            $table->enum('kategori_dp', ['Baik', 'Revisi', 'Buang'])->default('Buang');
            $table->decimal('tk', 8, 3)->default(0); // Tingkat Kesukaran
            $table->enum('kategori_tk', ['Mudah', 'Sedang', 'Sukar'])->default('Sedang');
            $table->enum('keputusan', ['Dipakai', 'Dipakai dengan catatan', 'Revisi', 'Buang'])->default('Buang');
            $table->timestamps();

            $table->unique(['ujian_id', 'soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis_butir');
    }
};
