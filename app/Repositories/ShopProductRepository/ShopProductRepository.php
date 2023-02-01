<?php

namespace App\Repositories\ShopProductRepository;


use App\Models\ShopProduct;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ShopProductRepository extends CoreRepository
{

    protected function getModelClass(): string
    {
        return ShopProduct::class;
    }

    public function getById(int $id, int $shop_id)
    {
        return $this->model()->with([
            'product.translation' => fn($q) => $q->actualTranslation($this->language),
            'product.galleries',
            'product.translations',
            'product',
            'product.category.translation',
            'product.brand',
            'product.unit.translations',
            'shop.translation' => fn($q) => $q->actualTranslation($this->language)
        ])->where('id', $id)->where('shop_id', $shop_id)->first();
    }

    public function paginate(int $shop_id, $array): LengthAwarePaginator
    {

        /** @var ShopProduct $shopProduct */
        $shopProduct = $this->model();

        $orderBy = $this->checkOrderBy($shopProduct, $array);


        return $shopProduct->with([
            'product.translation' => fn($q) => $q->actualTranslation($this->language)->select('id', 'product_id', 'locale', 'title'),
            'product.category' => fn($q) => $q->select('id', 'uuid'),
            'product.category.translation' => fn($q) => $q->where('locale', $this->language)
                ->select('id', 'category_id', 'locale', 'title'),
            'product.brand' => fn($q) => $q->select('id', 'uuid', 'title'),
            'product.unit.translation' => fn($q) => $q->where('locale', $this->language),
            'discount',
            'reviews',
            'shop'
        ])
            ->whereHas('product.translation', function ($q) use ($array){
                $q->actualTranslation($this->language)->select('id', 'product_id', 'locale', 'title');
            })
            ->when(isset($array['qr_code']), function ($q) use ($array) {
                $q->whereHas('product', function ($q) use ($array) {
                    $q->where('qr_code', 'LIKE', '%' . $array['qr_code'] . '%');
                });
            })
            ->when(isset($array['brand_id']), function ($q) use ($array) {
                $q->whereHas('product', function ($q) use ($array) {
                    $q->where('brand_id', $array['brand_id']);
                });
            })
            ->when(isset($array['category_id']), function ($q) use ($array) {
                $q->whereHas('product', function ($q) use ($array) {
                    $q->where('category_id', $array['category_id']);
                });
            })
            ->filter($array)
            ->where('shop_id', $shop_id)
            ->orderBy(data_get($orderBy, 'column'), data_get($orderBy, 'sort'))
            ->paginate(data_get($array, 'perPage', 10));
    }

    public function selectProducts(int $shop_id, $filter): LengthAwarePaginator
    {
        $perPage = data_get($filter, 'perPage', 10);

        /** @var ShopProduct $shopProduct */
        $shopProduct = $this->model();

        $orderBy = $this->checkOrderBy($shopProduct, $filter);

        return $shopProduct
            ->withProductTranslations(['lang' => $this->language])
            ->filter($filter)
            ->orderBy(data_get($orderBy, 'column'), data_get($orderBy, 'sort'))
            ->where('shop_id', $shop_id)
            ->select(['id', 'product_id'])
            ->paginate($perPage);
    }

    public function checkOrderBy(ShopProduct $shopProduct, $array): array
    {

        $modelAttributes = [
                'id',
                'created_at',
                'updated_at',
            ] + $shopProduct->getFillable();

        $column = data_get($array, 'column');
        $column = in_array($column, $modelAttributes) ? $column : 'id';

        $sort = data_get($array, 'sort');
        $sort = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';

        return [
            'column'    => $column,
            'sort'      => $sort,
        ];
    }

}
