<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyRoomBedsTable extends Migration
{
    public function up(): void
    {
        Schema::create('property_room_beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_room_id')->constrained()->onDelete('cascade');
            $table->string('bed_type');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_room_beds');
    }
}
