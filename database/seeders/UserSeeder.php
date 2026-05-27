<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('nama_role', 'Admin')->first();
        $guruRole  = Role::where('nama_role', 'Guru')->first();

        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'role_id'  => $adminRole->id,
                'name'     => 'Administrator',
                'email'    => 'admin@siabsoal.local',
                'password' => bcrypt('admin123'),
                'status'   => 'aktif',
            ]
        );

        User::firstOrCreate(
            ['username' => 'guru'],
            [
                'role_id'  => $guruRole->id,
                'name'     => 'Guru Contoh',
                'email'    => 'guru@siabsoal.local',
                'password' => bcrypt('guru123'),
                'status'   => 'aktif',
            ]
        );
    }
}
