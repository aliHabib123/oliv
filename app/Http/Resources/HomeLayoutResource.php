<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeLayoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Note: URLs are pre-transformed when layouts are saved,
     * so we just return the data as-is for optimal performance.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!is_array($this->resource)) {
            return [];
        }

        return $this->resource;
    }
}
