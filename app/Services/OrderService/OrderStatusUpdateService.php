<?php

namespace App\Services\OrderService;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ShopProduct;
use App\Services\CoreService;
use App\Services\WalletHistoryService\WalletHistoryService;

class OrderStatusUpdateService extends CoreService
{

    /**
     * @return mixed
     */
    protected function getModelClass()
    {
        return Order::class;
    }

    public function statusUpdate($order, $status)
    {
        if ($order->status == $status) {
            return ['status' => false, 'code' => ResponseError::ERROR_252];
        } elseif ($order->status == 'canceled') {
            return ['status' => false, 'code' => ResponseError::ERROR_254];
        }
        $order->update(['status' => $status]);
            // Top up Seller && Deliveryman Wallets when Order Status was delivered
            if ($status == 'delivered') {
                // SELLER TOP UP
                $this->sellerWalletTopUp($order);

                // DELIVERYMAN TOP UP
                if (isset($order->deliveryman)){
                    $this->deliverymanWalletTopUp($order);
                }
            }
            if ($status == 'canceled') {
                $user = $order->user;
                if ($order->orderDetails)
                {
                    $this->incrementQuantity($order->orderDetails);
                }
                if ($user->wallet && $order->transaction()->where('status','paid')->first()){
                    $user->wallet()->update(['price' => $user->wallet->price + $order->price]);
                }
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $order];
    }

    // Seller Order price topup function
    private function sellerWalletTopUp($order)
    {
        $seller = $order->shop->seller;
        if ($seller->wallet)
        {
        $seller->wallet()->update(['price' => $seller->wallet->price + ($order->price - $order->commission_fee)]);

        $request = request()->merge([
            'type' => 'topup',
            'price' => $order->price - $order->commission_fee,
            'note' => 'For Order #' . $order->id,
            'status' => 'paid',
        ]);
        return (new WalletHistoryService())->create($seller, $request);
        }
    }

    // Deliveryman  Order price topup function
    private function deliverymanWalletTopUp($order)
    {
        $deliveryman = $order->deliveryMan;
        if ($deliveryman->wallet)
        {
            $deliveryman->wallet()->update(['price' => $deliveryman->wallet->price + $order->delivery_fee]);
            $request = request()->merge([
                'type' => 'topup',
                'price' => $order->delivery_fee,
                'note' => 'For Order #' . $order->id,
                'status' => 'paid',
            ]);
            return (new WalletHistoryService())->create($deliveryman, $request);
        }

    }

    public function incrementQuantity($details)
    {
        $details->map(function ($model){
            ShopProduct::find($model->shop_product_id)->increment('quantity',$model->quantity);
        });
    }
}
