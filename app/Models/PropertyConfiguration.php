<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyConfiguration extends Model
{
    protected $fillable = [
        'property_id',
        'last_updated',
        'configurationContentUrl',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
