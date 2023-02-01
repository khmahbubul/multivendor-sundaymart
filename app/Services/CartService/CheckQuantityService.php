<?php

namespace App\Services\CartService;


use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\ShopProduct;
use App\Models\UserCart;
use App\Services\CoreService;

class CheckQuantityService extends CoreService
{

    public function __construct()
    {
        parent::__construct();
    }

    protected function getModelClass()
    {
        return Cart::class;
    }

    public function checkQuantity($shop_id,$cart_id)
    {
        $cart_ids = $this->model->where('id',$cart_id)->where('shop_id', $shop_id)->pluck('id');
        $user_cart_ids = UserCart::whereIn('cart_id', $cart_ids)->pluck('id');
        $shop_product_ids = CartDetail::whereIn('user_cart_id', $user_cart_ids)->pluck('shop_product_id');
        $cart_details = CartDetail::whereIn('user_cart_id', $user_cart_ids)->get();
        $shop_products = ShopProduct::whereIn('id', $shop_product_ids)->get();
        $cart_details->map(function ($item) use ($shop_products) {
            foreach ($shop_products as $shop_product) {
                if (($item->shop_product_id == $shop_product->id) && ($item->quantity > $shop_product->quantity)) {
                    $item->update(['quantity' => $shop_product->quantity]);
                }
            }
        });
    }

}
