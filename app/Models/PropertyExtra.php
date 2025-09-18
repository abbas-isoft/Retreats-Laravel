<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyExtra extends Model
{
    protected $fillable = [
        'property_id',
        'category',
        'extra_id',
        'name',
        'description',
        'img',
        'price_rate',
        'price_unit',
        'price_unit_label',
        'price_total',
        'currency_symbol',
        'is_default',
        'is_hidden',
        'is_included',
        'qty',
        'available',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_hidden' => 'boolean',
        'is_included' => 'boolean',
        'available' => 'array',
    ];
}
