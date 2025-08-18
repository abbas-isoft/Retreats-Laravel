<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerSaySection extends Model
{
    protected $fillable = [
        'title',
        'description',
        'owner_name',
        'secondary_title',
        'secondary_description',
        'image',
    ];
}
