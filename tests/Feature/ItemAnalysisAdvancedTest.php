<?php

namespace Tests\Feature;

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
use App\Services\ItemAnalysisService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ItemAnalysisAdvancedTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_calculates_dp_tk_categories_and_decisions_only(): void
    {
        $data = $this->makeAnalysisData();

        app(ItemAnalysisService::class)->prosesAnalisis($data['ujian']->id);

        $q1 = $data['soal'][0]->analisisButir()->firstOrFail();
        $q2 = $data['soal'][1]->analisisButir()->firstOrFail();

        $this->assertSame(0.5, (float) $q1->tk);
        $this->assertSame('Sedang', $q1->kategori_tk);
        $this->assertSame(1.0, (float) $q1->dp);
        $this->assertSame('Baik', $q1->kategori_dp);
        $this->assertSame('Dipakai', $q1->keputusan);
        $this->assertNull($q1->validitas);
        $this->assertNull($q1->reliabilitas);
        $this->assertNull($q1->distractor_status);

        $this->assertSame(-1.0, (float) $q2->dp);
        $this->assertSame('Buang', $q2->kategori_dp);
        $this->assertSame(0.5, (float) $q2->tk);
        $this->assertSame('Sedang', $q2->kategori_tk);
        $this->assertSame('Buang', $q2->keputusan);
        $this->assertNull($q2->validitas);
        $this->assertNull($q2->reliabilitas);
    }

    private function makeAnalysisData(): array
    {
        $role = Role::firstOrCreate(['nama_role' => 'Admin']);

        $guru = Guru::create([
            'nama_guru' => 'Guru Analisis',
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
            'nama_kelas' => 'IX Analisis',
            'tingkat' => 'IX',
        ]);

        $mapel = Mapel::create([
            'kode_mapel' => 'ANL' . substr(uniqid(), -5),
            'nama_mapel' => 'Mapel Analisis',
        ]);

        $ujian = Ujian::create([
            'guru_id' => $guru->id,
            'mapel_id' => $mapel->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama_ujian' => 'Ujian Analisis ' . uniqid(),
            'jenis_ujian' => 'UH',
            'tanggal_ujian' => now()->toDateString(),
            'jumlah_soal' => 2,
            'kkm' => 75,
            'metode_kelompok' => 'persen_50',
            'status' => 'olah_data',
        ]);

        UjianKelas::create(['ujian_id' => $ujian->id, 'kelas_id' => $kelas->id]);

        $soal = [
            Soal::create(['ujian_id' => $ujian->id, 'nomor_soal' => 1, 'kunci_jawaban' => 'A', 'bobot' => 1]),
            Soal::create(['ujian_id' => $ujian->id, 'nomor_soal' => 2, 'kunci_jawaban' => 'B', 'bobot' => 1]),
        ];

        $patterns = [
            ['nama' => 'Siswa 1', 'kelompok' => 'atas', 'jawaban' => ['A', 'C'], 'skor' => [1, 0], 'nilai' => 50],
            ['nama' => 'Siswa 2', 'kelompok' => 'atas', 'jawaban' => ['A', 'D'], 'skor' => [1, 0], 'nilai' => 50],
            ['nama' => 'Siswa 3', 'kelompok' => 'bawah', 'jawaban' => ['B', 'B'], 'skor' => [0, 1], 'nilai' => 50],
            ['nama' => 'Siswa 4', 'kelompok' => 'bawah', 'jawaban' => ['C', 'B'], 'skor' => [0, 1], 'nilai' => 50],
            ['nama' => 'Siswa 5', 'kelompok' => 'tengah', 'jawaban' => ['A', 'B'], 'skor' => [1, 1], 'nilai' => 100],
            ['nama' => 'Siswa 6', 'kelompok' => 'tengah', 'jawaban' => ['A', 'E'], 'skor' => [1, 0], 'nilai' => 50],
        ];

        foreach ($patterns as $index => $pattern) {
            $siswa = Siswa::create([
                'nis' => 'ANL' . $index . substr(uniqid(), -6),
                'nama_siswa' => $pattern['nama'],
                'jenis_kelamin' => $index % 2 === 0 ? 'L' : 'P',
                'status' => 'aktif',
            ]);

            $peserta = PesertaUjian::create([
                'ujian_id' => $ujian->id,
                'siswa_id' => $siswa->id,
                'kelas_id' => $kelas->id,
                'status_kehadiran' => 'hadir',
                'jumlah_benar' => array_sum($pattern['skor']),
                'jumlah_salah' => 2 - array_sum($pattern['skor']),
                'total_skor' => array_sum($pattern['skor']),
                'nilai' => $pattern['nilai'],
                'ranking' => $index + 1,
                'kelompok' => $pattern['kelompok'],
            ]);

            foreach ($soal as $soalIndex => $item) {
                JawabanSiswa::create([
                    'peserta_ujian_id' => $peserta->id,
                    'soal_id' => $item->id,
                    'jawaban' => $pattern['jawaban'][$soalIndex],
                    'skor_biner' => $pattern['skor'][$soalIndex],
                    'is_benar' => (bool) $pattern['skor'][$soalIndex],
                ]);
            }
        }

        return compact('role', 'guru', 'tahunAjaran', 'kelas', 'mapel', 'ujian', 'soal');
    }
}
