<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Language;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CategoryImport implements ToCollection,WithHeadingRow,WithBatchInserts
{
    use Importable;

    /**
     * @param Collection $collection
     * @return mixed
     */
    public function collection(Collection $collection)
    {
        $language = Language::where('default', 1)->first();
        foreach ($collection as $row) {
            $category = Category::create([
                'keywords' => $row['keywords'],
            ]);

            $category->translation()->create([
                'locale' => $language->locale,
                'title' => $row['title'],
                'description' => $row['description'] ?? null
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
