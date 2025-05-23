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
        Schema::create('property_listing', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('property_id')->unique(); // SuperControl propertyId
            $table->string('property_url')->nullable();
            $table->string('customer_property_id')->nullable();

            $table->json('card_payment_forms')->nullable(); // Will store array of cards
            $table->string('cancellation_policy')->nullable();

            $table->string('check_in_time')->nullable();
            $table->string('check_out_time')->nullable();
            $table->boolean('children_allowed')->default(false);
            $table->unsignedInteger('max_adults')->nullable();
            $table->unsignedInteger('max_guests')->nullable();
            $table->unsignedInteger('max_children')->nullable();
            $table->string('merchant_name')->nullable();
            $table->boolean('pets_allowed')->default(false);
            $table->boolean('smoking_allowed')->default(false);
            $table->boolean('allow_bookings')->default(true);
            $table->boolean('allow_enquiries')->default(true);

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
