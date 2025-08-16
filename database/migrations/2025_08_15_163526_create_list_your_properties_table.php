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
        Schema::create('list_your_properties', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('subtitle');
    $table->string('image')->nullable(); // You can use file upload or URL
    $table->string('button_text');
    $table->string('button_link');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_your_properties');
    }
};
