<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'property_id',
        'review',
        'reviewer_name',
        'location',
        'title',
        'subtitle',
    ];
}
