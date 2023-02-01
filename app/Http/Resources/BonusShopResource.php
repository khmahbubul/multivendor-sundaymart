<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class BonusShopResource extends JsonResource
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
            'shop_id' => $this->shop_id,
            'bonus_product_id' => $this->bonus_product_id,
            'bonus_quantity' => $this->bonus_quantity,
            'order_amount' => $this->order_amount,
            'status' => (bool)$this->status,
            'expired_at' => Carbon::parse($this->expired_at)->format('Y-m-d'),

            'shop' => ShopResource::make($this->whenLoaded('shop')),
            'shop_product' => ShopProductResource::make($this->whenLoaded('shopProduct'))
        ];
    }
}
