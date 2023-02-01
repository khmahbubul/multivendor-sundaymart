<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Language;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BrandImport implements ToCollection,WithHeadingRow,WithBatchInserts
{
    use Importable;

    /**
     * @param Collection $collection
     * @return mixed
     */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            Brand::updateOrCreate(['title' => $row['title']],[
                'title' => $row['title'],
            ]);
        }
        return true;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function batchSize(): int
    {
        return 500;
    }


}
