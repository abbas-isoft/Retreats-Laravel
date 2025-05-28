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
        Schema::create('price_los', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->json('price_data'); // Store historical data as JSON
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('single_configurations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_los');
    }
};
