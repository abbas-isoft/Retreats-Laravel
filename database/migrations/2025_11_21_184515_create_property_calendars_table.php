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
        Schema::create('property_calendars', function (Blueprint $table) {
    $table->id();
    $table->bigInteger('listing_id');
    $table->date('date');
    $table->boolean('is_available');
    $table->integer('price')->nullable();
    $table->string('status')->nullable();
    $table->timestamps();

    $table->unique(['listing_id', 'date']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_calendars');
    }
};
