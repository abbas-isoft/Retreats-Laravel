<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('test_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->date('start_date');
            $table->integer('nights');
            $table->string('guest_firstname');
            $table->string('guest_lastname');
            $table->string('email');
            $table->string('phone');
            $table->decimal('value', 10, 2);
            $table->string('booking_reference')->unique();
            $table->string('booking_id');
            $table->text('supercontrol_response')->nullable();

            // Extra Guest Details
            $table->string('address1')->nullable();
            $table->string('town')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->nullable();
            $table->unsignedInteger('adults')->default(0);
            $table->unsignedInteger('children')->default(0);
            $table->unsignedInteger('infants')->default(0);
            $table->text('special_requests')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_bookings');
    }
};

