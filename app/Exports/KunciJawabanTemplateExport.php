<?php

namespace App\Exports;

use App\Models\Ujian;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KunciJawabanTemplateExport implements WithMultipleSheets
{
    public function __construct(private readonly Ujian $ujian)
    {
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
        $rows = [['nomor_soal', 'kunci_jawaban', 'bobot']];

        for ($i = 1; $i <= $this->ujian->jumlah_soal; $i++) {
            $rows[] = [$i, $withExample ? ['A', 'B', 'C', 'D', 'E'][($i - 1) % 5] : '', 1];
        }

        return $rows;
    }

    private function instructionRows(): array
    {
        return [
            ['PETUNJUK IMPORT KUNCI JAWABAN'],
            ['1', 'Gunakan sheet DATA_INPUT untuk mengisi kunci jawaban.'],
            ['2', 'Kolom nomor_soal diisi angka sesuai nomor soal pada ujian.'],
            ['3', 'Kolom kunci_jawaban hanya boleh A, B, C, D, atau E.'],
            ['4', 'Kolom bobot boleh dikosongkan; jika kosong sistem memakai bobot 1.'],
            ['5', 'Jangan mengubah nama header kolom karena sistem membaca header tersebut saat import.'],
        ];
    }
}
