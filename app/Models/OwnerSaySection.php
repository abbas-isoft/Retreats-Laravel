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
        'owner_title',
        'testimonial',
        'rating',
        'active',
    ];

    public function getImageAttribute($value)
    {
        return $value && !str_starts_with($value, 'http') ? env('ADMIN_URL') . '/' . $value : $value;
    }
}
