<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SiswaImportFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_siswa_template_download_is_accessible(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)
            ->get(route('siswa.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_siswa_import_preview_validates_and_keeps_correct_rows(): void
    {
        $admin = $this->createAdmin();
        $tahunAjaran = TahunAjaran::firstOrCreate(
            ['tahun_ajaran' => '2025/2026', 'semester' => 'Ganjil'],
            ['status' => 'aktif']
        );
        $kelas = Kelas::firstOrCreate(
            ['nama_kelas' => 'IX A', 'tahun_ajaran_id' => $tahunAjaran->id],
            ['tingkat' => 'IX']
        );

        $file = $this->csvUpload([
            ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'kelas', 'tahun_ajaran', 'status'],
            ['99001', '320099001', 'Budi Santoso', 'L', 'IX A', '2025/2026', 'aktif'],
            ['99002', '320099002', 'Siti Aminah', 'P', 'IX A', '2025/2026', 'aktif'],
        ]);

        $response = $this->actingAs($admin)
            ->post(route('siswa.import.preview'), [
                'file' => $file,
            ]);

        $response->assertOk();
        $response->assertSee('Data Valid');
        $response->assertSee('Budi Santoso');
        $response->assertSee('Siti Aminah');

        $this->assertCount(2, session('siswa_import_preview_data'));
        $this->assertSame('99001', session('siswa_import_preview_data.0.nis'));
        $this->assertSame('99002', session('siswa_import_preview_data.1.nis'));
    }

    public function test_siswa_import_preview_reports_errors_for_invalid_data(): void
    {
        $admin = $this->createAdmin();

        $file = $this->csvUpload([
            ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'kelas', 'tahun_ajaran', 'status'],
            ['', '320099001', 'Budi Santoso', 'L', 'IX A', '2025/2026', 'aktif'], // NIS empty
            ['99002', '320099002', 'Siti Aminah', 'X', 'IX A', '2025/2026', 'aktif'], // invalid JK
            ['99003', '320099003', 'Rudi', 'L', 'Kelas Fiktif', '2025/2026', 'aktif'], // Kelas not found
        ]);

        $response = $this->actingAs($admin)
            ->post(route('siswa.import.preview'), [
                'file' => $file,
            ]);

        $response->assertOk();
        $response->assertSee('Data Error');
        $response->assertSee('NIS wajib diisi');
        $response->assertSee('Jenis kelamin harus L atau P');
        $response->assertSee('tidak ditemukan');
    }

    public function test_siswa_import_confirm_persists_students_and_relations(): void
    {
        $admin = $this->createAdmin();
        $tahunAjaran = TahunAjaran::firstOrCreate(
            ['tahun_ajaran' => '2025/2026', 'semester' => 'Ganjil'],
            ['status' => 'aktif']
        );
        $kelas = Kelas::firstOrCreate(
            ['nama_kelas' => 'IX A', 'tahun_ajaran_id' => $tahunAjaran->id],
            ['tingkat' => 'IX']
        );

        $file = $this->csvUpload([
            ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'kelas', 'tahun_ajaran', 'status'],
            ['99001', '320099001', 'Budi Santoso', 'L', 'IX A', '2025/2026', 'aktif'],
        ]);

        $this->actingAs($admin)
            ->post(route('siswa.import.preview'), [
                'file' => $file,
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('siswa.import.confirm'))
            ->assertRedirect(route('siswa.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('siswa', [
            'nis' => '99001',
            'nisn' => '320099001',
            'nama_siswa' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $siswa = Siswa::where('nis', '99001')->firstOrFail();

        $this->assertDatabaseHas('siswa_kelas', [
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'status' => 'aktif',
        ]);
    }

    private function createAdmin(): User
    {
        $role = Role::firstOrCreate(['nama_role' => 'Admin']);
        return User::create([
            'role_id' => $role->id,
            'name' => 'Admin Siswa Test',
            'username' => 'admin_siswa_test_' . uniqid(),
            'password' => 'password',
            'status' => 'aktif',
        ]);
    }

    private function csvUpload(array $rows): UploadedFile
    {
        $csv = collect($rows)
            ->map(fn (array $row) => implode(',', $row))
            ->implode("\n");

        return UploadedFile::fake()->createWithContent('siswa_test.csv', $csv);
    }
}
