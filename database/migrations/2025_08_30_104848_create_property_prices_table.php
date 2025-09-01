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
       Schema::create('property_detail_prices', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('property_id')->unique();
        $table->string('currency')->nullable();

        $table->json('fees')->nullable();       // fees array
        $table->json('price_los')->nullable();  // priceLos array

        $table->string('deposit_type')->nullable();
        $table->decimal('deposit_value', 10, 2)->nullable();
        $table->decimal('security_deposit', 10, 2)->nullable();
        $table->integer('balance_due_days')->nullable();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_prices');
    }
};
