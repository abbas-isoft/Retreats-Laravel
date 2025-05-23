<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceConfiguration extends Model
{
    public function fees()
    {
        return $this->hasMany(PriceFee::class);
    }

    public function losEntries()
    {
        return $this->hasMany(PriceLosEntry::class);
    }

    protected $fillable = [
        'property_id',
        'currency',
        'balance_due_days',
        'deposit_type',
        'deposit_value',
        'security_deposit',
    ];
}
