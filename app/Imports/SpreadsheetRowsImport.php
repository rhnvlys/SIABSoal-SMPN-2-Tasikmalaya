<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class SpreadsheetRowsImport implements ToArray, WithCalculatedFormulas
{
    public function array(array $array): array
    {
        return $array;
    }
}
