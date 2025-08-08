<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LatestArrival extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'description', 'image_url'];

    
    public function images()
    {
        return $this->hasMany(PropertyContentImage::class);
    }
}
