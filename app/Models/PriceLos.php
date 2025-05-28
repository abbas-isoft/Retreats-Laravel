<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceLos extends Model
{
    protected $fillable = [
        'single_configuration_id',
        'date',
        'data',
    ];
}
