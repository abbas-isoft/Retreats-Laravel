<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::create('why_partner_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('section_title')->nullable(); // "Why Partner with Group Retreats?"
            $table->string('box_title'); // e.g. "A True Understanding of the Market"
            $table->text('box_description')->nullable(); // description text
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('why_partner_boxes');
    }
};
