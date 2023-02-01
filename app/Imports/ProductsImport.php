<?php

namespace App\Imports;

use App\Helpers\ResponseError;
use App\Models\Language;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProductsImport implements ToCollection, WithHeadingRow, WithBatchInserts
{
    use Importable, ApiResponse;

    /**
     * @param Collection $collection
     * @return mixed
     */
    public function collection(Collection $collection)
    {
        $language = Language::where('default', 1)->first();
            foreach ($collection as $row) {
                $product = Product::updateOrCreate(['qr_code' => $row['qr_code']],[
                    'category_id' => $row['category_id'],
                    'brand_id' => $row['brand_id'],
                    'unit_id' => $row['unit_id'],
                    'keywords' => $row['keywords'],
                    'qr_code' => $row['qr_code'],
                ]);

                $product->translation()->delete();

                $product->translation()->create([
                    'locale' => $language->locale,
                    'title' => $row['product_name'],
                    'description' => $row['product_description'] ?? null
                ]);

            }
            return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer'],
            'brand_id' => ['required', 'integer'],
            'unit_id' => ['required', 'integer'],
            'keywords' => ['required', 'string', 'max:255'],
            'qr_code' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
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
