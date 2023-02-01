<?php

namespace App\Repositories\BonusRepository;

use App\Models\BonusShop;
use App\Repositories\CoreRepository;

class BonusShopRepository extends CoreRepository
{

    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();

    }

    /**
     * @return mixed
     */
    protected function getModelClass()
    {
        return BonusShop::class;
    }

    public function paginate(int $perPage)
    {
        return $this->model()->with(['shopProduct.product.translation' => fn($q) => $q->where('locale', $this->lang)
            ->select('id', 'product_id', 'locale', 'title')])->orderByDesc('id')->paginate($perPage);
    }


    /**
     * Get one brands by Identification number
     */
    public function show(int $id)
    {
        return $this->model()->with(['shopProduct.product.translation' => fn($q) => $q->where('locale', $this->lang)
            ->select('id', 'product_id', 'locale', 'title')])->find($id);
    }

}
