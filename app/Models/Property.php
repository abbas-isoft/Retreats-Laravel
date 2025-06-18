<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'account_id',
        'propertyId',
        'active',
        'lastUpdated',
        'listingUrl',
    ];

    public $timestamps = true;

    protected $guarded = [];

    // Relationship: property belongs to one account
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
