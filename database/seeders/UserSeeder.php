<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Guru;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::updateOrCreate(['nama_role' => 'Admin']);
        $guruRole  = Role::updateOrCreate(['nama_role' => 'Guru']);
        $kepalaRole = Role::updateOrCreate(['nama_role' => 'Kepala Sekolah']);

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'role_id'  => $adminRole->id,
                'name'     => 'Administrator',
                'email'    => 'admin@siabsoal.local',
                'password' => Hash::make('admin123'),
                'status'   => 'aktif',
            ]
        );

        $guruUser = User::updateOrCreate(
            ['username' => 'guru'],
            [
                'role_id'  => $guruRole->id,
                'name'     => 'Guru Contoh',
                'email'    => 'guru@siabsoal.local',
                'password' => Hash::make('guru123'),
                'status'   => 'aktif',
            ]
        );

        Guru::updateOrCreate(
            ['user_id' => $guruUser->id],
            [
                'nama_guru' => 'Guru Contoh',
                'status' => 'aktif',
            ]
        );

        User::updateOrCreate(
            ['username' => 'kepsek'],
            [
                'role_id'  => $kepalaRole->id,
                'name'     => 'Kepala Sekolah',
                'email'    => 'kepsek@siabsoal.local',
                'password' => Hash::make('kepsek123'),
                'status'   => 'aktif',
            ]
        );
    }
}
