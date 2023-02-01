<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'price' => (double) $this->price,
            'currency_price' => (double) $this->currency_price,
            'rate' => (int) $this->rate,
            'tax' => round($this->tax,2),
            'commission_fee' => round($this->commission_fee,2),
            'status' => $this->status,
            'delivery_fee' => round($this->delivery_fee,2),
            'delivery_date' => $this->delivery_date,
            'delivery_time' => $this->delivery_time,
            'note' => $this->when(isset($this->note), (string) $this->note),
            'total_discount' => round($this->total_discount,2),
            'order_details_count' => $this->when($this->order_details_count, (int) $this->order_details_count),
            'created_at' => $this->when($this->created_at, optional($this->created_at)->format('Y-m-d H:i:s')),
            'updated_at' => $this->when($this->updated_at, optional($this->updated_at)->format('Y-m-d H:i:s')),

            'shop' => ShopResource::make($this->whenLoaded('shop')),
            'currency' => CurrencyResource::make($this->whenLoaded('currency')),
            'user' => UserResource::make($this->whenLoaded('user')),
            'details' => OrderDetailResource::collection($this->whenLoaded('orderDetails')),
            'transaction' => TransactionResource::make($this->whenLoaded('transaction')),
            'review' => ReviewResource::make($this->whenLoaded('review')),
            'delivery_address' => UserAddressResource::make($this->whenLoaded('deliveryAddress')),
            'deliveryman' => UserResource::make($this->whenLoaded('deliveryMan')),
            'delivery_type' => DeliveryResource::make($this->whenLoaded('deliveryType')),
            'coupon' => CouponResource::make($this->whenLoaded('coupon')),
            'bonus_shop' => BonusShopResource::make($this->whenLoaded('bonusShop'))
        ];
    }
}
