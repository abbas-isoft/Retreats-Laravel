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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id')->unique();
            $table->string('property_url')->nullable();
            $table->string('customer_property_id')->nullable();

            $table->json('card_payment_forms')->nullable(); // array
            $table->string('cancellation_policy')->nullable();
            $table->string('check_in_time')->nullable();
            $table->string('check_out_time')->nullable();
            $table->boolean('children_allowed')->default(false);

            $table->integer('max_adults')->nullable();
            $table->integer('max_guests')->nullable();
            $table->integer('max_children')->nullable();

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
        Schema::dropIfExists('listings');
    }
};
