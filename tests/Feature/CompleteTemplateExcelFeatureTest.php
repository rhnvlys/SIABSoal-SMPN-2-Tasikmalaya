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
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

    public function test_downloaded_complete_template_can_be_previewed_without_processing_technical_or_output_sheets(): void
    {
        $data = $this->makeTemplateContext();

        PesertaUjian::create([
            'ujian_id' => $data['ujian']->id,
            'siswa_id' => $data['validSiswa']->id,
            'kelas_id' => $data['kelasUjian']->id,
            'status_kehadiran' => 'hadir',
        ]);

        $download = $this->actingAs($data['admin'])
            ->get(route('template-excel.download', [
                'type' => 'lengkap',
                'ujian_id' => $data['ujian']->id,
            ]))
            ->assertOk();

        $downloadContent = $download->baseResponse instanceof BinaryFileResponse
            ? file_get_contents($download->baseResponse->getFile()->getPathname())
            : $download->streamedContent();

        $file = UploadedFile::fake()->createWithContent(
            'Template_Administrasi_Penilaian_SIABSoal.xlsx',
            $downloadContent
        );

        $response = $this->actingAs($data['admin'])
            ->post(route('template-excel.upload.preview'), [
                'ujian_id' => $data['ujian']->id,
                'import_mode' => 'skor-01',
                'file' => $file,
            ]);

        $response->assertOk();
        $response->assertSee('File dapat diproses');
        $response->assertSee('DATA_IMPORT_SYSTEM');
        $response->assertSee('sheet teknis/fallback');
        $response->assertSee('HASIL_T1');
        $response->assertSee('sheet output');

        $preview = session('template_lengkap_preview');
        $this->assertSame([], $preview['errors']);
        $this->assertArrayHasKey('INPUT_SKOR_01', $preview['payload']);
        $this->assertArrayNotHasKey('INPUT_JAWABAN_ABCD', $preview['payload']);
        $this->assertNotContains('', array_column($preview['payload']['DATA_KELAS'] ?? [], 'nama_kelas'));
        $this->assertArrayHasKey('DATA_IMPORT_SYSTEM', $preview['ignored_sheets']);
        $this->assertArrayHasKey('INPUT_JAWABAN_ABCD', $preview['ignored_sheets']);
        $this->assertArrayHasKey('HASIL_T1', $preview['ignored_sheets']);
        $this->assertNotContains('DATA_IMPORT_SYSTEM', $preview['processable_sheets']);
    }

    public function test_complete_template_marks_missing_analysis_as_belum_dianalisis(): void
    {
        $data = $this->makeTemplateContext();

        $raw = Excel::raw(
            new AssessmentTemplateExport('lengkap', $data['ujian']),
            ExcelFormat::XLSX
        );
        $path = tempnam(sys_get_temp_dir(), 'siabsoal-analysis-');
        file_put_contents($path, $raw);

        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getSheetByName('ANALISIS_T3');

            $this->assertSame('-', $sheet->getCell('G7')->getValue());
            $this->assertSame('Belum Dianalisis', $sheet->getCell('H7')->getValue());
            $this->assertSame('-', $sheet->getCell('I7')->getValue());
            $this->assertSame('Belum Dianalisis', $sheet->getCell('J7')->getValue());
            $this->assertSame('Belum Dianalisis', $sheet->getCell('K7')->getValue());
        } finally {
            @unlink($path);
        }
    }

    public function test_template_excel_page_describes_complete_workbook_as_sixteen_sheets(): void
    {
        $data = $this->makeTemplateContext();

        $this->actingAs($data['admin'])
            ->get(route('template-excel.index', ['ujian_id' => $data['ujian']->id]))
            ->assertOk()
            ->assertSee('16 sheet: Identitas, Data Kelas, Data Siswa, TP/LM/KKTP, Daftar Hadir, Kunci Jawaban, Input Jawaban, Input Skor, T1-T5, Referensi.')
            ->assertSee('Sumber Jawaban Utama');
    }

    public function test_guru_gets_clear_message_for_inaccessible_exam_and_empty_state_without_exams(): void
    {
        $data = $this->makeTemplateContext();
        $otherGuruUser = $this->makeUser('Guru', 'guru_tanpa_ujian_' . uniqid());

        Guru::create([
            'user_id' => $otherGuruUser->id,
            'nama_guru' => 'Guru Tanpa Ujian',
            'jenis_kelamin' => 'P',
            'status' => 'aktif',
        ]);

        $this->actingAs($otherGuruUser)
            ->get(route('template-excel.index', ['ujian_id' => $data['ujian']->id]))
            ->assertRedirect(route('template-excel.index'))
            ->assertSessionHas('error', 'Ujian tidak ditemukan atau Anda tidak memiliki akses ke ujian ini.');

        $this->actingAs($otherGuruUser)
            ->get(route('template-excel.index'))
            ->assertOk()
            ->assertSee('Belum ada ujian yang dapat diproses. Silakan buat ujian terlebih dahulu atau hubungi admin.');

        $this->actingAs($otherGuruUser)
            ->post(route('template-excel.upload.preview'), [
                'ujian_id' => $data['ujian']->id,
                'import_mode' => 'skor-01',
                'file' => UploadedFile::fake()->create('template.xlsx', 10),
            ])
            ->assertRedirect(route('template-excel.index'))
            ->assertSessionHas('error', 'Ujian tidak ditemukan atau Anda tidak memiliki akses ke ujian ini.');
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
