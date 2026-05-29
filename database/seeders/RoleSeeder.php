<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['Admin', 'Guru', 'Kepala Sekolah'];

        foreach ($roles as $role) {
            Role::updateOrCreate(['nama_role' => $role]);
        }
    }
}
