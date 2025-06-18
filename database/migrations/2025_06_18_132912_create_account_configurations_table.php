<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('account_configurations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('account_id')->unique();
        $table->json('card_payment_forms')->nullable();
        $table->string('cancellation_policy')->nullable();
        $table->timestamp('last_updated')->nullable();
        $table->string('merchant_name')->nullable();
        $table->integer('minimum_age')->nullable();
        $table->string('rental_agreement_url')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_configurations');
    }
};
