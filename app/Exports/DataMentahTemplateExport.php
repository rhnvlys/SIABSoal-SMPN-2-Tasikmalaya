<?php

namespace App\Exports;

use App\Models\Ujian;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DataMentahTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Ujian $ujian,
        private readonly string $mode
    ) {
    }

    public function sheets(): array
    {
        return [
            new StyledArraySheet('PETUNJUK', $this->instructionRows(), 1),
            new StyledArraySheet('DATA_INPUT', $this->templateRows(false), 1),
            new StyledArraySheet('CONTOH', $this->templateRows(true), 1),
        ];
    }

    private function templateRows(bool $withExample): array
    {
        $this->ujian->loadMissing('soal');
        $prefix = $this->mode === 'biner' ? 'skor_' : 'soal_';
        $headings = ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran'];

        foreach ($this->ujian->soal->sortBy('nomor_soal') as $soal) {
            $headings[] = $prefix . $soal->nomor_soal;
        }

        $row = $withExample
            ? ['2025001', '3200000001', 'Contoh Siswa', 'L', 'hadir']
            : ['', '', '', '', 'hadir'];
        foreach ($this->ujian->soal->sortBy('nomor_soal') as $soal) {
            $row[] = $withExample ? ($this->mode === 'biner' ? '1' : 'A') : '';
        }

        return [$headings, $row];
    }

    private function instructionRows(): array
    {
        $answerFormat = $this->mode === 'biner'
            ? 'Kolom skor_1, skor_2, dan seterusnya hanya boleh diisi 0 atau 1.'
            : 'Kolom soal_1, soal_2, dan seterusnya hanya boleh diisi A, B, C, D, E, atau dikosongkan.';

        return [
            ['PETUNJUK IMPORT DATA MENTAH T1'],
            ['1', 'Gunakan sheet DATA_INPUT untuk mengisi data.'],
            ['2', 'Kolom nis wajib sesuai NIS yang sudah ada di database.'],
            ['3', 'Kolom status_kehadiran diisi hadir atau tidak_hadir.'],
            ['4', $answerFormat],
            ['5', 'Sheet CONTOH berisi contoh dummy dan tidak perlu diupload.'],
            ['6', 'Jangan mengubah nama header kolom karena sistem membaca header tersebut saat validasi.'],
        ];
    }
}
