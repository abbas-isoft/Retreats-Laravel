<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyRoomsTable extends Migration
{
    public function up(): void
    {
        // Schema::create('property_rooms', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('property_content_id')->constrained()->onDelete('cascade');
        //     $table->string('name')->nullable();
        //     $table->timestamps();
        // });

        Schema::create('property_rooms', function (Blueprint $table) {
    $table->id();
    // make sure this matches the type of property_contents.id
    $table->unsignedBigInteger('property_content_id'); 
    $table->string('name')->nullable();
    $table->timestamps();

    // then add foreign key
    $table->foreign('property_content_id')->references('id')->on('property_contents')->onDelete('cascade');
});
    }

    public function down(): void
    {
        Schema::dropIfExists('property_rooms');
    }
}
