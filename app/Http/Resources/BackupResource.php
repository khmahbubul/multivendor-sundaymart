<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class BackupResource extends JsonResource
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
            'title' => (string) $this->title,
            'status' => (bool) $this->status,
            'path' => '/storage/laravel-backup/', // (string) $this->path,
            'created_at' => $this->when($this->created_at, optional($this->created_at)->format('Y-m-d H:i:s')),

            // Relations
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
