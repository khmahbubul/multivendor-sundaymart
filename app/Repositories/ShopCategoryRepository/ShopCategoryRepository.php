<?php

namespace App\Repositories\ShopCategoryRepository;


use App\Models\ShopCategory;
use App\Repositories\CoreRepository;

class ShopCategoryRepository extends CoreRepository
{
    private $lang;
    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
    }

    protected function getModelClass()
    {
        return ShopCategory::class;
    }


    public function getById(int $category_id,int $shop_id)
    {
        return $this->model()->where('category_id',$category_id)->where('shop_id',$shop_id)->first();
    }
}
