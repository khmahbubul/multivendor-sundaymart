<?php

namespace App\Repositories\BonusRepository;

use App\Models\BonusProduct;
use App\Repositories\CoreRepository;

class BonusProductRepository extends CoreRepository
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
        return BonusProduct::class;
    }

    public function paginate($perPage)
    {
        return $this->model()->with([
            'bonusProduct.product.translation' => fn($q) => $q->where('locale', $this->lang)
            ->select('id', 'product_id', 'locale', 'title')])
            ->orderByDesc('id')
            ->paginate($perPage);
    }


    /**
     * Get one brands by Identification number
     */
    public function show(int $id)
    {
        return $this->model()->with([
            'shopProduct.product.translation' => fn($q) => $q->where('locale', $this->lang)->select('id', 'product_id', 'locale', 'title'),
            'bonusProduct.product.translation' => fn($q) => $q->where('locale', $this->lang)->select('id', 'product_id', 'locale', 'title')
        ])->find($id);
    }

}
