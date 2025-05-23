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
        Schema::create('price_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_configuration_id');
            $table->string('name');
            $table->string('type');
            $table->decimal('price', 10, 2);
            $table->decimal('vat', 5, 2);
            $table->boolean('refundable');
            $table->boolean('hidden');
            $table->string('fee_payable_with')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_fees');
    }
};
