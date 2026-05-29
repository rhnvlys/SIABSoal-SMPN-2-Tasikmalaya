<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['Admin', 'Guru', 'Kepala Sekolah'] as $roleName) {
            DB::table('roles')->updateOrInsert(
                ['nama_role' => $roleName],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }

        $guruRoleId = DB::table('roles')->where('nama_role', 'Guru')->value('id');
        $kepalaRoleId = DB::table('roles')->where('nama_role', 'Kepala Sekolah')->value('id');
        $operatorRoleIds = DB::table('roles')->where('nama_role', 'Operator')->pluck('id');
        $wakilRoleIds = DB::table('roles')->where('nama_role', 'Wakil Kurikulum')->pluck('id');

        if ($guruRoleId && $operatorRoleIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('role_id', $operatorRoleIds)
                ->update(['role_id' => $guruRoleId, 'updated_at' => now()]);
        }

        if ($kepalaRoleId && $wakilRoleIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('role_id', $wakilRoleIds)
                ->update(['role_id' => $kepalaRoleId, 'updated_at' => now()]);
        }

        DB::table('roles')
            ->whereIn('nama_role', ['Operator', 'Wakil Kurikulum'])
            ->delete();
    }

    public function down(): void
    {
        foreach (['Operator', 'Wakil Kurikulum'] as $roleName) {
            DB::table('roles')->updateOrInsert(
                ['nama_role' => $roleName],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
};
