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
        Schema::create('property_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('card_code')->nullable();
            $table->string('card_type')->nullable();
            $table->string('cancellation_policy')->nullable();
            $table->string('merchant_name')->nullable();
            $table->integer('minimum_age')->nullable();
            $table->string('rental_agreement_url')->nullable();
            $table->dateTime('last_updated')->nullable();
            $table->string('configurationContentUrl')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_configurations');
    }
};
