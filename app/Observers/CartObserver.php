<?php

namespace App\Observers;

use App\Models\BonusShop;
use App\Models\Cart;

class CartObserver
{

    public function created(Cart $cart)
    {


//        $cart_id = $cart->userCarts()->id;
//        $cart = Cart::find($cart_id);
//        $user_cart_ids = $cart->userCarts()->pluck('id')->toArray();
//        $cart_detail_price = CartDetail::whereIn('user_cart_id', $user_cart_ids)->sum('price');
//        $cart->update(['total_price' => $cart_detail_price]);
    }

}
