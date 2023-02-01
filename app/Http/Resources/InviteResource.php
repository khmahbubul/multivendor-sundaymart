<?php

namespace App\Http\Resources;

use App\Models\Invitation;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class InviteResource extends JsonResource
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
            'id' => (int) $this->id,
            'shop_id' => (int) $this->shop_id,
            'user_id' => (int) $this->user_id,
            'role' => (string) $this->role,
            'status' => (string) Invitation::getStatusKey($this->status),
            'created_at' => $this->when($this->created_at, optional($this->created_at)->format('Y-m-d H:i:s')),
            'updated_at' => $this->when($this->updated_at, optional($this->updated_at)->format('Y-m-d H:i:s')),

            'user' => UserResource::make($this->whenLoaded('user')),
            'shop' => ShopResource::make($this->whenLoaded('shop')),

        ];
    }
}
