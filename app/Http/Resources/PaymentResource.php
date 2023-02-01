<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'tag' => (string) $this->tag,
            'input' =>  $this->when($this->input, (int) $this->input),
            'client_id' => $this->when($this->client_id,(string) $this->client_id),
            'secret_id' => $this->when($this->secret_id, (string) $this->secret_id),
            'sandbox' => $this->when($this->sandbox, (boolean) $this->sandbox),
            'active' => (boolean) $this->active,
            'created_at' => $this->when($this->created_at, optional($this->created_at)->format('Y-m-d H:i:s')),
            'updated_at' => $this->when($this->updated_at, optional($this->updated_at)->format('Y-m-d H:i:s')),

            // Relations
            'translation' => TranslationResource::make($this->whenLoaded('translation')),
            'translations' => TranslationResource::collection($this->whenLoaded('translations')),
        ];
    }
}
