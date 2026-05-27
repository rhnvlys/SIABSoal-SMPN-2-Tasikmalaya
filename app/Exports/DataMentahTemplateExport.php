<?php

namespace App\Exports;

use App\Models\Ujian;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DataMentahTemplateExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly Ujian $ujian,
        private readonly string $mode
    ) {
    }

    public function headings(): array
    {
        $this->ujian->loadMissing('soal');
        $prefix = $this->mode === 'biner' ? 'skor_' : 'soal_';
        $headings = ['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'status_kehadiran'];

        foreach ($this->ujian->soal->sortBy('nomor_soal') as $soal) {
            $headings[] = $prefix . $soal->nomor_soal;
        }

        return $headings;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return $this->mode === 'biner' ? 'IMPORT_SKOR_01' : 'IMPORT_JAWABAN_ABCD';
    }
}
