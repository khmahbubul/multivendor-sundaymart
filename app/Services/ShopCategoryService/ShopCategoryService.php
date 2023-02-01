<?php

namespace App\Services\ShopCategoryService;

use App\Helpers\ResponseError;
use App\Models\ShopCategory;
use App\Services\CoreService;

class ShopCategoryService extends CoreService
{

    protected function getModelClass(): string
    {
        return ShopCategory::class;
    }

    public function create($collection): array
    {
        $this->setParams($collection);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => []];
    }

    public function update($collection, $shop): array
    {
        $shopCategory = $shop->categories();
        $shopCategory->detach();

        $this->setParams($collection);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => []];

    }

    public function setParams($collection)
    {
        foreach (data_get($collection, 'categories', []) as $category_id) {
            $this->model()->create([
                'shop_id'       => data_get($collection, 'shop_id'),
                'category_id'   => $category_id
            ]);
        }
    }


}
