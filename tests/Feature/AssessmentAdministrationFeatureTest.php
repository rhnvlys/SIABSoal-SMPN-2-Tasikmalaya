<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Role;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianKelas;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AssessmentAdministrationFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_create_ujian_with_assessment_administration_fields(): void
    {
        $data = $this->makeBaseData();

        $response = $this->actingAs($data['admin'])->post(route('ujian.store'), [
            'guru_id' => $data['guru']->id,
            'mapel_id' => $data['mapel']->id,
            'tahun_ajaran_id' => $data['tahunAjaran']->id,
            'nama_ujian' => 'Ulangan Administrasi Excel',
            'jenis_penilaian' => 'Penilaian Harian',
            'tujuan_pembelajaran' => 'Peserta didik memahami materi pengujian.',
            'lingkup_materi' => 'Materi administrasi penilaian.',
            'tanggal_ujian' => now()->toDateString(),
            'jumlah_soal' => 5,
            'kktp' => 76,
            'metode_kelompok' => 'persen_50',
            'kelas_ids' => [$data['kelas']->id],
        ]);

        $response->assertRedirect(route('ujian.index'));

        $this->assertDatabaseHas('ujian', [
            'nama_ujian' => 'Ulangan Administrasi Excel',
            'jenis_penilaian' => 'Penilaian Harian',
            'jenis_ujian' => 'UH',
            'tujuan_pembelajaran' => 'Peserta didik memahami materi pengujian.',
            'lingkup_materi' => 'Materi administrasi penilaian.',
            'kkm' => 76,
            'kktp' => 76,
        ]);

        $ujian = Ujian::where('nama_ujian', 'Ulangan Administrasi Excel')->firstOrFail();
        $this->assertCount(5, $ujian->soal);
        $this->assertDatabaseHas('ujian_kelas', [
            'ujian_id' => $ujian->id,
            'kelas_id' => $data['kelas']->id,
        ]);
    }

    public function test_template_excel_download_is_available_and_scoped_to_teacher_ujian(): void
    {
        $data = $this->makeBaseData();
        $other = $this->makeTeacherUjian('Guru Lain');

        Excel::fake();

        $this->actingAs($data['admin'])
            ->get(route('template-excel.download', ['type' => 'data-siswa']))
            ->assertOk();
        Excel::assertDownloaded('data_siswa.xlsx');

        $this->actingAs($data['guruUser'])
            ->get(route('template-excel.download', ['type' => 'jawaban-abcd', 'ujian_id' => $data['ujian']->id]))
            ->assertOk();

        $this->actingAs($data['guruUser'])
            ->get(route('template-excel.download', ['type' => 'jawaban-abcd', 'ujian_id' => $other['ujian']->id]))
            ->assertNotFound();
    }

    private function makeBaseData(): array
    {
        $adminRole = Role::firstOrCreate(['nama_role' => 'Admin']);
        $guruRole = Role::firstOrCreate(['nama_role' => 'Guru']);

        $admin = User::create([
            'role_id' => $adminRole->id,
            'name' => 'Admin Assessment',
            'username' => 'admin_assessment_' . uniqid(),
            'password' => 'password',
            'status' => 'aktif',
        ]);

        $guruUser = User::create([
            'role_id' => $guruRole->id,
            'name' => 'Guru Assessment',
            'username' => 'guru_assessment_' . uniqid(),
            'password' => 'password',
            'status' => 'aktif',
        ]);

        $guru = Guru::create([
            'user_id' => $guruUser->id,
            'nama_guru' => 'Guru Assessment',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $tahunAjaran = TahunAjaran::firstOrCreate([
            'tahun_ajaran' => '2098/2099',
            'semester' => 'Ganjil',
        ], [
            'status' => 'aktif',
        ]);

        $kelas = Kelas::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_kelas' => 'IX Assessment',
            'tingkat' => 'IX',
        ]);

        $mapel = Mapel::create([
            'kode_mapel' => 'ASM' . substr(uniqid(), -5),
            'nama_mapel' => 'Mapel Assessment',
        ]);

        $ujian = Ujian::create([
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_ujian' => 'Ujian Template ' . uniqid(),
            'jenis_ujian' => 'UH',
            'jenis_penilaian' => 'Ulangan Harian',
            'tanggal_ujian' => now()->toDateString(),
            'jumlah_soal' => 2,
            'kkm' => 75,
            'kktp' => 75,
            'metode_kelompok' => 'persen_50',
            'status' => 'draft',
        ]);

        UjianKelas::create(['ujian_id' => $ujian->id, 'kelas_id' => $kelas->id]);

        return compact('admin', 'guruUser', 'guru', 'tahunAjaran', 'kelas', 'mapel', 'ujian');
    }

    private function makeTeacherUjian(string $namaGuru): array
    {
        $data = $this->makeBaseData();
        $data['guru']->update(['nama_guru' => $namaGuru]);
        $data['guruUser']->update(['name' => $namaGuru]);

        return $data;
    }
}
