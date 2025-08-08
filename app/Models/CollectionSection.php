<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionSection extends Model
{
    protected $fillable = [
        'section_key',
        'title',
        'description',
    ];
}
