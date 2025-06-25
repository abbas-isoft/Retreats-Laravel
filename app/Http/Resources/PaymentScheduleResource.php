<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'due_days' => $this->due_days,
            'amount' => $this->amount,
        ];
    }
}
