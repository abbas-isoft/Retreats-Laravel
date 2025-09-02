<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\PriceFeeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceConfigurationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'property_id' => $this->property_id,
            'currency' => $this->currency,
            'balance_due_days' => $this->balance_due_days,
            'deposit_type' => $this->deposit_type,
            'deposit_value' => $this->deposit_value,
            'security_deposit' => $this->security_deposit,
            'fees' => PriceFeeResource::collection($this->whenLoaded('fees')),
            'los_entries' => PriceLosEntryResource::collection($this->whenLoaded('losEntries')),
            // 'payment_schedules' => PaymentScheduleResource::collection($this->whenLoaded('paymentSchedules')),
            // 'price_los_entries' => PriceLosEntryResource::collection($this->whenLoaded('losEntries')),
        ];
    }
}
