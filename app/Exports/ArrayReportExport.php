<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArrayReportExport implements FromArray, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $title = 'LAPORAN',
        private readonly array $metaRows = []
    ) {
    }

    public function array(): array
    {
        $content = [];

        foreach ($this->metaRows as $row) {
            $content[] = is_array($row) ? $row : [$row];
        }

        if (!empty($content)) {
            $content[] = [];
        }

        $content[] = $this->headings;

        return array_merge($content, $this->rows);
    }

    public function title(): string
    {
        return substr($this->title, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        $headerRow = $this->headerRow();

        if (!empty($this->metaRows)) {
            $sheet->getStyle('A1:A' . count($this->metaRows))->getFont()->setBold(true);
        }

        $sheet->getStyle("A{$headerRow}:{$sheet->getHighestColumn()}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1F2937']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E7F0FA'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRow = $this->headerRow();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->freezePane('A' . ($headerRow + 1));
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()
                    ->setRGB('D9E2EC');

                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
            },
        ];
    }

    private function headerRow(): int
    {
        return count($this->metaRows) + (empty($this->metaRows) ? 1 : 2);
    }
}
