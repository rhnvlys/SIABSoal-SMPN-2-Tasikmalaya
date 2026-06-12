<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

abstract class BaseSheet implements WithTitle, WithEvents
{
    protected string $title;

    // ─── Color Palette ─────────────────────────────────────────────────
    // Navy primary
    protected const CLR_NAVY         = '1B365D';
    protected const CLR_NAVY_DARK    = '142847';
    // Blue accents
    protected const CLR_BLUE_LIGHT   = 'E8F1F5';
    protected const CLR_BLUE_HEADER  = 'DDEBFA';
    protected const CLR_BLUE_SOFT    = 'F0F7FF';
    // Status colors
    protected const CLR_GREEN_BG     = 'E2F0D9';
    protected const CLR_GREEN_TEXT   = '2E7D32';
    protected const CLR_GREEN_DARK   = '1B5E20';
    protected const CLR_YELLOW_BG    = 'FFF8E1';
    protected const CLR_YELLOW_TEXT  = '7F6000';
    protected const CLR_RED_BG       = 'FFEBEE';
    protected const CLR_RED_TEXT     = 'C62828';
    protected const CLR_GRAY_BG      = 'F5F5F5';
    protected const CLR_GRAY_TEXT    = '757575';
    protected const CLR_GRAY_BORDER  = 'E0E0E0';
    // Input area
    protected const CLR_INPUT_BG     = 'FFFDE7';
    protected const CLR_INPUT_BORDER = 'FFD54F';
    // Text
    protected const CLR_TEXT_PRIMARY  = '212121';
    protected const CLR_TEXT_SECONDARY = '616161';
    // Zebra
    protected const CLR_ZEBRA_ODD    = 'FFFFFF';
    protected const CLR_ZEBRA_EVEN   = 'F8FBFE';
    // Tab colors for sheet grouping
    protected const TAB_BERANDA      = '1B365D';
    protected const TAB_IDENTITAS    = '2196F3';
    protected const TAB_DATA         = '4CAF50';
    protected const TAB_INPUT        = 'FF9800';
    protected const TAB_HASIL        = '9C27B0';
    protected const TAB_ANALISIS     = 'F44336';
    protected const TAB_NILAI        = '009688';
    protected const TAB_REKAP        = '3F51B5';
    protected const TAB_SYSTEM       = '9E9E9E';
    protected const TAB_REFERENSI    = 'BDBDBD';
    protected const TAB_PETUNJUK     = '00BCD4';

    public function title(): string
    {
        return substr($this->title, 0, 31);
    }

    // ─── Base Styles ───────────────────────────────────────────────────

    protected function getBaseStyles(): array
    {
        return [
            'font' => [
                'name' => 'Calibri',
                'size' => 11,
                'color' => ['rgb' => self::CLR_TEXT_PRIMARY],
            ]
        ];
    }

    // ─── Border Styles ─────────────────────────────────────────────────

