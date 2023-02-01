<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        /** @var User $this */

        return [
            'id' => $this->id,
            'uuid' => $this->when($this->uuid, $this->uuid),
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->when($this->email, (string) $this->email),
            'phone' => $this->when($this->phone, (string) $this->phone),
            'birthday' => $this->when($this->birthday, optional($this->birthday)->format('Y-m-d H:i:s')),
            'gender' => $this->when($this->gender, $this->gender),
            'email_verified_at' => $this->when($this->email_verified_at, optional($this->email_verified_at)->format('Y-m-d H:i:s')),
            'phone_verified_at' => $this->when($this->phone_verified_at, optional($this->phone_verified_at)->format('Y-m-d H:i:s')),
            'registered_at' => $this->when($this->created_at, optional($this->created_at)->format('Y-m-d H:i:s')),
            'active' => $this->when(isset($this->active), (bool) $this->active),
            'img' => $this->when($this->img, $this->img),
            'role' => $this->when($this->role, $this->role),
            'orders_sum_price' => $this->when($this->orders_sum_price, round($this->orders_sum_price, 2)),

            'addresses' => UserAddressResource::collection($this->whenLoaded('addresses')),
            'shop' => ShopResource::make($this->whenLoaded('shop')),
            'wallet' => WalletResource::make($this->whenLoaded('wallet')),
            'point' => UserPointResource::make($this->whenLoaded('point')),
        ];
    }
}
