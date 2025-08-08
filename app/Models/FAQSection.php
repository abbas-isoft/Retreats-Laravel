<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FAQSection extends Model
{
    use HasFactory;

    // Explicitly specify the table name in case there's any mismatch
    protected $table = 'faq_sections';  // Ensure this matches the actual table name

    protected $fillable = ['question', 'answer'];
}

