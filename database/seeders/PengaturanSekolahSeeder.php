<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PengaturanSekolah;

class PengaturanSekolahSeeder extends Seeder
{
    public function run(): void
    {
        PengaturanSekolah::firstOrCreate(
            ['id' => 1],
            [
                'nama_sekolah'        => 'SMP NEGERI 2 TASIKMALAYA',
                'nama_sistem'         => 'SIABSoal SMPN 2 Tasikmalaya',
                'nama_lengkap_sistem' => 'Sistem Informasi Analisis Butir Soal Berbasis Web SMPN 2 Tasikmalaya',
                'alamat'              => 'Jl. Letkol RE Djaelani No. 1, Tasikmalaya, Jawa Barat',
                'kepala_sekolah'      => 'Drs. H. Contoh Kepala Sekolah, M.Pd.',
                'nip_kepala_sekolah'  => '196501011990031001',
            ]
        );
    }
}
