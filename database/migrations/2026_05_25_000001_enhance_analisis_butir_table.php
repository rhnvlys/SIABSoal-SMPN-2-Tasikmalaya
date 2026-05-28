<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE analisis_butir MODIFY kategori_dp ENUM('Sangat Baik','Baik','Cukup','Jelek','Bermasalah','Revisi','Buang') NOT NULL DEFAULT 'Jelek'");
        DB::statement("ALTER TABLE analisis_butir MODIFY keputusan ENUM('Dipakai','Dipakai dengan catatan','Revisi','Buang') NOT NULL DEFAULT 'Buang'");

        if (! Schema::hasColumn('analisis_butir', 'jumlah_benar')) {
            Schema::table('analisis_butir', function (Blueprint $table) {
                $table->integer('jumlah_benar')->default(0)->after('n_analisis');
            });
        }

        if (! Schema::hasColumn('analisis_butir', 'jumlah_peserta_valid')) {
            Schema::table('analisis_butir', function (Blueprint $table) {
                $table->integer('jumlah_peserta_valid')->default(0)->after('jumlah_benar');
            });
        }

        if (! Schema::hasColumn('analisis_butir', 'distractor_status')) {
            Schema::table('analisis_butir', function (Blueprint $table) {
                $table->json('distractor_status')->nullable()->after('kategori_tk');
            });
        }

        if (! Schema::hasColumn('analisis_butir', 'validitas')) {
            Schema::table('analisis_butir', function (Blueprint $table) {
                $table->decimal('validitas', 8, 3)->nullable()->after('distractor_status');
            });
        }

        if (! Schema::hasColumn('analisis_butir', 'kategori_validitas')) {
            Schema::table('analisis_butir', function (Blueprint $table) {
                $table->string('kategori_validitas', 30)->nullable()->after('validitas');
            });
        }

        if (! Schema::hasColumn('analisis_butir', 'reliabilitas')) {
            Schema::table('analisis_butir', function (Blueprint $table) {
                $table->decimal('reliabilitas', 8, 3)->nullable()->after('kategori_validitas');
            });
        }

        if (! Schema::hasColumn('analisis_butir', 'kategori_reliabilitas')) {
            Schema::table('analisis_butir', function (Blueprint $table) {
                $table->string('kategori_reliabilitas', 30)->nullable()->after('reliabilitas');
            });
        }

        if (! Schema::hasColumn('analisis_butir', 'metode_reliabilitas')) {
            Schema::table('analisis_butir', function (Blueprint $table) {
                $table->string('metode_reliabilitas', 20)->nullable()->after('kategori_reliabilitas');
            });
        }

        if (! Schema::hasColumn('analisis_butir', 'alasan_rekomendasi')) {
            Schema::table('analisis_butir', function (Blueprint $table) {
                $table->text('alasan_rekomendasi')->nullable()->after('keputusan');
            });
        }
    }

    public function down(): void
    {
        DB::table('analisis_butir')
            ->whereIn('kategori_dp', ['Sangat Baik', 'Baik'])
            ->update(['kategori_dp' => 'Baik']);

        DB::table('analisis_butir')
            ->whereIn('kategori_dp', ['Cukup', 'Jelek'])
            ->update(['kategori_dp' => 'Revisi']);

        DB::table('analisis_butir')
            ->where('kategori_dp', 'Bermasalah')
            ->update(['kategori_dp' => 'Buang']);

        $columns = array_filter([
            'jumlah_benar',
            'jumlah_peserta_valid',
            'distractor_status',
            'validitas',
            'kategori_validitas',
            'reliabilitas',
            'kategori_reliabilitas',
            'metode_reliabilitas',
            'alasan_rekomendasi',
        ], fn (string $column) => Schema::hasColumn('analisis_butir', $column));

        if ($columns !== []) {
            Schema::table('analisis_butir', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }

        DB::statement("ALTER TABLE analisis_butir MODIFY kategori_dp ENUM('Baik','Revisi','Buang') NOT NULL DEFAULT 'Buang'");
        DB::statement("ALTER TABLE analisis_butir MODIFY keputusan ENUM('Dipakai','Dipakai dengan catatan','Revisi','Buang') NOT NULL DEFAULT 'Buang'");
    }
};
