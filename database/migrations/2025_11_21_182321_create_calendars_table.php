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
    Schema::create('calendars', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('listing_id');  // hostaway listing ID
        $table->date('date');
        $table->decimal('price', 10, 2)->default(0);
        $table->boolean('available')->default(true);
        $table->integer('min_stay')->default(1);
        $table->unsignedBigInteger('reservation_id')->nullable(); // if booked
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendars');
    }
};
