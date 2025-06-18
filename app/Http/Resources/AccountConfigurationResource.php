<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountConfigurationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'card_payment_forms' => json_decode($this->card_payment_forms, true),
            'cancellation_policy' => $this->cancellation_policy,
            'last_updated' => $this->last_updated,
            'merchant_name' => $this->merchant_name,
            'minimum_age' => $this->minimum_age,
            'rental_agreement_url' => $this->rental_agreement_url,
        ];
    }
}
