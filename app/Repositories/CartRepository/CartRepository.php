<?php

namespace App\Repositories\CartRepository;


use App\Models\Cart;
use App\Repositories\CoreRepository;
use App\Services\CartService\CheckQuantityService;

class CartRepository extends CoreRepository
{
    private $lang;
    private CheckQuantityService $checkQuantityService;

    public function __construct(CheckQuantityService $checkQuantityService)
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
        $this->checkQuantityService = $checkQuantityService;
    }

    protected function getModelClass()
    {
        return Cart::class;
    }

    public function get($shop_id, $cart_id = null)
    {
        $user_id = auth('sanctum')->user()->id ?? null;
//        $this->checkQuantityService->checkQuantity($shop_id, $cart_id);
        $query = $this->model()->with([
            'userCarts.cartDetails.shopProduct.product.unit.translation' => fn($q) => $q->actualTranslation($this->lang),
            'userCarts.cartDetails.shopProduct.product',
            'userCarts.cartDetails.shopProduct.product.translation' => fn($q) => $q->actualTranslation($this->lang)
        ])->where('shop_id', $shop_id);
        if ($cart_id)
            return $query->find($cart_id);
        return $query->where('owner_id',$user_id)->first();
    }

    public function getById(int $id)
    {
        return $this->model()->find($id);
    }

}
