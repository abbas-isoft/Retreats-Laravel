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
         Schema::create('property_content_amenity', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('property_content_id');
        $table->unsignedBigInteger('amenity_id');
        $table->timestamps();

        $table->foreign('property_content_id')
              ->references('id')->on('property_contents')
              ->onDelete('cascade');

        $table->foreign('amenity_id')
              ->references('id')->on('amenities')
              ->onDelete('cascade');

        $table->unique(['property_content_id', 'amenity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_content_amenity');
    }
};
