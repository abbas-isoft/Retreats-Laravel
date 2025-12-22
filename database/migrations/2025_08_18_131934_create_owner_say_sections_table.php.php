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
        Schema::create('owner_say_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('owner_name')->nullable();
            $table->string('secondary_title')->nullable();
            $table->text('secondary_description')->nullable();
            $table->string('image')->nullable(); // store image path
            $table->string('owner_title')->nullable();      // VARCHAR(255) NULL
            $table->longText('testimonial')->nullable();   // LONGTEXT NULL
            $table->integer('rating')->default(5);          // INT default 5
            $table->tinyInteger('active')->default(1);   
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_say_sections');
    }
};
