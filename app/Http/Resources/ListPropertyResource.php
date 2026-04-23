<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ListPropertyResource extends JsonResource
{
    public function toArray($request)
    {
        $getImageUrl = function ($image, $default = '') {
            if (!$image) {
                return $default ? url('images/' . $default) : '';
            }
            if (preg_match('/^https?:\/\//', $image)) {
                return $image;
            }
            return url('storage/images/' . ltrim($image, '/'));
        };

        $section1 = [
            'section1_subtitle' => $this->section1_subtitle,
            'content'           => $this->description,
            'button_title'      => $this->button_text,
            'image'             => $getImageUrl($this->hero_image, 'default-hero.jpg'),
        ];

        $ownerSay = $this->ownerSay ?? null;
        $section2 = [
            'title'                 => $ownerSay->title ?? '',
            'content'               => $ownerSay->description ?? '',
            'image'                 => $getImageUrl($ownerSay->image ?? null, 'default-owner.jpg'),
            'owner_name'            => $ownerSay->owner_name ?? '',
            'secondary_title'       => $ownerSay->secondary_title ?? '',
            'secondary_description' => $ownerSay->secondary_description ?? '',
        ];

        $whyBoxes = $this->whyPartnerBoxes ?? [];
        $whyBoxesFormatted = [];
        $whyPartnerSectionTitle = '';

        if (count($whyBoxes) > 0) {
            $whyPartnerSectionTitle = $whyBoxes[0]->section_title ?? '';
        }

        foreach ($whyBoxes as $box) {
            $whyBoxesFormatted[] = [
                'title'       => $box->box_title,
                'description' => $box->box_description,
            ];
        }

        return [
            'title'    => $this->title,
            'subtitle' => $this->subtitle,
            'section1' => $section1,
            'section2' => $section2,
            'why_partner_section_title'       => $whyPartnerSectionTitle,
            'why_partner_with_group_retreats' => $whyBoxesFormatted,

            // === SEO Section as Nested Object ===
            'meta' => [
                'meta_title'       => $this->meta_title ?? '',
                'meta_description' => $this->meta_description ?? '',
                'meta_keywords'    => $this->meta_keywords ?? '',
                'og' => [
                    'title'       => $this->og_title ?? '',
                    'description' => $this->og_description ?? '',
                    'images'      => $this->og_images ?? [],
                    'url'         => $this->og_url ?? '',
                    'type'        => $this->og_type ?? 'website',
                    'site_name'   => $this->og_site_name ?? '',
                    'width'       => $this->og_image_width ?? 1200,
                    'height'      => $this->og_image_height ?? 630,
                ],
                'twitter' => [
                    'card'        => $this->twitter_card ?? 'summary_large_image',
                    'title'       => $this->twitter_title ?? '',
                    'description' => $this->twitter_description ?? '',
                    'image'       => $this->twitter_image ?? '',
                    'site'        => $this->twitter_site ?? '',
                ],
                'canonical_url' => $this->canonical_url ?? '',
                'schema_markup' => $this->schema_markup ?? [],
                'robots_txt'    => $this->robots_txt ?? true,
                'is_active'     => $this->is_active ?? true,
            ],
        ];
    }
}
