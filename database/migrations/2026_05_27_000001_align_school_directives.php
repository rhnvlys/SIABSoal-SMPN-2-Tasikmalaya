<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Align database with school directives.
 *
 * Changes:
 * 1. Add 'tidak_hadir' to peserta_ujian.keterangan enum
 * 2. Revert kategori_dp enum to simplified 3 values: Baik, Revisi, Buang
 *
 * NO destructive changes — old nullable columns (validitas, reliabilitas, etc.)
 * are kept but will no longer be populated by the system.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 1. Add 'tidak_hadir' to keterangan enum
        DB::statement("ALTER TABLE peserta_ujian MODIFY keterangan ENUM('tercapai','perlu_peningkatan','tidak_hadir') NULL");

        // 2. Simplify kategori_dp back to 3 values (Baik, Revisi, Buang)
        // First migrate existing data to fit 3-value enum
        DB::table('analisis_butir')
            ->whereIn('kategori_dp', ['Sangat Baik'])
            ->update(['kategori_dp' => 'Baik']);

        DB::table('analisis_butir')
            ->whereIn('kategori_dp', ['Cukup'])
            ->update(['kategori_dp' => 'Revisi']);

        DB::table('analisis_butir')
            ->whereIn('kategori_dp', ['Jelek', 'Bermasalah'])
            ->update(['kategori_dp' => 'Buang']);

        DB::statement("ALTER TABLE analisis_butir MODIFY kategori_dp ENUM('Baik','Revisi','Buang') NOT NULL DEFAULT 'Buang'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE peserta_ujian MODIFY keterangan ENUM('tercapai','perlu_peningkatan') NULL");
        DB::statement("ALTER TABLE analisis_butir MODIFY kategori_dp ENUM('Sangat Baik','Baik','Cukup','Jelek','Bermasalah','Revisi','Buang') NOT NULL DEFAULT 'Jelek'");
    }
};
