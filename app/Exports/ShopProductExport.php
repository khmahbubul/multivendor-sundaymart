<?php

namespace App\Exports;

use App\Models\ShopProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShopProductExport implements FromCollection,WithHeadings
{
    private $shop;

    public function __construct($shop)
    {
        $this->lang = request('lang') ?? null;
        $this->shop = $shop;
    }
    /**
    * @return Collection
    */
    public function collection()
    {
        $model = ShopProduct::where('shop_id', $this->shop->id)->get();

        return $model->map(function ($model){
            return $this->productModel($model);
        });
    }


    public function headings(): array
    {
        return [
            '#',
            'Quantity',
            'Price',
            'product_id',
        ];
    }

    private function productModel($item): array
    {
        return [
            '#' => $item->id,
            'Quantity' => $item->quantity,
            'Price' => $item->price,
            'product_id' => $item->product_id,
        ];
    }
}
