<?php

namespace App\Repositories\ShopPaymentRepository;

use App\Models\ShopPayment;
use App\Repositories\CoreRepository;

class ShopPaymentRepository extends CoreRepository
{
    private $lang;

    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
    }

    protected function getModelClass()
    {
        return ShopPayment::class;
    }

    // get list product
    public function list($array)
    {
        return $this->model()->filter($array)->get();
    }

    public function paginate($perPage, $shop = null)
    {
        return $this->model()->with(['payment.translation' => fn($q) => $q->actualTranslation($this->lang)])
            ->when(isset($shop), function ($q) use ($shop) {
                $q->where('shop_id', $shop);
            })
            ->paginate($perPage);
    }


    public function getById(int $id, $shop = null)
    {
        return $this->model()->with(['payment.translation' => fn($q) => $q->actualTranslation($this->lang),'payment.translations'])
            ->when(isset($shop), function ($q) use ($shop) {
                $q->where('shop_id', $shop);
            })->find($id);
    }
}
