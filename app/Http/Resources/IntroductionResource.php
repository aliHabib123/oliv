<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class IntroductionResource extends JsonResource
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

        // Transform banner image to full URL
        if (!empty($data['banner_image'])) {
            $data['banner_image'] = url(Storage::url($data['banner_image']));
        }

        // Transform feature section icons to full URLs
        if (!empty($data['feature_sections']) && is_array($data['feature_sections'])) {
            foreach ($data['feature_sections'] as &$section) {
                if (!empty($section['items']) && is_array($section['items'])) {
                    foreach ($section['items'] as &$item) {
                        if (!empty($item['icon'])) {
                            $item['icon'] = url(Storage::url($item['icon']));
                        }
                    }
                }
            }
        }

        return $data;
    }
}
