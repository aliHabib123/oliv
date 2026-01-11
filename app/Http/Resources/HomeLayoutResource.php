<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class HomeLayoutResource extends JsonResource
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

        // Transform block images to full URLs
        if (!empty($data['blocks']) && is_array($data['blocks'])) {
            foreach ($data['blocks'] as &$block) {
                // Handle different block types that might have images
                if (!empty($block['image'])) {
                    $block['image'] = url(Storage::url($block['image']));
                }
                if (!empty($block['background_image'])) {
                    $block['background_image'] = url(Storage::url($block['background_image']));
                }
                if (!empty($block['icon'])) {
                    $block['icon'] = url(Storage::url($block['icon']));
                }
            }
        }

        return $data;
    }
}
