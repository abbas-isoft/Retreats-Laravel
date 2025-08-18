<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListProperty extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'hero_image',
        'button_text',
    ];
}
