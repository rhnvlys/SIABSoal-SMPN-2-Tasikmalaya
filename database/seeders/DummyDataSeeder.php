<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Kelas;
use App\Models\SiswaKelas;
use App\Models\Mapel;
use App\Models\Ujian;
use App\Models\UjianKelas;
use App\Models\Soal;
use App\Models\User;
use App\Models\Role;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $guruRole = Role::where('nama_role', 'Guru')->first();

        // ============================
        // Guru (2 guru + link ke user)
        // ============================
        $userGuru = User::where('username', 'guru')->first();

        $guru1 = Guru::firstOrCreate(
            ['nip' => '198501152010012001'],
            [
                'user_id'       => $userGuru?->id,
                'nama_guru'     => 'Siti Nurhaliza, S.Pd.',
                'jenis_kelamin' => 'P',
                'no_hp'         => '081234567890',
                'status'        => 'aktif',
            ]
        );

        // Guru kedua tanpa user account
        $guru2 = Guru::firstOrCreate(
            ['nip' => '197803212005011002'],
            [
                'user_id'       => null,
                'nama_guru'     => 'Ahmad Fauzi, S.Pd.',
                'jenis_kelamin' => 'L',
                'no_hp'         => '081298765432',
                'status'        => 'aktif',
            ]
        );

        // Buat user account untuk guru kedua
        $userGuru2 = User::firstOrCreate(
            ['username' => 'afauzi'],
            [
                'role_id'  => $guruRole->id,
                'name'     => 'Ahmad Fauzi',
                'password' => bcrypt('guru123'),
                'status'   => 'aktif',
            ]
        );
        $guru2->update(['user_id' => $userGuru2->id]);

        // ============================
        // Tahun Ajaran
        // ============================
        $ta = TahunAjaran::firstOrCreate(
            ['tahun_ajaran' => '2025/2026', 'semester' => 'Ganjil'],
            ['status' => 'aktif']
        );

        // ============================
        // Kelas (2 kelas)
        // ============================
        $kelas1 = Kelas::firstOrCreate(
            ['nama_kelas' => 'IX A', 'tahun_ajaran_id' => $ta->id],
            [
                'tingkat'       => 'IX',
                'wali_kelas_id' => $guru1->id,
            ]
        );

        $kelas2 = Kelas::firstOrCreate(
            ['nama_kelas' => 'IX B', 'tahun_ajaran_id' => $ta->id],
            [
                'tingkat'       => 'IX',
                'wali_kelas_id' => $guru2->id,
            ]
        );

        // ============================
        // Mata Pelajaran (3 mapel)
        // ============================
        $mtk = Mapel::firstOrCreate(
            ['kode_mapel' => 'MTK'],
            ['nama_mapel' => 'Matematika']
        );

        Mapel::firstOrCreate(
            ['kode_mapel' => 'IPA'],
            ['nama_mapel' => 'Ilmu Pengetahuan Alam']
        );

        Mapel::firstOrCreate(
            ['kode_mapel' => 'BIN'],
            ['nama_mapel' => 'Bahasa Indonesia']
        );

        // ============================
        // Siswa (20 siswa)
        // ============================
        $namaSiswa = [
            ['nis' => '2501', 'nisn' => '0051234001', 'nama' => 'Adinda Putri Rahayu',   'jk' => 'P'],
            ['nis' => '2502', 'nisn' => '0051234002', 'nama' => 'Bagus Prasetyo',         'jk' => 'L'],
            ['nis' => '2503', 'nisn' => '0051234003', 'nama' => 'Citra Dewi Lestari',     'jk' => 'P'],
            ['nis' => '2504', 'nisn' => '0051234004', 'nama' => 'Daffa Rahadian',         'jk' => 'L'],
            ['nis' => '2505', 'nisn' => '0051234005', 'nama' => 'Eka Safitri',            'jk' => 'P'],
            ['nis' => '2506', 'nisn' => '0051234006', 'nama' => 'Fajar Maulana',          'jk' => 'L'],
            ['nis' => '2507', 'nisn' => '0051234007', 'nama' => 'Gita Pramesti',          'jk' => 'P'],
            ['nis' => '2508', 'nisn' => '0051234008', 'nama' => 'Hendra Wijaya',          'jk' => 'L'],
            ['nis' => '2509', 'nisn' => '0051234009', 'nama' => 'Indah Permatasari',      'jk' => 'P'],
            ['nis' => '2510', 'nisn' => '0051234010', 'nama' => 'Joko Susanto',           'jk' => 'L'],
            ['nis' => '2511', 'nisn' => '0051234011', 'nama' => 'Kartika Sari',           'jk' => 'P'],
            ['nis' => '2512', 'nisn' => '0051234012', 'nama' => 'Lukman Hakim',           'jk' => 'L'],
            ['nis' => '2513', 'nisn' => '0051234013', 'nama' => 'Mega Wulandari',         'jk' => 'P'],
            ['nis' => '2514', 'nisn' => '0051234014', 'nama' => 'Naufal Rizky',           'jk' => 'L'],
            ['nis' => '2515', 'nisn' => '0051234015', 'nama' => 'Oktavia Rahmawati',      'jk' => 'P'],
            ['nis' => '2516', 'nisn' => '0051234016', 'nama' => 'Putra Aditya',           'jk' => 'L'],
            ['nis' => '2517', 'nisn' => '0051234017', 'nama' => 'Qonita Zahra',           'jk' => 'P'],
            ['nis' => '2518', 'nisn' => '0051234018', 'nama' => 'Rendi Saputra',          'jk' => 'L'],
            ['nis' => '2519', 'nisn' => '0051234019', 'nama' => 'Salsabila Azzahra',      'jk' => 'P'],
            ['nis' => '2520', 'nisn' => '0051234020', 'nama' => 'Taufik Hidayat',         'jk' => 'L'],
        ];

        $siswaIds = [];
        foreach ($namaSiswa as $s) {
            $siswa = Siswa::firstOrCreate(
                ['nis' => $s['nis']],
                [
                    'nisn'           => $s['nisn'],
                    'nama_siswa'     => $s['nama'],
                    'jenis_kelamin'  => $s['jk'],
                    'status'         => 'aktif',
                ]
            );
            $siswaIds[] = $siswa->id;
        }

        // ============================
        // Siswa-Kelas assignment (10 per kelas)
        // ============================
        foreach ($siswaIds as $i => $siswaId) {
            $kelasId = $i < 10 ? $kelas1->id : $kelas2->id;
            SiswaKelas::firstOrCreate(
                ['siswa_id' => $siswaId, 'kelas_id' => $kelasId, 'tahun_ajaran_id' => $ta->id],
                ['status' => 'aktif']
            );
        }

        // ============================
        // Ujian contoh (Matematika - IX A - 20 soal)
        // ============================
        $ujian = Ujian::firstOrCreate(
            ['nama_ujian' => 'UH 1 Matematika Kelas IX A'],
            [
                'guru_id'          => $guru1->id,
                'mapel_id'         => $mtk->id,
                'tahun_ajaran_id'  => $ta->id,
                'jenis_ujian'      => 'UH',
                'tanggal_ujian'    => '2025-09-15',
                'jumlah_soal'      => 20,
                'kkm'              => 75.00,
                'metode_kelompok'  => 'persen_50',
                'status'           => 'draft',
            ]
        );

        // Assign ujian ke kelas IX A
        UjianKelas::firstOrCreate([
            'ujian_id' => $ujian->id,
            'kelas_id' => $kelas1->id,
        ]);

        // Auto-create soal 1-20 with kunci jawaban
        $kunciJawaban = ['A','C','B','D','A','B','C','A','D','B','A','C','D','B','A','C','B','D','A','C'];
        for ($i = 1; $i <= 20; $i++) {
            Soal::firstOrCreate(
                ['ujian_id' => $ujian->id, 'nomor_soal' => $i],
                ['kunci_jawaban' => $kunciJawaban[$i - 1], 'bobot' => 1.00]
            );
        }

        // Update status ujian karena kunci sudah lengkap
        $ujian->update(['status' => 'kunci_lengkap']);

        // ============================
        // Peserta Ujian & Jawaban Siswa (10 siswa kelas IX A)
        // Data jawaban untuk validasi T1-T5
        // ============================
        $jawabanSiswa = [
            // Siswa 1-10 (kelas IX A) — variasi jawaban agar ada kelompok atas & bawah
            ['A','C','B','D','A','B','C','A','D','B','A','C','D','B','A','C','B','D','A','C'], // 20/20 = 100
            ['A','C','B','D','A','B','C','A','D','B','A','C','D','B','A','C','B','D','A','A'], // 19/20 = 95
            ['A','C','B','D','A','B','C','A','D','B','A','C','D','B','A','C','B','A','A','C'], // 18/20 = 90
            ['A','C','B','D','A','B','C','A','D','A','A','C','D','B','A','C','A','D','B','C'], // 16/20 = 80
            ['A','C','B','D','A','B','C','A','A','B','A','C','A','B','A','C','A','D','A','C'], // 15/20 = 75 (= KKM)
            ['A','C','B','D','A','B','A','A','D','A','A','A','D','B','A','C','A','A','A','C'], // 13/20 = 65
            ['A','C','B','A','A','B','A','A','D','A','A','A','D','A','A','C','A','A','A','A'], // 11/20 = 55
            ['A','C','B','A','A','A','A','A','A','B','A','A','A','A','A','A','A','A','A','A'], // 7/20 = 35
            ['A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A'], // 5/20 = 25
            ['B','B','A','A','B','A','A','A','A','A','A','A','A','A','B','A','A','A','B','A'], // 3/20 = 15
        ];

        $soalList = Soal::where('ujian_id', $ujian->id)->orderBy('nomor_soal')->get();

        foreach (array_slice($siswaIds, 0, 10) as $idx => $siswaId) {
            $peserta = \App\Models\PesertaUjian::firstOrCreate(
                ['ujian_id' => $ujian->id, 'siswa_id' => $siswaId],
                [
                    'kelas_id'          => $kelas1->id,
                    'status_kehadiran'  => 'hadir',
                    'jumlah_benar'      => 0,
                    'jumlah_salah'      => 0,
                    'total_skor'        => 0,
                    'nilai'             => 0,
                ]
            );

            foreach ($soalList as $soalIdx => $soal) {
                $jwb = $jawabanSiswa[$idx][$soalIdx] ?? null;
                \App\Models\JawabanSiswa::firstOrCreate(
                    ['peserta_ujian_id' => $peserta->id, 'soal_id' => $soal->id],
                    [
                        'jawaban'    => $jwb,
                        'skor_biner' => 0,
                        'is_benar'   => false,
                    ]
                );
            }
        }
    }
}