    protected function applyBorder($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->applyFromArray([
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => self::CLR_GRAY_BORDER],
        ]);
    }

    protected function applyThickBorder($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getOutline()->applyFromArray([
            'borderStyle' => Border::BORDER_MEDIUM,
            'color' => ['rgb' => self::CLR_NAVY],
        ]);
    }

    // ─── Header Styles ─────────────────────────────────────────────────

    protected function applyHeaderStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
                'name' => 'Calibri',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::CLR_NAVY],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::CLR_NAVY_DARK],
                ],
            ],
        ]);
    }

    protected function applySubHeaderStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => self::CLR_NAVY],
                'size' => 11,
                'name' => 'Calibri',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::CLR_BLUE_LIGHT],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::CLR_GRAY_BORDER],
                ],
            ],
        ]);
    }

    // ─── Section Title Style ───────────────────────────────────────────

    protected function applySectionTitle($sheet, string $range, string $title): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => self::CLR_NAVY],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::CLR_BLUE_HEADER],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $this->applyThickBorder($sheet, $range);
    }

    // ─── Input Area Style ──────────────────────────────────────────────

    protected function applyInputStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => self::CLR_INPUT_BG],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => self::CLR_INPUT_BORDER],
                ],
            ],
        ]);
    }

    // ─── Alternating Row Colors (Zebra Striping) ───────────────────────

    protected function applyAlternatingRows($sheet, int $startRow, int $endRow, string $startCol, string $endCol): void
    {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $bgColor = ($row % 2 === 0) ? self::CLR_ZEBRA_EVEN : self::CLR_ZEBRA_ODD;
            $sheet->getStyle("{$startCol}{$row}:{$endCol}{$row}")->getFill()->applyFromArray([
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $bgColor],
            ]);
        }
    }

    // ─── Status Badge Styles ───────────────────────────────────────────

    protected function applyStatusBadge($sheet, string $cell, string $status): void
    {
        $styles = match (true) {
            str_contains(strtolower($status), 'tercapai'),
            str_contains(strtolower($status), 'tuntas'),
            str_contains(strtolower($status), 'baik'),
            str_contains(strtolower($status), 'terima'),
            str_contains(strtolower($status), 'pakai'),
            str_contains(strtolower($status), 'mudah') => [
                'bg' => self::CLR_GREEN_BG, 'text' => self::CLR_GREEN_TEXT
            ],
            str_contains(strtolower($status), 'sedang'),
            str_contains(strtolower($status), 'cukup'),
            str_contains(strtolower($status), 'revisi'),
            str_contains(strtolower($status), 'perbaiki'),
            str_contains(strtolower($status), 'peningkatan') => [
                'bg' => self::CLR_YELLOW_BG, 'text' => self::CLR_YELLOW_TEXT
            ],
            str_contains(strtolower($status), 'buang'),
            str_contains(strtolower($status), 'jelek'),
            str_contains(strtolower($status), 'sukar'),
            str_contains(strtolower($status), 'tidak'),
            str_contains(strtolower($status), 'gagal') => [
                'bg' => self::CLR_RED_BG, 'text' => self::CLR_RED_TEXT
            ],
            default => [
                'bg' => self::CLR_GRAY_BG, 'text' => self::CLR_GRAY_TEXT
            ],
        };

        $sheet->getStyle($cell)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $styles['text']]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $styles['bg']],
            ],
        ]);
    }

    // ─── Sheet Tab Color ───────────────────────────────────────────────

    protected function setTabColor($sheet, string $colorRGB): void
    {
        $sheet->getTabColor()->setRGB($colorRGB);
    }

    // ─── Print Setup ───────────────────────────────────────────────────

    protected function setPrintArea($sheet, string $range): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setPrintArea($range);
        $sheet->getPageMargins()
            ->setTop(0.5)
            ->setBottom(0.5)
            ->setLeft(0.4)
            ->setRight(0.4);
    }

    // ─── Metadata Label Row ────────────────────────────────────────────

    protected function applyMetaLabel($sheet, string $cell): void
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => self::CLR_NAVY],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8FAFC'],
            ],
        ]);
    }

    // ─── Data Validations ──────────────────────────────────────────────

    protected function addWholeNumberValidation($sheet, string $cellRange, int $min, int $max): void
    {
        $cellAddress = explode(':', $cellRange)[0];
        $validation = $sheet->getCell($cellAddress)->getDataValidation();
        $validation->setType(DataValidation::TYPE_WHOLE);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Input Tidak Valid');
        $validation->setError("Isi angka antara {$min} sampai {$max}.");
        $validation->setPromptTitle('Input Angka');
        $validation->setPrompt("Isi angka antara {$min} sampai {$max}.");
        $validation->setOperator(DataValidation::OPERATOR_BETWEEN);
        $validation->setFormula1((string) $min);
        $validation->setFormula2((string) $max);

        if (str_contains($cellRange, ':')) {
            $sheet->setDataValidation($cellRange, $validation);
        }
    }

    protected function addDropdown($sheet, string $cellRange, array $options): void
    {
        $cellAddress = explode(':', $cellRange)[0];
        $validation = $sheet->getCell($cellAddress)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Input Tidak Valid');
        $validation->setError('Pilih nilai dari daftar yang tersedia.');
        $validation->setPromptTitle('Pilih Nilai');
        $validation->setPrompt('Silakan pilih salah satu opsi dari dropdown.');
        $validation->setFormula1('"' . implode(',', $options) . '"');

        // Apply to range
        if (str_contains($cellRange, ':')) {
            $sheet->setDataValidation($cellRange, $validation);
        }
    }

    // ─── Number Format Helpers ─────────────────────────────────────────

    protected function applyPercentFormat($sheet, string $range): void
    {
        $sheet->getStyle($range)->getNumberFormat()->setFormatCode('0.00');
    }

    protected function applyScoreFormat($sheet, string $range): void
    {
        $sheet->getStyle($range)->getNumberFormat()->setFormatCode('0.0');
    }

    // ─── Watermark / Footer Info ───────────────────────────────────────

    protected function setSheetFooter($sheet, string $title): void
    {
        $sheet->getHeaderFooter()
            ->setOddHeader('&L&B' . $title . '&R&D &T')
            ->setOddFooter('&L&"Calibri,Regular"&8SIABSoal - SMPN 2 Tasikmalaya&C&P / &N&R&"Calibri,Regular"&8Dicetak: &D');
    }
}
