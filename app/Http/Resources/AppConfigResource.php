<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AppConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!is_array($this->resource)) {
            return [];
        }

        $data = $this->resource;

        // Transform logo to full URL
        if (!empty($data['basic']['logo'])) {
            $data['basic']['logo'] = url(Storage::url($data['basic']['logo']));
        }

        return $data;
    }
}
