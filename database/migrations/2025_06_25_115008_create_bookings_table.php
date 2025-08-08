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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('property_id');
            $table->date('start_date');
            $table->integer('nights');
            $table->string('guest_firstname');
            $table->string('guest_lastname');
            $table->string('email');
            $table->string('phone');
            $table->decimal('value');
            $table->string('booking_reference')->nullable();
            $table->string('booking_id')->nullable(); // 👈 NEW COLUMN
            $table->text('supercontrol_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
