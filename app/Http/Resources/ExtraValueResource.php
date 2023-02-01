<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class ExtraValueResource extends JsonResource
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
            'extra_group_id' => (int) $this->extra_group_id,
            'value' => (string) $this->value,
            'active' => (boolean) $this->active,

            // Relations
            'group' => ExtraGroupResource::make($this->whenLoaded('group')),
            'galleries' => GalleryResource::collection($this->whenLoaded('galleries')),
        ];
    }
}
