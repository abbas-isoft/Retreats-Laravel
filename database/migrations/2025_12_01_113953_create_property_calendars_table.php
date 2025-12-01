<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_calendars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id');   // Hostaway Listing ID
            $table->unsignedBigInteger('calendar_day_id')->nullable(); // Hostaway calendar ID (202537704)
            $table->date('date');
            $table->boolean('is_available')->default(1);
            $table->boolean('is_processed')->nullable();
            $table->string('status')->nullable();
            $table->integer('price')->nullable();
            $table->integer('minimum_stay')->nullable();
            $table->integer('maximum_stay')->nullable();
            $table->boolean('closed_on_arrival')->nullable();
            $table->boolean('closed_on_departure')->nullable();
            $table->text('note')->nullable();
            $table->integer('count_available_units')->nullable();
            $table->integer('available_units_to_sell')->nullable();
            $table->integer('count_pending_units')->nullable();
            $table->integer('count_blocking_reservations')->nullable();
            $table->integer('count_blocked_units')->nullable();
            $table->integer('count_reserved_units')->nullable();
            $table->integer('desired_units_to_sell')->nullable();
            $table->json('reservations')->nullable();

            $table->timestamps();

            $table->unique(['listing_id', 'date']); // Each listing + date unique
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_calendars');
    }
};
