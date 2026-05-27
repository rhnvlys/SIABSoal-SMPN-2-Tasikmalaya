<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Role;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianKelas;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UjianAccessAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_access_any_ujian_detail_route(): void
    {
        $data = $this->makeUjianAccessData();

        $this->actingAs($data['admin'])
            ->get(route('kunci-jawaban.index', $data['ujianGuruB']))
            ->assertOk();
    }

    public function test_guru_can_access_their_own_ujian_detail_route(): void
    {
        $data = $this->makeUjianAccessData();

        $this->actingAs($data['guruUserA'])
            ->get(route('kunci-jawaban.index', $data['ujianGuruA']))
            ->assertOk();
    }

    public function test_guru_cannot_access_another_teachers_ujian_detail_routes(): void
    {
        $data = $this->makeUjianAccessData();

        $routes = [
            route('kunci-jawaban.index', $data['ujianGuruB']),
            route('data-mentah.index', $data['ujianGuruB']),
            route('olah-data.index', $data['ujianGuruB']),
            route('analisis-data.index', $data['ujianGuruB']),
            route('daftar-nilai.index', $data['ujianGuruB']),
            route('rekap-nilai.index', $data['ujianGuruB']),
            route('export.data-mentah.excel', $data['ujianGuruB']),
            route('ujian.show', $data['ujianGuruB']),
        ];

        foreach ($routes as $url) {
            $this->actingAs($data['guruUserA'])
                ->get($url)
                ->assertForbidden();
        }
    }

    private function makeUjianAccessData(): array
    {
        $adminRole = Role::firstOrCreate(['nama_role' => 'Admin']);
        $guruRole = Role::firstOrCreate(['nama_role' => 'Guru']);

        $admin = User::create([
            'role_id' => $adminRole->id,
            'name' => 'Admin Test',
            'username' => 'admin_access_' . uniqid(),
            'password' => 'password',
            'status' => 'aktif',
        ]);

        $guruUserA = User::create([
            'role_id' => $guruRole->id,
            'name' => 'Guru A',
            'username' => 'guru_a_' . uniqid(),
            'password' => 'password',
            'status' => 'aktif',
        ]);

        $guruUserB = User::create([
            'role_id' => $guruRole->id,
            'name' => 'Guru B',
            'username' => 'guru_b_' . uniqid(),
            'password' => 'password',
            'status' => 'aktif',
        ]);

        $guruA = Guru::create([
            'user_id' => $guruUserA->id,
            'nama_guru' => 'Guru A',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $guruB = Guru::create([
            'user_id' => $guruUserB->id,
            'nama_guru' => 'Guru B',
            'jenis_kelamin' => 'P',
            'status' => 'aktif',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2099/2100',
            'semester' => 'Ganjil',
            'status' => 'aktif',
        ]);

        $kelas = Kelas::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_kelas' => 'IX Test',
            'tingkat' => 'IX',
        ]);

        $mapel = Mapel::create([
            'kode_mapel' => 'TST' . substr(uniqid(), -5),
            'nama_mapel' => 'Mapel Test',
        ]);

        $ujianGuruA = $this->createUjianForGuru($guruA, $mapel, $tahunAjaran, $kelas, 'Ujian Guru A');
        $ujianGuruB = $this->createUjianForGuru($guruB, $mapel, $tahunAjaran, $kelas, 'Ujian Guru B');

        return compact('admin', 'guruUserA', 'guruUserB', 'ujianGuruA', 'ujianGuruB');
    }

    private function createUjianForGuru(Guru $guru, Mapel $mapel, TahunAjaran $tahunAjaran, Kelas $kelas, string $namaUjian): Ujian
    {
        $ujian = Ujian::create([
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_ujian' => $namaUjian . ' ' . uniqid(),
            'jenis_ujian' => 'UH',
            'tanggal_ujian' => now()->toDateString(),
            'jumlah_soal' => 1,
            'kkm' => 75,
            'metode_kelompok' => 'persen_50',
            'status' => 'draft',
        ]);

        UjianKelas::create([
            'ujian_id' => $ujian->id,
            'kelas_id' => $kelas->id,
        ]);

        Soal::create([
            'ujian_id' => $ujian->id,
            'nomor_soal' => 1,
            'kunci_jawaban' => 'A',
            'bobot' => 1,
        ]);

        return $ujian;
    }
}
