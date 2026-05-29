<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianKelas;
use App\Models\User;
use App\Services\GroupingService;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CoreAuditFixTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manual_group_size_cannot_exceed_half_of_present_students(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Jumlah kelompok terlalu besar. Maksimal untuk jumlah siswa hadir 21 adalah 10.');

        app(GroupingService::class)->hitungJumlahKelompok(21, 'manual', 11);
    }

    public function test_automatic_group_size_uses_floor_half_of_present_students(): void
    {
        $this->assertSame(10, app(GroupingService::class)->hitungJumlahKelompok(21, 'persen_50'));
        $this->assertSame(17, app(GroupingService::class)->hitungJumlahKelompok(35, 'persen_50'));
    }

    public function test_inactive_user_cannot_login_with_required_message(): void
    {
        $role = Role::firstOrCreate(['nama_role' => 'Admin']);
        $user = User::create([
            'role_id' => $role->id,
            'name' => 'User Nonaktif',
            'username' => 'nonaktif_' . uniqid(),
            'password' => 'password123',
            'status' => 'nonaktif',
        ]);

        $this->from(route('login'))
            ->post(route('login.post'), [
                'username' => $user->username,
                'password' => 'password123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Akun Anda nonaktif. Silakan hubungi administrator.');

        $this->assertGuest();
    }

    public function test_import_validation_reports_row_student_and_question_context(): void
    {
        $data = $this->makeImportContext();

        $result = app(ImportService::class)->preview([
            ['999999', '', '', 'L', 'hadir', 'A'],
            [$data['siswa']->nis, $data['siswa']->nisn, $data['siswa']->nama_siswa, 'L', 'hadir', 'Z'],
            [$data['siswa']->nis, $data['siswa']->nisn, $data['siswa']->nama_siswa, 'L', 'hadir', '1'],
        ], $data['ujian'], 'abcd');

        $this->assertSame('Baris 2: NIS 999999 tidak ditemukan di database.', $result['errors'][0]['errors'][0]);
        $this->assertSame(
            'Baris 3, siswa ' . $data['siswa']->nama_siswa . ', soal 1: jawaban harus A/B/C/D/E atau kosong.',
            $result['errors'][1]['errors'][0]
        );
        $this->assertSame(
            'Baris 4: NIS ' . $data['siswa']->nis . ' duplikat dengan baris 3.',
            $result['errors'][2]['errors'][0]
        );
    }

    public function test_profile_can_be_updated_and_password_requires_current_password(): void
    {
        $role = Role::firstOrCreate(['nama_role' => 'Guru']);
        $user = User::create([
            'role_id' => $role->id,
            'name' => 'Guru Profil',
            'username' => 'guru_profile_' . uniqid(),
            'email' => 'guru_profile_' . uniqid() . '@example.test',
            'password' => 'password123',
            'status' => 'aktif',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Guru Profil Baru',
                'email' => 'profil_baru_' . uniqid() . '@example.test',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Guru Profil Baru', $user->name);

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'salah',
                'password' => 'password456',
                'password_confirmation' => 'password456',
            ])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'password123',
                'password' => 'password456',
                'password_confirmation' => 'password456',
            ])
            ->assertRedirect(route('profile.password.edit'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('password456', $user->fresh()->password));
    }

    private function makeImportContext(): array
    {
        $guru = Guru::create([
            'nama_guru' => 'Guru Validasi',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2098/2099',
            'semester' => 'Ganjil',
            'status' => 'aktif',
        ]);

        $kelas = Kelas::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_kelas' => 'IX Validasi',
            'tingkat' => 'IX',
        ]);

        $mapel = Mapel::create([
            'kode_mapel' => 'VAL' . substr(uniqid(), -5),
            'nama_mapel' => 'Mapel Validasi',
        ]);

        $siswa = Siswa::create([
            'nis' => 'VAL' . substr(uniqid(), -8),
            'nisn' => 'NISN' . substr(uniqid(), -8),
            'nama_siswa' => 'Siswa Validasi',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        SiswaKelas::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'status' => 'aktif',
        ]);

        $ujian = Ujian::create([
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_ujian' => 'Ujian Validasi ' . uniqid(),
            'jenis_ujian' => 'UH',
            'tanggal_ujian' => now()->toDateString(),
            'jumlah_soal' => 1,
            'kkm' => 75,
            'metode_kelompok' => 'persen_50',
            'status' => 'kunci_lengkap',
        ]);

        UjianKelas::create(['ujian_id' => $ujian->id, 'kelas_id' => $kelas->id]);
        Soal::create(['ujian_id' => $ujian->id, 'nomor_soal' => 1, 'kunci_jawaban' => 'A', 'bobot' => 1]);

        return compact('guru', 'tahunAjaran', 'kelas', 'mapel', 'siswa', 'ujian');
    }
}
