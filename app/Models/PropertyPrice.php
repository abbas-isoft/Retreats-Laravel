<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'property_id',
        'last_updated',
        'listing_url',
    ];
}
