<?php

namespace App\Services;

use App\Exports\ArrayReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportService
{
    public function downloadExcel(
        array $header,
        array $rows,
        string $filename,
        string $title = 'LAPORAN',
        array $metaRows = []
    ) {
        $safeName = str_replace(' ', '_', $filename);

        return Excel::download(new ArrayReportExport($header, $rows, $title, $metaRows), $safeName . '.xlsx');
    }
}
