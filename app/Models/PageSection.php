<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSection extends Model
{
    protected $fillable = ['section_name', 'data'];

    protected $casts = [
        'data' => 'array', 
    ];
}
