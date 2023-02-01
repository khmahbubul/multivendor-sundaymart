<?php

namespace App\Exports;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    public function __construct()
    {
        $this->lang = request('lang') ?? null;
    }
    /**
    * @return Collection
    */
    public function collection()
    {
        $model = Product::with([
            'category.translation' => fn($q) => $q->where('locale', $this->lang),
            'unit.translation' => fn($q) => $q->where('locale', $this->lang),
            'translation' => fn($q) => $q->where('locale', $this->lang),
            'brand',
        ])->get();
        return $model->map(function ($model){
            return $this->productModel($model);
        });
    }

    public function headings(): array
    {
        return [
            'Product name',
            'Product description',
            'category_id',
            'brand_id',
            'unit_id',
            'Keywords',
            'Qr code',
        ];
    }

    private function productModel($item): array
    {
        return [
            'Product name' =>  $item->translation ? $item->translation->title : '',
            'Product description' =>  $item->translation ? $item->translation->description : '',
            'category_id' => $item->category_id,
            'brand_id' => $item->brand_id,
            'unit_id' => $item->unit_id,
            'Keywords' => $item->keywords,
            'Qr code' => $item->qr_code,
        ];
    }
}
