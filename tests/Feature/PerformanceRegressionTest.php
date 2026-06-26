<?php

namespace Tests\Feature;

use App\Exports\ArrayReportExport;
use App\Models\AnalisisButir;
use App\Models\Guru;
use App\Models\JawabanSiswa;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\PesertaUjian;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\Ujian;
use App\Models\UjianKelas;
use App\Models\User;
use App\Services\GroupingService;
use App\Services\ItemAnalysisService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PerformanceRegressionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_t2_ranking_uses_bounded_query_count_and_never_groups_absent_students(): void
    {
        $data = $this->makeLargeContext();
        $queries = 0;
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries++;
        });

        $result = app(GroupingService::class)->prosesKelompok($data['ujian']->id);

        $this->assertLessThanOrEqual(12, $queries, "T2 menjalankan {$queries} query; ranking masih diperbarui per peserta.");
        $this->assertSame(24, $result['jumlah_hadir']);
        $this->assertSame(12, $result['jumlah_kelompok']);
        $this->assertSame(12, PesertaUjian::where('ujian_id', $data['ujian']->id)->where('kelompok', 'atas')->count());
        $this->assertSame(12, PesertaUjian::where('ujian_id', $data['ujian']->id)->where('kelompok', 'bawah')->count());
        $this->assertFalse(PesertaUjian::where('ujian_id', $data['ujian']->id)
            ->where('status_kehadiran', 'tidak_hadir')->whereNotNull('kelompok')->exists());
    }

    public function test_t3_uses_aggregate_query_and_preserves_dp_tk_results(): void
    {
        $data = $this->makeLargeContext();
        app(GroupingService::class)->prosesKelompok($data['ujian']->id);
        $queries = 0;
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries++;
        });

        $result = app(ItemAnalysisService::class)->prosesAnalisis($data['ujian']->id);

        $this->assertLessThanOrEqual(12, $queries, "T3 menjalankan {$queries} query; BA/BB masih dihitung per soal.");
        $this->assertCount(25, $result);
        $this->assertSame(25, AnalisisButir::where('ujian_id', $data['ujian']->id)->count());
        $first = $data['ujian']->analisisButir()->where('nomor_soal', 1)->firstOrFail();
        $this->assertSame(12, $first->ja);
        $this->assertSame(12, $first->jb);
        $this->assertNotNull($first->dp);
        $this->assertNotNull($first->tk);
    }

    public function test_large_t2_t3_t4_tables_are_paginated(): void
    {
        $data = $this->makeLargeContext();
        app(GroupingService::class)->prosesKelompok($data['ujian']->id);
        app(ItemAnalysisService::class)->prosesAnalisis($data['ujian']->id);

        $this->actingAs($data['admin'])
            ->get(route('olah-data.index', $data['ujian']))
            ->assertOk()
            ->assertViewHas('pesertaHadir', fn ($value) => $value instanceof LengthAwarePaginator
                && $value->count() === 20 && $value->total() === 24);

        $this->get(route('analisis-data.index', $data['ujian']))
            ->assertOk()
            ->assertViewHas('analisis', fn ($value) => $value instanceof LengthAwarePaginator
                && $value->count() === 20 && $value->total() === 25);

        $this->get(route('daftar-nilai.index', $data['ujian']))
            ->assertOk()
            ->assertViewHas('peserta', fn ($value) => $value instanceof LengthAwarePaginator
                && $value->count() === 20 && $value->total() === 25);
    }

    public function test_guru_dashboard_does_not_eager_load_all_answers_and_analysis(): void
    {
        $data = $this->makeLargeContext();

        $this->actingAs($data['guruUser'])
            ->get(route('dashboard.guru'))
            ->assertOk()
            ->assertViewHas('ujianLanjutkan', function (?Ujian $ujian): bool {
                return $ujian !== null
                    && !$ujian->relationLoaded('soal')
                    && !$ujian->relationLoaded('pesertaUjian')
                    && !$ujian->relationLoaded('analisisButir');
            });
    }

    public function test_t1_to_t5_excel_exports_use_one_lightweight_report_sheet(): void
    {
        $data = $this->makeLargeContext();
        app(GroupingService::class)->prosesKelompok($data['ujian']->id);
        app(ItemAnalysisService::class)->prosesAnalisis($data['ujian']->id);
        Excel::fake();
        $this->actingAs($data['admin']);

        $exports = [
            'export.data-mentah.excel' => 'data_mentah_',
            'export.olah-data.excel' => 'olah_data_',
            'export.analisis.excel' => 'analisis_butir_',
            'export.daftar-nilai.excel' => 'daftar_nilai_',
            'export.rekap-nilai.excel' => 'rekap_nilai_',
        ];

        foreach ($exports as $routeName => $prefix) {
            $this->get(route($routeName, $data['ujian']))->assertOk();
            $filename = str_replace(' ', '_', $prefix . $data['ujian']->nama_ujian) . '.xlsx';
            Excel::assertDownloaded($filename, function ($export): bool {
                return $export instanceof ArrayReportExport
                    && count($export->array()) > 2;
            });
        }
    }

    private function makeLargeContext(): array
    {
        $role = Role::firstOrCreate(['nama_role' => 'Admin']);
        $admin = User::create([
            'role_id' => $role->id,
            'name' => 'Admin Performance',
            'username' => 'admin_perf_' . uniqid(),
            'password' => 'password123',
            'status' => 'aktif',
        ]);
        $guruRole = Role::firstOrCreate(['nama_role' => 'Guru']);
        $guruUser = User::create([
            'role_id' => $guruRole->id,
            'name' => 'Guru Performance',
            'username' => 'guru_perf_' . uniqid(),
            'password' => 'password123',
            'status' => 'aktif',
        ]);
        $guru = Guru::create([
            'user_id' => $guruUser->id,
            'nama_guru' => 'Guru Performance',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2095/2096',
            'semester' => 'Ganjil',
            'status' => 'aktif',
        ]);
        $kelas = Kelas::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_kelas' => 'TEST_QA_PERF_' . substr(uniqid(), -5),
            'tingkat' => 'IX',
        ]);
        $mapel = Mapel::create([
            'kode_mapel' => 'PF' . substr(uniqid(), -6),
            'nama_mapel' => 'TEST QA Performance',
        ]);
        $ujian = Ujian::create([
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_ujian' => 'TEST_QA_PERFORMANCE_' . substr(uniqid(), -5),
            'jenis_ujian' => 'UH',
            'tanggal_ujian' => now()->toDateString(),
            'jumlah_soal' => 25,
            'kkm' => 75,
            'kktp' => 75,
            'metode_kelompok' => 'persen_50',
            'status' => 'data_mentah',
        ]);
        UjianKelas::create(['ujian_id' => $ujian->id, 'kelas_id' => $kelas->id]);

        $questions = collect();
        for ($questionNumber = 1; $questionNumber <= 25; $questionNumber++) {
            $questions->push(Soal::create([
                'ujian_id' => $ujian->id,
                'nomor_soal' => $questionNumber,
                'kunci_jawaban' => 'A',
                'bobot' => 1,
            ]));
        }

        for ($studentNumber = 1; $studentNumber <= 25; $studentNumber++) {
            $siswa = Siswa::create([
                'nis' => sprintf('PF%02d%s', $studentNumber, substr(uniqid(), -5)),
                'nama_siswa' => sprintf('Siswa Performance %02d', $studentNumber),
                'jenis_kelamin' => $studentNumber % 2 === 0 ? 'P' : 'L',
                'status' => 'aktif',
            ]);
            $correct = $studentNumber === 25 ? 0 : 25 - $studentNumber + 1;
            $peserta = PesertaUjian::create([
                'ujian_id' => $ujian->id,
                'siswa_id' => $siswa->id,
                'kelas_id' => $kelas->id,
                'status_kehadiran' => $studentNumber === 25 ? 'tidak_hadir' : 'hadir',
                'jumlah_benar' => $correct,
                'jumlah_salah' => 25 - $correct,
                'total_skor' => $correct,
                'nilai' => $correct * 4,
                'keterangan' => $studentNumber === 25
                    ? 'tidak_hadir'
                    : ($correct * 4 >= 75 ? 'tercapai' : 'perlu_peningkatan'),
            ]);

            foreach ($questions as $index => $question) {
                $isCorrect = $index < $correct && $studentNumber !== 25;
                JawabanSiswa::create([
                    'peserta_ujian_id' => $peserta->id,
                    'soal_id' => $question->id,
                    'jawaban' => $isCorrect ? 'A' : 'B',
                    'skor_biner' => $isCorrect ? 1 : 0,
                    'is_benar' => $isCorrect,
                ]);
            }
        }

        return compact('admin', 'guruUser', 'ujian');
    }
}
