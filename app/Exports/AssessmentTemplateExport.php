<?php

namespace App\Exports;

use App\Models\Ujian;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AssessmentTemplateExport implements WithMultipleSheets
{
    public const TYPES = [
        'data-siswa' => 'Template Data Siswa',
        'kunci-jawaban' => 'Template Kunci Jawaban',
        'jawaban-abcd' => 'Template Jawaban A/B/C/D/E',
        'skor-01' => 'Template Skor 0/1',
        'daftar-nilai' => 'Template Daftar Nilai',
        'rekap-nilai' => 'Template Rekap Nilai',
    ];

    public function __construct(
        private readonly string $type,
        private readonly ?Ujian $ujian = null
    ) {
    }

    public static function label(string $type): string
    {
        return self::TYPES[$type] ?? 'Template Excel';
    }

    public function sheets(): array
    {
        return [
            new StyledArraySheet('PETUNJUK', $this->instructionRows(), 1),
            new StyledArraySheet('DATA_INPUT', $this->dataInputRows(), 1),
            new StyledArraySheet('CONTOH', $this->exampleRows(), 1),
        ];
    }

    private function instructionRows(): array
    {
        $rows = [
            ['PETUNJUK ' . strtoupper(self::label($this->type))],
            ['1', 'Gunakan sheet DATA_INPUT untuk mengisi data yang akan diupload atau disalin ke sistem.'],
            ['2', 'Jangan ubah nama kolom pada baris header.'],
            ['3', 'Isi NIS sesuai data siswa yang sudah terdaftar di sistem.'],
            ['4', 'Jawaban hanya A, B, C, D, atau E.'],
            ['5', 'Skor hanya 0 atau 1.'],
            ['6', 'Status kehadiran diisi hadir atau tidak_hadir.'],
            ['7', 'Simpan file lalu upload kembali ke sistem sesuai menu import yang tersedia.'],
            ['8', 'Sheet CONTOH hanya panduan dan boleh dihapus sebelum upload.'],
        ];

        if ($this->ujian) {
            $rows[] = [];
            $rows[] = ['IDENTITAS UJIAN'];
            $rows[] = ['Nama Ujian', $this->ujian->nama_ujian];
            $rows[] = ['Jenis Penilaian', $this->ujian->jenis_penilaian_label];
            $rows[] = ['Mata Pelajaran', $this->ujian->mapel->nama_mapel ?? '-'];
            $rows[] = ['Kelas', $this->ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-'];
            $rows[] = ['KKTP/KKM', $this->ujian->kktp_value];
        }

        return $rows;
    }

    private function dataInputRows(): array
    {
        return match ($this->type) {
            'data-siswa' => [
                ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'kelas', 'tahun_ajaran', 'status'],
                ['', '', '', '', '', '', 'aktif'],
            ],
            'kunci-jawaban' => $this->kunciJawabanRows(false),
            'jawaban-abcd' => $this->jawabanRows('soal_', false),
            'skor-01' => $this->jawabanRows('skor_', false),
            'daftar-nilai' => [
                ['no', 'nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran', 'jumlah_benar', 'jumlah_salah', 'nilai', 'keterangan'],
                ['', '', '', '', '', 'hadir', '', '', '', ''],
            ],
            'rekap-nilai' => [
                ['komponen', 'nilai', 'catatan'],
                ['jumlah_siswa', '', ''],
                ['hadir', '', ''],
                ['tidak_hadir', '', ''],
                ['rata_rata', '', ''],
            ],
            default => [['kolom_1', 'kolom_2']],
        };
    }

    private function exampleRows(): array
    {
        return match ($this->type) {
            'data-siswa' => [
                ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'kelas', 'tahun_ajaran', 'status'],
                ['2025001', '3200000001', 'Contoh Siswa', 'L', 'VII A', '2025/2026', 'aktif'],
            ],
            'kunci-jawaban' => $this->kunciJawabanRows(true),
            'jawaban-abcd' => $this->jawabanRows('soal_', true),
            'skor-01' => $this->jawabanRows('skor_', true),
            'daftar-nilai' => [
                ['no', 'nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran', 'jumlah_benar', 'jumlah_salah', 'nilai', 'keterangan'],
                [1, '2025001', '3200000001', 'Contoh Siswa', 'L', 'hadir', 16, 4, 80, 'TERCAPAI'],
            ],
            'rekap-nilai' => [
                ['komponen', 'nilai', 'catatan'],
                ['jumlah_siswa', 32, 'Total peserta ujian'],
                ['hadir', 30, 'Siswa yang mengikuti ujian'],
                ['tidak_hadir', 2, 'Siswa tidak mengikuti ujian'],
                ['rata_rata', 78.50, 'Rata-rata nilai siswa hadir'],
            ],
            default => [['kolom_1', 'kolom_2'], ['contoh', 'contoh']],
        };
    }

    private function kunciJawabanRows(bool $withExample): array
    {
        $jumlahSoal = $this->ujian?->jumlah_soal ?? 10;
        $rows = [['nomor_soal', 'kunci_jawaban', 'bobot']];

        for ($i = 1; $i <= $jumlahSoal; $i++) {
            $rows[] = [$i, $withExample ? $this->exampleAnswer($i) : '', 1];
        }

        return $rows;
    }

    private function jawabanRows(string $prefix, bool $withExample): array
    {
        $jumlahSoal = $this->ujian?->jumlah_soal ?? 10;
        $headings = ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran'];

        for ($i = 1; $i <= $jumlahSoal; $i++) {
            $headings[] = $prefix . $i;
        }

        $rows = [$headings];

        if ($withExample) {
            $example = ['2025001', '3200000001', 'Contoh Siswa', 'L', 'hadir'];
            for ($i = 1; $i <= $jumlahSoal; $i++) {
                $example[] = $prefix === 'skor_' ? ($i % 3 === 0 ? 0 : 1) : $this->exampleAnswer($i);
            }
            $rows[] = $example;
        } else {
            $blank = ['', '', '', '', 'hadir'];
            for ($i = 1; $i <= $jumlahSoal; $i++) {
                $blank[] = '';
            }
            $rows[] = $blank;
        }

        return $rows;
    }

    private function exampleAnswer(int $number): string
    {
        return ['A', 'B', 'C', 'D', 'E'][($number - 1) % 5];
    }
}
