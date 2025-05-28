<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSchedule extends Model
{
    protected $fillable = [
        'price_configuration_id',  // Fix this to match your table's foreign key
        'deposit_type',
        'deposit_value',
        'security_deposit_value',
        'balance_due_days',
    ];

    public function priceConfiguration()
    {
        return $this->belongsTo(PriceConfiguration::class);
    }
}
