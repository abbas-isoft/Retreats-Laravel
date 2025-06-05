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
        Schema::create('content_listings', function (Blueprint $table) {
            $table->id();
            $table->integer('property_id');
            $table->string('property_url')->nullable();
            $table->string('customer_property_id');
            $table->json('accepted_payment_forms');
            $table->string('cancellation_policy');
            $table->time('check_in_time');
            $table->time('check_out_time');
            $table->boolean('children_allowed');
            $table->integer('maximum_adults');
            $table->integer('maximum_guests');
            $table->integer('maximum_children');
            $table->string('merchant_name');
            $table->boolean('pets_allowed');
            $table->boolean('smoking_allowed');
            $table->boolean('allow_bookings');
            $table->boolean('allow_enquiries');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_listings');
    }
};
