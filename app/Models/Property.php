<?php

// app/Models/Property.php

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
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    protected $guarded = [];

    public function account()
{
    return $this->belongsTo(Account::class, 'account_id', 'id');
}

}
