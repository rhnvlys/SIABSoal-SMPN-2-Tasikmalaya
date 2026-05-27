<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujian')->onDelete('cascade');
            $table->integer('nomor_soal');
            $table->enum('kunci_jawaban', ['A', 'B', 'C', 'D', 'E'])->nullable();
            $table->decimal('bobot', 5, 2)->default(1.00);
            $table->timestamps();

            // Kombinasi ujian_id dan nomor_soal harus unique
            $table->unique(['ujian_id', 'nomor_soal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soal');
    }
};
