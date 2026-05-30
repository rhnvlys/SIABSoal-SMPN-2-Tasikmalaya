<?php

namespace Tests\Feature;

use App\Exports\DataMentahTemplateExport;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Mapel;
use App\Models\PengaturanSekolah;
use App\Models\Role;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianKelas;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Tests\TestCase;

class AuditLogoExportFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_audit_log_records_actor_snapshot_and_is_visible_to_admin_and_kepala_only(): void
    {
        $admin = $this->makeUser('Admin', 'admin_audit_' . uniqid());
        $kepala = $this->makeUser('Kepala Sekolah', 'kepsek_audit_' . uniqid());
        $guru = $this->makeUser('Guru', 'guru_audit_' . uniqid());

        $this->actingAs($admin);
        LogAktivitas::catat('Tambah ujian', 'Ujian', 'Admin menambahkan ujian UTS Matematika kelas VII', Ujian::class, 99);

        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $admin->id,
            'nama_user' => $admin->name,
            'role' => 'Admin',
            'aksi' => 'Tambah ujian',
            'modul' => 'Ujian',
            'deskripsi' => 'Admin menambahkan ujian UTS Matematika kelas VII',
            'related_type' => Ujian::class,
            'related_id' => 99,
        ]);

        $this->actingAs($admin)
            ->get('/audit-log')
            ->assertOk()
            ->assertSee('Riwayat Aktivitas')
            ->assertSee('Admin menambahkan ujian UTS Matematika kelas VII');

        $this->actingAs($admin)
            ->get('/audit-trail')
            ->assertOk()
            ->assertSee('Riwayat Aktivitas');

        $this->actingAs($kepala)
            ->get('/audit-log')
            ->assertOk()
            ->assertSee('Riwayat Aktivitas');

        $this->actingAs($guru)
            ->get('/audit-log')
            ->assertForbidden();
    }

    public function test_admin_can_upload_logo_and_landing_uses_public_logo_url(): void
    {
        Storage::fake('public');

        $admin = $this->makeUser('Admin', 'admin_logo_' . uniqid());
        $pengaturan = PengaturanSekolah::firstOrCreate(
            ['id' => 1],
            [
                'nama_sekolah' => 'SMP NEGERI 2 TASIKMALAYA',
                'nama_sistem' => 'SIABSoal SMPN 2 Tasikmalaya',
                'nama_lengkap_sistem' => 'Sistem Informasi Analisis Butir Soal Berbasis Web SMPN 2 Tasikmalaya',
            ]
        );

        $this->actingAs($admin)
            ->put('/profil-sekolah', [
                'nama_sekolah' => $pengaturan->nama_sekolah,
                'nama_sistem' => $pengaturan->nama_sistem,
                'nama_lengkap_sistem' => $pengaturan->nama_lengkap_sistem,
                'alamat' => 'Jl. Letkol RE Djaelani No. 1',
                'kepala_sekolah' => 'Kepala Sekolah',
                'nip_kepala_sekolah' => '196501011990031001',
                'logo' => UploadedFile::fake()->image('logo.png', 80, 80),
            ])
            ->assertRedirect('/profil-sekolah');

        $pengaturan->refresh();

        $this->assertStringStartsWith('logo-sekolah/', $pengaturan->logo);
        Storage::disk('public')->assertExists($pengaturan->logo);

        $this->get('/logo-sekolah/' . $pengaturan->logo)
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->get('/')
            ->assertOk()
            ->assertSee('/logo-sekolah/' . $pengaturan->logo, false);
    }

    public function test_data_mentah_and_kunci_templates_are_readable_xlsx_workbooks(): void
    {
        $data = $this->makeUjianContext();
        $export = new DataMentahTemplateExport($data['ujian'], 'biner');

        $this->assertInstanceOf(WithMultipleSheets::class, $export);
        $this->assertSame(['PETUNJUK', 'IMPORT_SKOR_01'], array_map(
            fn ($sheet) => $sheet->title(),
            $export->sheets()
        ));

        $this->actingAs($data['guru']->user)
            ->get(route('data-mentah.template', [$data['ujian'], 'biner']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($data['guru']->user)
            ->get(route('kunci-jawaban.template', $data['ujian']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function makeUser(string $roleName, string $username): User
    {
        $role = Role::firstOrCreate(['nama_role' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'name' => ucfirst(str_replace('_', ' ', $username)),
            'username' => $username,
            'password' => 'password123',
            'status' => 'aktif',
        ]);
    }

    private function makeUjianContext(): array
    {
        $guruUser = $this->makeUser('Guru', 'guru_template_' . uniqid());
        $guru = Guru::create([
            'user_id' => $guruUser->id,
            'nama_guru' => 'Guru Template',
            'status' => 'aktif',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2098/2099',
            'semester' => 'Ganjil',
            'status' => 'aktif',
        ]);

        $kelas = Kelas::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'wali_kelas_id' => $guru->id,
            'nama_kelas' => 'VII Template',
            'tingkat' => 'VII',
        ]);

        $mapel = Mapel::create([
            'kode_mapel' => 'TMP' . substr(uniqid(), -5),
            'nama_mapel' => 'Mapel Template',
        ]);

        $ujian = Ujian::create([
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_ujian' => 'Ujian Template ' . uniqid(),
            'jenis_ujian' => 'UH',
            'tanggal_ujian' => now()->toDateString(),
            'jumlah_soal' => 2,
            'kkm' => 75,
            'metode_kelompok' => 'persen_50',
            'status' => 'kunci_lengkap',
        ]);

        UjianKelas::create(['ujian_id' => $ujian->id, 'kelas_id' => $kelas->id]);
        Soal::create(['ujian_id' => $ujian->id, 'nomor_soal' => 1, 'kunci_jawaban' => 'A', 'bobot' => 1]);
        Soal::create(['ujian_id' => $ujian->id, 'nomor_soal' => 2, 'kunci_jawaban' => 'B', 'bobot' => 1]);

        return compact('guru', 'tahunAjaran', 'kelas', 'mapel', 'ujian');
    }
}
