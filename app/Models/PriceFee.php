<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceFee extends Model
{
    public function configuration()
    {
        return $this->belongsTo(PriceConfiguration::class);
    }

    protected $fillable = [
        'price_configuration_id',
        'name',
        'type',
        'price',
        'vat',
        'refundable',
        'hidden',
        'fee_payable_with',
    ];
}
