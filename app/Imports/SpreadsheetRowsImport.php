<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class SpreadsheetRowsImport implements ToArray
{
    public function array(array $array): array
    {
        return $array;
    }
}
