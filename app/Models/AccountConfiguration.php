<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'card_payment_forms', 
        'cancellation_policy',
        'last_updated',
        'merchant_name',
        'minimum_age',
        'rental_agreement_url',
    ];

    protected $casts = [
        'card_payment_forms' => 'array',
        'last_updated' => 'datetime',
    ];

    public function account()
{
    return $this->belongsTo(Account::class);
}
}
