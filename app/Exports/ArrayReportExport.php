<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ArrayReportExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $title = 'LAPORAN'
    ) {
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return substr($this->title, 0, 31);
    }
}
