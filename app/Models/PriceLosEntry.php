<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceLosEntry extends Model
{
    public function configuration()
    {
        return $this->belongsTo(PriceConfiguration::class);
    }

    protected $fillable = [
        'price_configuration_id',
        'date',
        'length_of_stay_prices',
    ];
}
