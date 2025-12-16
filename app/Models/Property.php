<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'account_id',
        'active',
        'lastUpdated',
        'listingUrl',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
     public function propertyContents()
    {
        return $this->hasMany(PropertyContent::class, 'property_id', 'id');
    }

    // 🔹 NEW: Property can belong to many collections
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_property');
    }

    public function images()
{
    return $this->hasMany(PropertyContentImage::class, 'property_content_id', 'id');
}
public function contents()
{
    return $this->hasMany(PropertyContent::class, 'property_id');
}
// Relation to regions
    public function regions()
    {
        return $this->belongsToMany(Region::class, 'region_property', 'property_id', 'region_id');
    }
}
