<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $fillable = [
        'single_configuration_id',
        'currency',
        'property_id',
    ];
}
