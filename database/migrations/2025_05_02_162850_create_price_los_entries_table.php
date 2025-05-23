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
        Schema::create('price_los_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_configuration_id');
            $table->date('date');
            $table->json('length_of_stay_prices'); // Store array as JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_los_entries');
    }
};
