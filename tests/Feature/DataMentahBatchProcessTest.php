<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class DataMentahBatchProcessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_start_initializes_progress_and_resets_dependent_t2_t3_results(): void
    {
        $data = $this->makeBatchContext();

        $response = $this->actingAs($data['admin'])->postJson(
            "/ujian/{$data['ujian']->id}/data-mentah/proses/start",
            ['mode' => 'abcd', 'reset' => true]
        );

        $response->assertOk()
            ->assertJsonPath('status', 'running')
            ->assertJsonPath('total', 14)
            ->assertJsonPath('processed', 0)
            ->assertJsonPath('remaining', 14)
            ->assertJsonPath('progress', 0)
            ->assertJsonPath('next_offset', 0);

        $this->assertDatabaseMissing('analisis_butir', ['ujian_id' => $data['ujian']->id]);
        $this->assertDatabaseMissing('peserta_ujian', [
            'ujian_id' => $data['ujian']->id,
            'kelompok' => 'atas',
        ]);
        $this->assertSame('kunci_lengkap', $data['ujian']->fresh()->status);
    }

    public function test_each_batch_processes_only_requested_participants_and_reports_progress(): void
    {
        $data = $this->makeBatchContext();
        $this->actingAs($data['admin'])->postJson(
            "/ujian/{$data['ujian']->id}/data-mentah/proses/start",
            ['mode' => 'abcd', 'reset' => true]
        )->assertOk();

        $response = $this->postJson(
            "/ujian/{$data['ujian']->id}/data-mentah/proses/batch",
            ['offset' => 0, 'limit' => 5, 'mode' => 'abcd']
        );

        $response->assertOk()
            ->assertJsonPath('status', 'running')
            ->assertJsonPath('total', 14)
            ->assertJsonPath('processed', 5)
            ->assertJsonPath('remaining', 9)
            ->assertJsonPath('progress', 35.71)
            ->assertJsonPath('next_offset', 5);

        $first = $data['peserta']->first()->fresh();
        $sixth = $data['peserta']->get(5)->fresh();

        $this->assertSame(10, $first->jumlah_benar);
        $this->assertSame('100.00', $first->nilai);
        $this->assertSame('tercapai', $first->keterangan);
        $this->assertSame(0, $sixth->jumlah_benar);
        $this->assertSame('dianalisis', $data['ujian']->status);
        $this->assertSame('kunci_lengkap', $data['ujian']->fresh()->status);
    }

    public function test_batch_completion_scores_all_students_and_keeps_absent_student_outcome(): void
    {
        $data = $this->makeBatchContext();
        $this->actingAs($data['admin'])->postJson(
            "/ujian/{$data['ujian']->id}/data-mentah/proses/start",
            ['mode' => 'abcd', 'reset' => true]
        )->assertOk();

        $this->postJson("/ujian/{$data['ujian']->id}/data-mentah/proses/batch", [
            'offset' => 0,
            'limit' => 5,
            'mode' => 'abcd',
        ])->assertOk()->assertJsonPath('status', 'running');

        $this->postJson("/ujian/{$data['ujian']->id}/data-mentah/proses/batch", [
            'offset' => 5,
            'limit' => 5,
            'mode' => 'abcd',
        ])->assertOk()->assertJsonPath('status', 'running');

        $response = $this->postJson("/ujian/{$data['ujian']->id}/data-mentah/proses/batch", [
            'offset' => 10,
            'limit' => 5,
            'mode' => 'abcd',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('processed', 14)
            ->assertJsonPath('remaining', 0)
            ->assertJsonPath('progress', 100)
            ->assertJsonPath('next_offset', 14);

        $absent = $data['peserta']->last()->fresh();
        $this->assertSame('tidak_hadir', $absent->status_kehadiran);
        $this->assertSame(0, $absent->jumlah_benar);
        $this->assertSame(10, $absent->jumlah_salah);
        $this->assertSame('0.00', $absent->nilai);
        $this->assertSame('tidak_hadir', $absent->keterangan);
        $this->assertSame('data_mentah', $data['ujian']->fresh()->status);

        $this->getJson("/ujian/{$data['ujian']->id}/data-mentah/proses/status")
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('processed', 14);
    }

    public function test_binary_mode_uses_existing_binary_scores_without_requiring_answers(): void
    {
        $data = $this->makeBatchContext();
        $first = $data['peserta']->first();

        JawabanSiswa::where('peserta_ujian_id', $first->id)->get()->each(function (JawabanSiswa $answer, int $index) {
            $answer->update([
                'jawaban' => null,
                'skor_biner' => $index < 7 ? 1 : 0,
                'is_benar' => $index < 7,
            ]);
        });

        $this->actingAs($data['admin'])->postJson(
            "/ujian/{$data['ujian']->id}/data-mentah/proses/start",
            ['mode' => 'biner', 'reset' => true]
        )->assertOk();

        $this->postJson("/ujian/{$data['ujian']->id}/data-mentah/proses/batch", [
            'offset' => 0,
            'limit' => 5,
            'mode' => 'biner',
        ])->assertOk();

        $first->refresh();
        $this->assertSame(7, $first->jumlah_benar);
        $this->assertSame(3, $first->jumlah_salah);
        $this->assertSame('70.00', $first->nilai);
        $this->assertSame('perlu_peningkatan', $first->keterangan);
    }

    public function test_legacy_process_endpoint_no_longer_runs_full_scoring_request(): void
    {
        $data = $this->makeBatchContext();

        $this->actingAs($data['admin'])
            ->post(route('data-mentah.proses', $data['ujian']), ['mode' => 'abcd'])
            ->assertRedirect(route('data-mentah.index', $data['ujian']))
            ->assertSessionHas('warning');

        $this->assertSame(0, $data['peserta']->first()->fresh()->jumlah_benar);
    }

    public function test_kepala_sekolah_cannot_start_or_run_t1_batches(): void
    {
        $data = $this->makeBatchContext();
        $kepala = $this->makeUser('Kepala Sekolah', 'kepsek_batch_' . uniqid());

        $this->actingAs($kepala)
            ->postJson("/ujian/{$data['ujian']->id}/data-mentah/proses/start", ['mode' => 'abcd'])
            ->assertForbidden();

        $this->postJson("/ujian/{$data['ujian']->id}/data-mentah/proses/batch", [
            'offset' => 0,
            'limit' => 5,
            'mode' => 'abcd',
        ])->assertForbidden();
    }

    public function test_data_mentah_page_is_paginated_and_exposes_batch_progress_contract(): void
    {
        $data = $this->makeBatchContext();

        $response = $this->actingAs($data['admin'])
            ->get(route('data-mentah.index', $data['ujian']));

        $response->assertOk()
            ->assertViewHas('peserta', function ($peserta) {
                return $peserta instanceof LengthAwarePaginator
                    && $peserta->count() === 10
                    && $peserta->total() === 14;
            })
            ->assertSee('/data-mentah/proses/start', false)
            ->assertSee('/data-mentah/proses/batch', false)
            ->assertSee('/data-mentah/proses/status', false)
            ->assertSee('t1BatchProgress', false);
    }

    private function makeBatchContext(): array
    {
        $admin = $this->makeUser('Admin', 'admin_batch_' . uniqid());
        $guruUser = $this->makeUser('Guru', 'guru_batch_' . uniqid());
        $guru = Guru::create([
            'user_id' => $guruUser->id,
            'nama_guru' => 'Guru Batch',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);
        $tahunAjaran = TahunAjaran::create([
            'tahun_ajaran' => '2096/2097',
            'semester' => 'Ganjil',
            'status' => 'aktif',
        ]);
        $kelas = Kelas::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_kelas' => 'TEST_QA_IX_A_' . substr(uniqid(), -5),
            'tingkat' => 'IX',
        ]);
        $mapel = Mapel::create([
            'kode_mapel' => 'B' . substr(uniqid(), -7),
            'nama_mapel' => 'TEST_QA_Matematika',
        ]);
        $ujian = Ujian::create([
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_ujian' => 'TEST_QA_UH_1_Matematika_IX_A_' . substr(uniqid(), -5),
            'jenis_ujian' => 'UH',
            'tanggal_ujian' => now()->toDateString(),
            'jumlah_soal' => 10,
            'kkm' => 75,
            'kktp' => 75,
            'metode_kelompok' => 'persen_50',
            'status' => 'dianalisis',
        ]);
        UjianKelas::create(['ujian_id' => $ujian->id, 'kelas_id' => $kelas->id]);

        $soal = collect();
        for ($number = 1; $number <= 10; $number++) {
            $soal->push(Soal::create([
                'ujian_id' => $ujian->id,
                'nomor_soal' => $number,
                'kunci_jawaban' => 'A',
                'bobot' => 1,
            ]));
        }

        $peserta = collect();
        for ($studentNumber = 1; $studentNumber <= 14; $studentNumber++) {
            $siswa = Siswa::create([
                'nis' => sprintf('TQ%03d%s', $studentNumber, substr(uniqid(), -3)),
                'nisn' => sprintf('99%08d', random_int(1, 99999999)),
                'nama_siswa' => sprintf('TEST QA Siswa %02d', $studentNumber),
                'jenis_kelamin' => $studentNumber % 2 === 0 ? 'P' : 'L',
                'status' => 'aktif',
            ]);
            $participant = PesertaUjian::create([
                'ujian_id' => $ujian->id,
                'siswa_id' => $siswa->id,
                'kelas_id' => $kelas->id,
                'status_kehadiran' => $studentNumber === 14 ? 'tidak_hadir' : 'hadir',
                'jumlah_benar' => 0,
                'jumlah_salah' => 0,
                'total_skor' => 0,
                'nilai' => 0,
                'ranking' => $studentNumber,
                'kelompok' => $studentNumber <= 7 ? 'atas' : 'bawah',
            ]);

            foreach ($soal as $questionIndex => $question) {
                $correctAnswers = max(0, 11 - $studentNumber);
                JawabanSiswa::create([
                    'peserta_ujian_id' => $participant->id,
                    'soal_id' => $question->id,
                    'jawaban' => $questionIndex < $correctAnswers ? 'A' : 'B',
                    'skor_biner' => 0,
                    'is_benar' => false,
                ]);
            }

            $peserta->push($participant);
        }

        AnalisisButir::create([
            'ujian_id' => $ujian->id,
            'soal_id' => $soal->first()->id,
            'nomor_soal' => 1,
            'ba' => 1,
            'bb' => 0,
            'ja' => 7,
            'jb' => 7,
            'n_analisis' => 14,
            'dp' => 0.143,
            'kategori_dp' => 'Buang',
            'tk' => 0.071,
            'kategori_tk' => 'Sukar',
            'keputusan' => 'Buang',
        ]);

        return compact('admin', 'guruUser', 'guru', 'tahunAjaran', 'kelas', 'mapel', 'ujian', 'soal', 'peserta');
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
}
