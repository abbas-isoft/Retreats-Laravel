<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingStep extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'image_url',
        'video_url',
        'description',
        'note',
    ];
}
