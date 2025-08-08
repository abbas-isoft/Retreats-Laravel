<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetreatSearch extends Model
{
    protected $fillable = [ 'city', 'check_in', 'check_out', 'guests'];
}