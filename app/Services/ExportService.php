<?php

namespace App\Services;

use App\Exports\ArrayReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportService
{
    public function downloadExcel(array $header, array $rows, string $filename, string $title = 'LAPORAN')
    {
        $safeName = str_replace(' ', '_', $filename);

        return Excel::download(new ArrayReportExport($header, $rows, $title), $safeName . '.xlsx');
    }
}
