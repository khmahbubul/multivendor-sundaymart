<?php

namespace App\Observers;

use App\Models\BonusShop;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\ShopProduct;

class CartDetailObserver
{
    public function updated(CartDetail $cartDetail)
    {
        $this->addBonusProduct($cartDetail);
//
//        $this->calculatePrice($cartDetail);

    }


    public function created(CartDetail $cartDetail)
    {
        $this->addBonusProduct($cartDetail);
//
//        $this->calculatePrice($cartDetail);

    }

    public function deleted(CartDetail $cartDetail)
    {
        $shop_product_id = $cartDetail->shop_product_id;

        $shop_product = ShopProduct::find($shop_product_id);

        $cartId = $cartDetail->userCart->cart->id;

        $cart = Cart::find($cartId);

        $cart->update(['total_price' => $cart->total_price - $cartDetail->price]);

        if ($shop_product->bonus) {
            $bonus_product_detail = CartDetail::where('shop_product_id', $shop_product->bonus->bonus_product_id)->first();
            if ($bonus_product_detail)
                $bonus_product_detail->delete();
        }
    }

    protected function addBonusProduct($cartDetail)
    {

        $shop_product_id = $cartDetail->shop_product_id;
        $shopProduct = ShopProduct::find($shop_product_id);
        if (isset($shopProduct->bonus) && ($shopProduct->bonus->shop_product_quantity <= $cartDetail->quantity)) {

            $quantity = floor($cartDetail->quantity / $shopProduct->bonus->shop_product_quantity) * $shopProduct->bonus->bonus_quantity;

            CartDetail::updateOrCreate([
                'shop_product_id' => $shopProduct->bonus->bonus_product_id,
                'user_cart_id'    => $cartDetail->user_cart_id,
                'bonus'           => true,
            ],[
                'quantity'        => $quantity,
                'price'           => 0
            ]);
        }elseif (isset($shopProduct->bonus) && ($shopProduct->bonus->shop_product_quantity > $cartDetail->quantity)){
            $cartDetail = CartDetail::where('shop_product_id',$shopProduct->bonus->bonus_product_id)->first();
            if ($cartDetail)
                $cartDetail->delete();
        }
    }

    protected function calculatePrice($cartDetail)
    {
        $cart_id = $cartDetail->userCart->cart->id;

        $cart = Cart::find($cart_id);

        $user_cart_ids = $cart->userCarts()->pluck('id')->toArray();

        $cart_detail_price = CartDetail::whereIn('user_cart_id', $user_cart_ids)->sum('price');

        $cart->update(['total_price' => $cart_detail_price]);
    }

    public function addShopBonusProduct($cartDetail)
    {
        $cartId = $cartDetail->userCart->cart->id;
        $cart = Cart::find($cartId);
        $shopId = $cartDetail->userCart->cart->shop_id;
        $bonusShop = BonusShop::active()->where('order_amount', '<=', $cart->total_price)->where('shop_id', $shopId)->orderBy('order_amount', 'desc')->first();
        if ($bonusShop) {
            CartDetail::updateOrCreate([
                'shop_product_id' => $bonusShop->bonus_product_id,
                'user_cart_id' => $cartDetail->user_cart_id,
                'quantity' => $bonusShop->bonus_quantity,
                'bonus' => true,
                'price' => 0
            ]);
        }
    }
}
