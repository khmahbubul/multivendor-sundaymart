<?php

namespace App\Repositories\ShopBrandRepository;


use App\Models\ShopBrand;
use App\Repositories\CoreRepository;

class ShopBrandRepository extends CoreRepository
{
    private $lang;
    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
    }

    protected function getModelClass(): string
    {
        return ShopBrand::class;
    }

    /**
     * @param int $perPage
     * @param $active
     * @param array $array
     * @return mixed
     */
    public function paginate(int $perPage, $active = null, array $array)
    {
        return $this->model()->whereHas('brand')->with('brand')->when(isset($array['shop_id']), function ($q) use ($array){
            $q->where('shop_id', $array['shop_id']);
        })->paginate($perPage);
    }

    public function getById(int $brand_id)
    {
        return $this->model()->with('brand')->where('brand_id',$brand_id)->first();
    }

    public function show(int $id)
    {
        return $this->model()->with('brand')->find($id);
    }
}
