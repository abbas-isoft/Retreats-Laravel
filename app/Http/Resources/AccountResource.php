<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'accountId' => $this->accountId,
            'companyName' => $this->companyName,
            'contentIndexUrl' => $this->contentIndexUrl,
            'configurationIndexUrl' => $this->configurationIndexUrl,
            'pricesIndexUrl' => $this->pricesIndexUrl,
            'availabilityIndexUrl' => $this->availabilityIndexUrl,
        ];
    }
}
