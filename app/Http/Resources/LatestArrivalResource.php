<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LatestArrivalResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // // 'id' => $this->id,
            // 'title' => $this->title 
            //             ?? $this->name 
            //             ?? $this->heading 
            //             ?? $this->property_name 
            //             ?? 'No Title',
            // 'summary' => strip_tags($this->summary ?? $this->description ?? ''),
            // 'images' => $this->images->map(fn($img) => [
            //     'id' => $img->id,
            //     'url' => $img->url,
            // ]),
            // 'amenities' => $this->amenities,
            // // 'created_at' => $this->created_at,


             'id' => $this->id,
        'title' => $this->title 
                        ?? $this->name 
                        ?? $this->heading 
                        ?? $this->property_name 
                        ?? 'No Title',
        'description' => $this->description,
        'amenities' => $this->amenities,
        'images' => PropertyContentImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
