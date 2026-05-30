<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('log_aktivitas', 'nama_user')) {
            Schema::table('log_aktivitas', function (Blueprint $table) {
                $table->string('nama_user')->nullable();
            });
        }

        if (!Schema::hasColumn('log_aktivitas', 'role')) {
            Schema::table('log_aktivitas', function (Blueprint $table) {
                $table->string('role', 100)->nullable();
            });
        }

        if (!Schema::hasColumn('log_aktivitas', 'aksi')) {
            Schema::table('log_aktivitas', function (Blueprint $table) {
                $table->string('aksi')->nullable();
            });
        }

        if (!Schema::hasColumn('log_aktivitas', 'deskripsi')) {
            Schema::table('log_aktivitas', function (Blueprint $table) {
                $table->text('deskripsi')->nullable();
            });
        }

        if (!Schema::hasColumn('log_aktivitas', 'related_type')) {
            Schema::table('log_aktivitas', function (Blueprint $table) {
                $table->string('related_type')->nullable();
            });
        }

        if (!Schema::hasColumn('log_aktivitas', 'related_id')) {
            Schema::table('log_aktivitas', function (Blueprint $table) {
                $table->unsignedBigInteger('related_id')->nullable();
            });
        }

        if (Schema::hasColumn('log_aktivitas', 'aktivitas') && Schema::hasColumn('log_aktivitas', 'aksi')) {
            DB::table('log_aktivitas')
                ->whereNull('aksi')
                ->update(['aksi' => DB::raw('aktivitas')]);
        }

        if (Schema::hasColumn('log_aktivitas', 'aktivitas') && Schema::hasColumn('log_aktivitas', 'deskripsi')) {
            DB::table('log_aktivitas')
                ->whereNull('deskripsi')
                ->update(['deskripsi' => DB::raw('aktivitas')]);
        }
    }

    public function down(): void
    {
        foreach (['related_id', 'related_type', 'deskripsi', 'aksi', 'role', 'nama_user'] as $column) {
            if (Schema::hasColumn('log_aktivitas', $column)) {
                Schema::table('log_aktivitas', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
