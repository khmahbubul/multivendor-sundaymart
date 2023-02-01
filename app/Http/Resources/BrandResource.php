<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class BrandResource extends JsonResource
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
            "id" => (int) $this->id,
            "uuid" => (string) $this->uuid,
            "title" => (string) $this->title,
            "active" => $this->when($this->active, (bool) $this->active),
            "img" => $this->when($this->img, $this->img),
            "products_count" => $this->when($this->products_count, $this->products_count),
            'created_at' => $this->when($this->created_at, optional($this->created_at)->format('Y-m-d H:i:s')),
            'updated_at' => $this->when($this->updated_at, optional($this->updated_at)->format('Y-m-d H:i:s')),
        ];
    }
}
