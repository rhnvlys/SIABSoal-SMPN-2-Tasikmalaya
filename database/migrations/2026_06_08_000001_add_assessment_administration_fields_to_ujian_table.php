<?php

use App\Models\Ujian;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujian', function (Blueprint $table) {
            if (!Schema::hasColumn('ujian', 'jenis_penilaian')) {
                $table->string('jenis_penilaian', 100)->nullable();
            }

            if (!Schema::hasColumn('ujian', 'tujuan_pembelajaran')) {
                $table->text('tujuan_pembelajaran')->nullable();
            }

            if (!Schema::hasColumn('ujian', 'lingkup_materi')) {
                $table->text('lingkup_materi')->nullable();
            }

            if (!Schema::hasColumn('ujian', 'kktp')) {
                $table->decimal('kktp', 5, 2)->nullable();
            }
        });

        if (Schema::hasColumn('ujian', 'jenis_penilaian')) {
            foreach (Ujian::JENIS_UJIAN_LABELS as $kode => $label) {
                DB::table('ujian')
                    ->where('jenis_ujian', $kode)
                    ->whereNull('jenis_penilaian')
                    ->update(['jenis_penilaian' => $label]);
            }
        }

        if (Schema::hasColumn('ujian', 'kktp') && Schema::hasColumn('ujian', 'kkm')) {
            DB::table('ujian')
                ->whereNull('kktp')
                ->update(['kktp' => DB::raw('kkm')]);
        }
    }

    public function down(): void
    {
        foreach (['kktp', 'lingkup_materi', 'tujuan_pembelajaran', 'jenis_penilaian'] as $column) {
            if (Schema::hasColumn('ujian', $column)) {
                Schema::table('ujian', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
