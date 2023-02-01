<?php

namespace App\Repositories\BannerRepository;

use App\Models\Banner;
use App\Repositories\CoreRepository;

class BannerRepository extends CoreRepository
{
    private $lang;
    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
    }

    protected function getModelClass()
    {
        return Banner::class;
    }

    public function bannersPaginate($perPage)
    {
        return $this->model()->with(['translation' => fn($q) => $q->actualTranslation($this->lang)])
            ->where('shop_id', null)
            ->paginate($perPage);
    }

    public function bannerDetails(int $id)
    {
        return $this->model()->with(['translation' => fn($q) => $q->actualTranslation($this->lang),'translations'])->find($id);
    }

    public function bannerPaginateSeller($perPage,$shop_id)
    {
        return $this->model()->with(['translation' => fn($q) => $q->actualTranslation($this->lang)])
            ->when(isset($shop_id),function ($q) use ($shop_id){
                $q->where('shop_id', $shop_id);
            })
            ->paginate($perPage);
    }

    public function bannersPaginateRest($perPage, $array)
    {
        return $this->model()->with(['translation' => fn($q) => $q->actualTranslation($this->lang)])
            ->when(isset($array['shop_id']),function ($q) use ($array){
                $q->where('shop_id', $array['shop_id']);
            })
            ->when(!isset($array['shop_id']),function ($q) use ($array){
                $q->where('shop_id', null);
            })
            ->where('active',true)
            ->paginate($perPage);
    }

}
