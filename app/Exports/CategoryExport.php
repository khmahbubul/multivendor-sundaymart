<?php

namespace App\Exports;

use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategoryExport implements FromCollection, WithHeadings
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
        $model = Category::with([
        'translation',
        'children.translation'
        ])->get();
        return $model->map(function ($model){
            return $this->tableBody($model);
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'Category name',
            'Status',
        ];
    }

    private function tableBody($item): array
    {
        return [
            'id' => $item->id,
            'Category name' => $item->translation ? $item->translation->title : '',
            'Status' => $item->active ? 'active' : 'inactive',
        ];
    }
}
