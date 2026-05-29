<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianKelas;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleDashboardAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_redirects_to_role_dashboard_and_rejects_legacy_role(): void
    {
        $admin = $this->makeUser('Admin', 'admin_role_' . uniqid(), 'password123');
        $guru = $this->makeUser('Guru', 'guru_role_' . uniqid(), 'password123');
        Guru::create([
            'user_id' => $guru->id,
            'nama_guru' => 'Guru Role',
            'status' => 'aktif',
        ]);
        $kepala = $this->makeUser('Kepala Sekolah', 'kepsek_role_' . uniqid(), 'password123');
        $operator = $this->makeUser('Operator', 'operator_role_' . uniqid(), 'password123');

        $this->post(route('login.post'), ['username' => $admin->username, 'password' => 'password123'])
            ->assertRedirect('/dashboard/admin');
        auth()->logout();

        $this->post(route('login.post'), ['username' => $guru->username, 'password' => 'password123'])
            ->assertRedirect('/dashboard/guru');
        auth()->logout();

        $this->post(route('login.post'), ['username' => $kepala->username, 'password' => 'password123'])
            ->assertRedirect('/dashboard/kepala-sekolah');
        auth()->logout();

        $this->post(route('login.post'), ['username' => $operator->username, 'password' => 'password123'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Role pengguna tidak valid. Hubungi administrator.');

        $this->assertGuest();
    }

    public function test_user_role_dropdown_only_lists_final_roles(): void
    {
        $admin = $this->makeUser('Admin', 'admin_dropdown_' . uniqid(), 'password123');
        Role::firstOrCreate(['nama_role' => 'Operator']);
        Role::firstOrCreate(['nama_role' => 'Wakil Kurikulum']);

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk()
            ->assertSee('Admin')
            ->assertSee('Guru')
            ->assertSee('Kepala Sekolah')
            ->assertDontSee('Operator')
            ->assertDontSee('Wakil Kurikulum');
    }

    public function test_dashboard_routes_are_separated_and_guarded_by_role(): void
    {
        $admin = $this->makeUser('Admin', 'admin_dash_' . uniqid(), 'password123');
        $guru = $this->makeUser('Guru', 'guru_dash_' . uniqid(), 'password123');
        Guru::create([
            'user_id' => $guru->id,
            'nama_guru' => 'Guru Dashboard',
            'status' => 'aktif',
        ]);
        $kepala = $this->makeUser('Kepala Sekolah', 'kepsek_dash_' . uniqid(), 'password123');

        $this->actingAs($admin)->get('/dashboard/admin')->assertOk()->assertSee('Dashboard Admin');
        $this->actingAs($admin)->get('/dashboard/guru')->assertForbidden();

        $this->actingAs($guru)->get('/dashboard/guru')->assertOk()->assertSee('Dashboard Guru');
        $this->actingAs($guru)->get('/dashboard/admin')->assertForbidden();

        $this->actingAs($kepala)->get('/dashboard/kepala-sekolah')->assertOk()->assertSee('Dashboard Kepala Sekolah');
        $this->actingAs($kepala)->get('/dashboard/admin')->assertForbidden();
    }

    public function test_kepala_sekolah_can_view_reports_but_cannot_process_or_modify(): void
    {
        $data = $this->makeUjianContext();
        $kepala = $this->makeUser('Kepala Sekolah', 'kepsek_report_' . uniqid(), 'password123');

        $this->actingAs($kepala)->get(route('analisis-data.index', $data['ujian']))->assertOk();
        $this->actingAs($kepala)->get(route('daftar-nilai.index', $data['ujian']))->assertOk();
        $this->actingAs($kepala)->get(route('rekap-nilai.index', $data['ujian']))->assertOk();
        $this->actingAs($kepala)->get(route('export.analisis.excel', $data['ujian']))->assertOk();

        $this->actingAs($kepala)->post(route('data-mentah.proses', $data['ujian']))->assertForbidden();
        $this->actingAs($kepala)->post(route('olah-data.proses', $data['ujian']))->assertForbidden();
        $this->actingAs($kepala)->post(route('analisis-data.proses', $data['ujian']))->assertForbidden();
        $this->actingAs($kepala)->post(route('daftar-nilai.manual', $data['ujian']))->assertForbidden();
    }

    public function test_guru_only_sees_and_manages_wali_classes(): void
    {
        $data = $this->makeUjianContext();
        $guruUser = $data['guru']->user;
        $otherClass = Kelas::create([
            'tahun_ajaran_id' => $data['tahunAjaran']->id,
            'nama_kelas' => 'IX Lain',
            'tingkat' => 'IX',
        ]);
        $siswa = Siswa::create([
            'nis' => 'WALI' . substr(uniqid(), -8),
            'nisn' => 'NISN' . substr(uniqid(), -8),
            'nama_siswa' => 'Siswa Wali',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $this->actingAs($guruUser)
            ->get(route('kelas.index'))
            ->assertOk()
            ->assertSee($data['kelas']->nama_kelas)
            ->assertDontSee($otherClass->nama_kelas);

        $this->actingAs($guruUser)->get(route('kelas.show', $data['kelas']))->assertOk();
        $this->actingAs($guruUser)->get(route('kelas.show', $otherClass))->assertForbidden();

        $this->actingAs($guruUser)
            ->post(route('kelas.siswa.store', $data['kelas']), ['siswa_id' => $siswa->id])
            ->assertRedirect(route('kelas.show', $data['kelas']));

        $this->assertDatabaseHas('siswa_kelas', [
            'kelas_id' => $data['kelas']->id,
            'siswa_id' => $siswa->id,
        ]);
    }

    private function makeUser(string $roleName, string $username, string $password): User
    {
        $role = Role::firstOrCreate(['nama_role' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'name' => ucfirst(str_replace('_', ' ', $username)),
            'username' => $username,
            'password' => $password,
            'status' => 'aktif',
        ]);
    }

    private function makeUjianContext(): array
    {
        $guruUser = $this->makeUser('Guru', 'guru_context_' . uniqid(), 'password123');
        $guru = Guru::create([
            'user_id' => $guruUser->id,
            'nama_guru' => 'Guru Konteks',
            'status' => 'aktif',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2097/2098',
            'semester' => 'Ganjil',
            'status' => 'aktif',
        ]);

        $kelas = Kelas::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'wali_kelas_id' => $guru->id,
            'nama_kelas' => 'IX Wali',
            'tingkat' => 'IX',
        ]);

        $mapel = Mapel::create([
            'kode_mapel' => 'ROL' . substr(uniqid(), -5),
            'nama_mapel' => 'Mapel Role',
        ]);

        $ujian = Ujian::create([
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_ujian' => 'Ujian Role ' . uniqid(),
            'jenis_ujian' => 'UH',
            'tanggal_ujian' => now()->toDateString(),
            'jumlah_soal' => 1,
            'kkm' => 75,
            'metode_kelompok' => 'persen_50',
            'status' => 'dianalisis',
        ]);

        UjianKelas::create(['ujian_id' => $ujian->id, 'kelas_id' => $kelas->id]);
        Soal::create(['ujian_id' => $ujian->id, 'nomor_soal' => 1, 'kunci_jawaban' => 'A', 'bobot' => 1]);

        return compact('guru', 'tahunAjaran', 'kelas', 'mapel', 'ujian');
    }
}
