<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'accountId',
        'companyName',
        'contentIndexUrl',
        'configurationIndexUrl',
        'pricesIndexUrl',
        'availabilityIndexUrl',
    ];

public function properties()
{
    return $this->hasMany(Property::class, 'account_id', 'id');
}

}
