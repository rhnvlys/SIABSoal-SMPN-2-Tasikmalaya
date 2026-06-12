<?php

namespace Tests\Feature;

use App\Exports\AssessmentTemplateExport;
use App\Exports\StyledArraySheet;
use App\Models\Guru;
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
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Tests\TestCase;

class CompleteTemplateExcelFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_complete_template_uses_school_requested_sheet_structure(): void
    {
        $data = $this->makeTemplateContext();

        $export = new AssessmentTemplateExport('lengkap', $data['ujian']);
        $sheetTitles = array_map(fn ($sheet) => $sheet->title(), $export->sheets());

        $this->assertSame([
            'BERANDA',
            'IDENTITAS',
            'DATA_KELAS',
            'DATA_SISWA',
            'TP_LM_KKTP',
            'DAFTAR_HADIR',
            'KUNCI_JAWABAN',
            'INPUT_JAWABAN_ABCD',
            'INPUT_SKOR_01',
            'HASIL_T1',
            'OLAH_T2',
            'ANALISIS_T3',
            'DAFTAR_NILAI_T4',
            'REKAP_NILAI_T5',
            'DATA_IMPORT_SYSTEM',
            'REFERENSI',
        ], $sheetTitles);

        $this->actingAs($data['admin'])
            ->get(route('template-excel.download', [
                'type' => 'lengkap',
                'ujian_id' => $data['ujian']->id,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_guru_preview_complete_template_processes_only_allowed_exam_rows_and_ignores_global_sheets(): void
    {
        $data = $this->makeTemplateContext();

        $file = $this->completeWorkbookUpload([
            'DATA_KELAS' => [
                ['kode_kelas', 'nama_kelas', 'tingkat', 'tahun_ajaran', 'wali_kelas'],
                ['KLS-1', $data['kelasUjian']->nama_kelas, 'IX', $data['tahunAjaran']->tahun_ajaran, $data['guru']->nama_guru],
            ],
            'DATA_SISWA' => [
                ['no', 'nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'kelas', 'status_siswa'],
                [1, $data['validSiswa']->nis, $data['validSiswa']->nisn, $data['validSiswa']->nama_siswa, 'L', $data['kelasUjian']->nama_kelas, 'aktif'],
                [2, $data['outsideSiswa']->nis, $data['outsideSiswa']->nisn, $data['outsideSiswa']->nama_siswa, 'P', $data['kelasLain']->nama_kelas, 'aktif'],
            ],
            'INPUT_SKOR_01' => [
                ['no', 'nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran', 'skor_1', 'jumlah_benar', 'nilai', 'keterangan'],
                [1, $data['validSiswa']->nis, $data['validSiswa']->nisn, $data['validSiswa']->nama_siswa, 'L', 'hadir', 1, '', '', ''],
                [2, $data['outsideSiswa']->nis, $data['outsideSiswa']->nisn, $data['outsideSiswa']->nama_siswa, 'P', 'hadir', 1, '', '', ''],
            ],
        ]);

        $response = $this->actingAs($data['guruUser'])
            ->post(route('template-excel.upload.preview'), [
                'ujian_id' => $data['ujian']->id,
                'file' => $file,
            ]);

        $response->assertOk();
        $response->assertSee('DATA_KELAS');
        $response->assertSee('diabaikan', false);
        $response->assertSee('hanya siswa kelas ' . $data['kelasUjian']->nama_kelas);

        $payload = session('template_lengkap_preview.payload');
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('INPUT_SKOR_01', $payload);
        $this->assertCount(1, $payload['INPUT_SKOR_01']);
        $this->assertSame($data['validSiswa']->nis, $payload['INPUT_SKOR_01'][0]['nis']);
    }

    public function test_guru_confirm_complete_template_persists_allowed_score_rows_only(): void
    {
        $data = $this->makeTemplateContext();
        $file = $this->completeWorkbookUpload([
            'INPUT_SKOR_01' => [
                ['no', 'nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran', 'skor_1', 'jumlah_benar', 'nilai', 'keterangan'],
                [1, $data['validSiswa']->nis, $data['validSiswa']->nisn, $data['validSiswa']->nama_siswa, 'L', 'hadir', 1, '', '', ''],
                [2, $data['outsideSiswa']->nis, $data['outsideSiswa']->nisn, $data['outsideSiswa']->nama_siswa, 'P', 'hadir', 1, '', '', ''],
            ],
        ]);

        $this->actingAs($data['guruUser'])
            ->post(route('template-excel.upload.preview'), [
                'ujian_id' => $data['ujian']->id,
                'file' => $file,
            ])
            ->assertOk();

        $this->actingAs($data['guruUser'])
            ->post(route('template-excel.upload.confirm'))
            ->assertRedirect(route('data-mentah.index', $data['ujian']))
            ->assertSessionHas('success');

        $peserta = PesertaUjian::where('ujian_id', $data['ujian']->id)
            ->where('siswa_id', $data['validSiswa']->id)
            ->firstOrFail();

        $this->assertDatabaseHas('jawaban_siswa', [
            'peserta_ujian_id' => $peserta->id,
            'soal_id' => $data['soal']->id,
            'skor_biner' => 1,
            'is_benar' => true,
        ]);

        $this->assertDatabaseMissing('peserta_ujian', [
            'ujian_id' => $data['ujian']->id,
            'siswa_id' => $data['outsideSiswa']->id,
        ]);
    }

    public function test_kepala_sekolah_cannot_upload_complete_template_to_modify_data(): void
    {
        $data = $this->makeTemplateContext();
        $kepala = $this->makeUser('Kepala Sekolah', 'kepala_lengkap_' . uniqid());

        $this->actingAs($kepala)
            ->post(route('template-excel.upload.preview'), [
                'ujian_id' => $data['ujian']->id,
                'file' => UploadedFile::fake()->create('template.xlsx', 10),
            ])
            ->assertForbidden();
    }

    private function makeTemplateContext(): array
    {
        $admin = $this->makeUser('Admin', 'admin_lengkap_' . uniqid());
        $guruUser = $this->makeUser('Guru', 'guru_lengkap_' . uniqid());

        $guru = Guru::create([
            'user_id' => $guruUser->id,
            'nama_guru' => 'Guru Template Lengkap',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2096/2097',
            'semester' => 'Ganjil',
            'status' => 'aktif',
        ]);

        $kelasUjian = Kelas::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'wali_kelas_id' => $guru->id,
            'nama_kelas' => 'IX Lengkap A',
            'tingkat' => 'IX',
        ]);

        $kelasLain = Kelas::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_kelas' => 'IX Lengkap B',
            'tingkat' => 'IX',
        ]);

        $mapel = Mapel::create([
            'kode_mapel' => 'LKP' . substr(uniqid(), -5),
            'nama_mapel' => 'Mapel Lengkap',
        ]);

        $validSiswa = Siswa::create([
            'nis' => 'LKP' . substr(uniqid(), -8),
            'nisn' => 'NISN' . substr(uniqid(), -8),
            'nama_siswa' => 'Siswa Lengkap Valid',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $outsideSiswa = Siswa::create([
            'nis' => 'LKO' . substr(uniqid(), -8),
            'nisn' => 'NISN' . substr(uniqid(), -7),
            'nama_siswa' => 'Siswa Lengkap Lain',
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
            'nama_ujian' => 'Ujian Template Lengkap ' . uniqid(),
            'jenis_ujian' => 'UH',
            'jenis_penilaian' => 'Penilaian Harian',
            'tanggal_ujian' => now()->toDateString(),
            'jumlah_soal' => 1,
            'kkm' => 75,
            'kktp' => 75,
            'metode_kelompok' => 'persen_50',
            'status' => 'kunci_lengkap',
        ]);

        UjianKelas::create(['ujian_id' => $ujian->id, 'kelas_id' => $kelasUjian->id]);
        $soal = Soal::create(['ujian_id' => $ujian->id, 'nomor_soal' => 1, 'kunci_jawaban' => 'A', 'bobot' => 1]);

        return compact('admin', 'guruUser', 'guru', 'tahunAjaran', 'kelasUjian', 'kelasLain', 'mapel', 'validSiswa', 'outsideSiswa', 'ujian', 'soal');
    }

    private function makeUser(string $roleName, string $username): User
    {
        $role = Role::firstOrCreate(['nama_role' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'name' => ucfirst(str_replace('_', ' ', $username)),
            'username' => $username,
            'password' => 'password',
            'status' => 'aktif',
        ]);
    }

    private function completeWorkbookUpload(array $sheets): UploadedFile
    {
        $export = new class($sheets) implements WithMultipleSheets {
            public function __construct(private readonly array $sheets)
            {
            }

            public function sheets(): array
            {
                $workbookSheets = [
                    new StyledArraySheet('BERANDA', [
                        ['SIABSoal SMPN 2 Tasikmalaya'],
                        ['Template Administrasi Penilaian'],
                    ]),
                ];

                foreach ($this->sheets as $title => $rows) {
                    $workbookSheets[] = new StyledArraySheet($title, $rows);
                }

                return $workbookSheets;
            }
        };

        return UploadedFile::fake()->createWithContent(
            'Template_Administrasi_Penilaian_SIABSoal.xlsx',
            Excel::raw($export, ExcelFormat::XLSX)
        );
    }
}
