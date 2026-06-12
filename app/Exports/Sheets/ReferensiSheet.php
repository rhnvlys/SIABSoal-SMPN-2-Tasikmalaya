<?php

namespace App\Exports\Sheets;

use App\Models\Ujian;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReferensiSheet extends BaseSheet
{
    public function __construct(private readonly ?Ujian $ujian)
    {
        $this->title = 'REFERENSI';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setShowGridLines(false);
                $this->setTabColor($sheet, self::TAB_REFERENSI);

                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', '📚  REFERENSI DROPDOWN DAN KATEGORI');
                $this->applyHeaderStyle($sheet, 'A1:H1');
                $sheet->getRowDimension(1)->setRowHeight(32);

                $lists = [
                    'A' => ['role', 'Admin', 'Guru', 'Kepala Sekolah'],
                    'B' => ['jenis_penilaian', 'Ulangan Harian', 'Penilaian Harian', 'STS', 'SAS', 'PAS', 'PAT', 'Asesmen Sumatif', 'Latihan'],
                    'C' => ['status_kehadiran', 'hadir', 'tidak_hadir', 'izin', 'sakit', 'alfa'],
                    'D' => ['pilihan_jawaban', 'A', 'B', 'C', 'D', 'E'],
                    'E' => ['pilihan_skor', '0', '1'],
                    'F' => ['jenis_kelamin', 'L', 'P'],
                    'G' => ['kategori_dp', 'Baik', 'Revisi', 'Buang'],
                    'H' => ['kategori_tk', 'Mudah', 'Sedang', 'Sukar'],
                ];

                foreach ($lists as $column => $values) {
                    foreach ($values as $idx => $value) {
                        $row = $idx + 3;
                        $sheet->setCellValue("{$column}{$row}", $value);
                    }
                    $sheet->getStyle("{$column}3")->getFont()->setBold(true);
                    $sheet->getColumnDimension($column)->setWidth($column === 'B' ? 24 : 18);
                    $this->applyBorder($sheet, "{$column}3:{$column}" . (count($values) + 2));
                }

                $sheet->freezePane('A4');
                $sheet->getStyle('A3:H12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
