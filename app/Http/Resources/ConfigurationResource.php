<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfigurationResource extends JsonResource
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
            'property_id' => $this->property_id,
            'account_id' => $this->account_id,
            'card_code' => $this->card_code,
            'card_type' => $this->card_type,
            'cancellation_policy' => $this->cancellation_policy,
            'merchant_name' => $this->merchant_name,
            'minimum_age' => $this->minimum_age,
            'rental_agreement_url' => $this->rental_agreement_url,
            'last_updated' => $this->last_updated,
            'configurationContentUrl' => $this->configurationContentUrl,
        ];
    }
}
