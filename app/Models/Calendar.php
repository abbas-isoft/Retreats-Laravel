<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $fillable = [
        'listing_id',
        'date',
        'price',
        'available',
        'min_stay',
        'reservation_id',
    ];
}