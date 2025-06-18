<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'id',
        'companyName',
        'contentIndexUrl',
        'configurationIndexUrl',
        'pricesIndexUrl',
        'availabilityIndexUrl',
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
