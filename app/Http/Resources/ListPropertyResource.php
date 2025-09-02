<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ListPropertyResource extends JsonResource
{
    public function toArray($request)
    {
        $image = $this->hero_image;
        if ($image && !str_starts_with($image, 'http')) {
            $image = url('images/' . $image);
        }

        // Section 1 = Hero Section
        $section1 = [
            'title'        => $this->title,
            'content'      => $this->description,
            'button_title' => $this->button_text,
            'image'        => $image,
        ];

        // Section 2 = Owner Say Section (testimonial)
        $ownerSay = $this->ownerSay ?? null;
       $section2 = [
      'title'   => $ownerSay->title ?? '',
      'content' => $ownerSay->description ?? '',
      'image'   => $ownerSay->image ? url('images/' . $ownerSay->image) : '',
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
            'title'   => $this->title,
            'subtitle'=> $this->subtitle,
            'section1'=> $section1,
            'section2'=> $section2,
            'why_partner_with_group_retreats' => $whyBoxesFormatted,
        ];
    }
}
