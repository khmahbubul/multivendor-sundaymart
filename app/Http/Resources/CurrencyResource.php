<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class CurrencyResource extends JsonResource
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
            'id'     => (int) $this->id,
            'symbol' => (string) $this->symbol,
            'title'  => (string) $this->title,
            "rate"   => $this->when($this->rate, (double) $this->rate),
            "default" => $this->when($this->default, (bool) $this->default),
            "active"  => $this->when($this->active, (bool) $this->active),
            "created_at" => $this->when($this->created_at, optional($this->created_at)->format('Y-m-d H:i:s')),
            "updated_at" => $this->when($this->updated_at, optional($this->updated_at)->format('Y-m-d H:i:s')),
        ];
    }
}
