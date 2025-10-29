<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_arrivals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('featured_arrival_property', function (Blueprint $table) {
            $table->id();
            $table->foreignId('featured_arrival_id')->constrained('featured_arrivals')->onDelete('cascade');
            $table->string('property_id'); // matches property_contents.property_id type
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_arrival_property');
        Schema::dropIfExists('featured_arrivals');
    }
};
