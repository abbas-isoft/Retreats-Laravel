<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ListPropertyResource extends JsonResource
{
    public function toArray($request)
    {
        // Helper function for safe image URLs
        $getImageUrl = function ($image, $default = '') {
            if (!$image) {
                return $default ? url('images/' . $default) : '';
            }
            if (preg_match('/^https?:\/\//', $image)) {
                return $image; // Already full URL
            }
            return url('storage/images/' . ltrim($image, '/'));
        };

        // Section 1 = Hero Section
        $section1 = [
            'section1_subtitle' => $this->section1_subtitle,
            'content'           => $this->description,
            'button_title'      => $this->button_text,
            'image'             => $getImageUrl($this->hero_image, 'default-hero.jpg'),
        ];

        // Section 2 = Owner Say Section (testimonial)
        $ownerSay = $this->ownerSay ?? null;
        $section2 = [
            'title'                 => $ownerSay->title ?? '',
            'content'               => $ownerSay->description ?? '',
            'image'                 => $getImageUrl($ownerSay->image ?? null, 'default-owner.jpg'),
            'owner_name'            => $ownerSay->owner_name ?? '',
            'secondary_title'       => $ownerSay->secondary_title ?? '',
            'secondary_description' => $ownerSay->secondary_description ?? '',
        ];

        // Why Partner Boxes
        $whyBoxes = $this->whyPartnerBoxes ?? [];
        $whyBoxesFormatted = [];
        foreach ($whyBoxes as $box) {
            $whyBoxesFormatted[] = [
                'title'       => $box->box_title,
                'description' => $box->box_description,
            ];
        }

        return [
            'title'                           => $this->title,
            'subtitle'                        => $this->subtitle,
            'section1'                        => $section1,
            'section2'                        => $section2,
            'why_partner_with_group_retreats' => $whyBoxesFormatted,
        ];
    }
}
