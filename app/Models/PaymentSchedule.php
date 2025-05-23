<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSchedule extends Model
{
    protected $fillable = [
        'single_configuration_id',
        'deposit_type',
        'deposit_value',
        'security_deposit_value',
        'balance_due_days',
    ];

    public function singleConfiguration()
    {
        return $this->belongsTo(SingleConfiguration::class);
    }
}
