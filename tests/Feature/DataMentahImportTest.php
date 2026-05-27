<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\JawabanSiswa;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\PesertaUjian;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianKelas;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DataMentahImportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_preview_keeps_only_valid_rows_and_rejects_students_outside_ujian_classes(): void
    {
        $data = $this->makeImportData();
        $file = $this->csvUpload([
            ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran', 'soal_1'],
            [$data['validSiswa']->nis, $data['validSiswa']->nisn, $data['validSiswa']->nama_siswa, 'L', 'hadir', 'A'],
            [$data['outsideSiswa']->nis, $data['outsideSiswa']->nisn, $data['outsideSiswa']->nama_siswa, 'P', 'hadir', 'B'],
        ]);

        $response = $this->actingAs($data['admin'])
            ->post(route('data-mentah.preview', $data['ujian']), [
                'mode' => 'abcd',
                'file' => $file,
            ]);

        $response->assertOk();
        $response->assertSee('Data Error');
        $response->assertSee('tidak terdaftar pada kelas ujian');

        $this->assertCount(1, session('import_preview_data'));
        $this->assertSame($data['validSiswa']->nis, session('import_preview_data.0.nis'));
        $this->assertSame($data['kelasUjian']->id, session('import_preview_data.0.kelas_id'));
    }

    public function test_confirm_import_is_blocked_when_preview_has_errors(): void
    {
        $data = $this->makeImportData();
        $file = $this->csvUpload([
            ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran', 'soal_1'],
            [$data['validSiswa']->nis, $data['validSiswa']->nisn, $data['validSiswa']->nama_siswa, 'L', 'hadir', 'A'],
            [$data['outsideSiswa']->nis, $data['outsideSiswa']->nisn, $data['outsideSiswa']->nama_siswa, 'P', 'hadir', 'B'],
        ]);

        $this->actingAs($data['admin'])
            ->post(route('data-mentah.preview', $data['ujian']), [
                'mode' => 'abcd',
                'file' => $file,
            ])
            ->assertOk();

        $this->actingAs($data['admin'])
            ->post(route('data-mentah.confirm-import', $data['ujian']))
            ->assertRedirect(route('data-mentah.index', $data['ujian']))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('peserta_ujian', [
            'ujian_id' => $data['ujian']->id,
            'siswa_id' => $data['validSiswa']->id,
        ]);
    }

    public function test_confirm_import_persists_rows_when_preview_has_no_errors(): void
    {
        $data = $this->makeImportData();
        $file = $this->csvUpload([
            ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran', 'soal_1'],
            [$data['validSiswa']->nis, $data['validSiswa']->nisn, $data['validSiswa']->nama_siswa, 'L', 'hadir', 'A'],
        ]);

        $this->actingAs($data['admin'])
            ->post(route('data-mentah.preview', $data['ujian']), [
                'mode' => 'abcd',
                'file' => $file,
            ])
            ->assertOk();

        $this->actingAs($data['admin'])
            ->post(route('data-mentah.confirm-import', $data['ujian']))
            ->assertRedirect(route('data-mentah.index', $data['ujian']));

        $this->assertDatabaseHas('peserta_ujian', [
            'ujian_id' => $data['ujian']->id,
            'siswa_id' => $data['validSiswa']->id,
            'kelas_id' => $data['kelasUjian']->id,
        ]);

        $this->assertDatabaseMissing('peserta_ujian', [
            'ujian_id' => $data['ujian']->id,
            'siswa_id' => $data['outsideSiswa']->id,
        ]);

        $peserta = PesertaUjian::where('ujian_id', $data['ujian']->id)
            ->where('siswa_id', $data['validSiswa']->id)
            ->firstOrFail();

        $this->assertDatabaseHas('jawaban_siswa', [
            'peserta_ujian_id' => $peserta->id,
            'soal_id' => $data['soal']->id,
            'jawaban' => 'A',
        ]);
    }

    public function test_preview_rejects_header_that_does_not_match_template(): void
    {
        $data = $this->makeImportData();
        $file = $this->csvUpload([
            ['nis', 'nisn', 'nama', 'jenis_kelamin', 'status_kehadiran', 'soal_1'],
            [$data['validSiswa']->nis, $data['validSiswa']->nisn, $data['validSiswa']->nama_siswa, 'L', 'hadir', 'A'],
        ]);

        $this->actingAs($data['admin'])
            ->from(route('data-mentah.index', $data['ujian']))
            ->post(route('data-mentah.preview', $data['ujian']), [
                'mode' => 'abcd',
                'file' => $file,
            ])
            ->assertRedirect(route('data-mentah.index', $data['ujian']))
            ->assertSessionHas('error');
    }

    private function makeImportData(): array
    {
        $adminRole = Role::firstOrCreate(['nama_role' => 'Admin']);

        $admin = User::create([
            'role_id' => $adminRole->id,
            'name' => 'Admin Import',
            'username' => 'admin_import_' . uniqid(),
            'password' => 'password',
            'status' => 'aktif',
        ]);

        $guru = Guru::create([
            'nama_guru' => 'Guru Import',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2097/2098',
            'semester' => 'Ganjil',
            'status' => 'aktif',
        ]);

        $kelasUjian = Kelas::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_kelas' => 'IX Import A',
            'tingkat' => 'IX',
        ]);

        $kelasLain = Kelas::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_kelas' => 'IX Import B',
            'tingkat' => 'IX',
        ]);

        $mapel = Mapel::create([
            'kode_mapel' => 'IMP' . substr(uniqid(), -5),
            'nama_mapel' => 'Mapel Import',
        ]);

        $validSiswa = Siswa::create([
            'nis' => 'IMP' . substr(uniqid(), -8),
            'nisn' => 'NISN' . substr(uniqid(), -8),
            'nama_siswa' => 'Siswa Valid',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $outsideSiswa = Siswa::create([
            'nis' => 'OUT' . substr(uniqid(), -8),
            'nisn' => 'NISN' . substr(uniqid(), -7),
            'nama_siswa' => 'Siswa Kelas Lain',
            'jenis_kelamin' => 'P',
            'status' => 'aktif',
        ]);

        SiswaKelas::create([
            'siswa_id' => $validSiswa->id,
            'kelas_id' => $kelasUjian->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'status' => 'aktif',
        ]);

        SiswaKelas::create([
            'siswa_id' => $outsideSiswa->id,
            'kelas_id' => $kelasLain->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'status' => 'aktif',
        ]);

        $ujian = Ujian::create([
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_ujian' => 'Ujian Import ' . uniqid(),
            'jenis_ujian' => 'UH',
            'tanggal_ujian' => now()->toDateString(),
            'jumlah_soal' => 1,
            'kkm' => 75,
            'metode_kelompok' => 'persen_50',
            'status' => 'kunci_lengkap',
        ]);

        UjianKelas::create([
            'ujian_id' => $ujian->id,
            'kelas_id' => $kelasUjian->id,
        ]);

        $soal = Soal::create([
            'ujian_id' => $ujian->id,
            'nomor_soal' => 1,
            'kunci_jawaban' => 'A',
            'bobot' => 1,
        ]);

        return compact('admin', 'guru', 'tahunAjaran', 'kelasUjian', 'kelasLain', 'mapel', 'validSiswa', 'outsideSiswa', 'ujian', 'soal');
    }

    private function csvUpload(array $rows): UploadedFile
    {
        $csv = collect($rows)
            ->map(fn (array $row) => implode(',', $row))
            ->implode("\n");

        return UploadedFile::fake()->createWithContent('data_mentah.csv', $csv);
    }
}
