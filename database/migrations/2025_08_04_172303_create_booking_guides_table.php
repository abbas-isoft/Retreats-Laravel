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
         Schema::create('booking_guides', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('subtitle');
        $table->string('video_url')->nullable(); 
        // $table->string('image')->nullable();
        // $table->string('enquire_now_button')->nullable();
        $table->text('description')->nullable();
        $table->tinyInteger('is_video')->default(0);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_guides');
    }
};
