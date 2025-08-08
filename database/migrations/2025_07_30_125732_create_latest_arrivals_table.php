<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('latest_arrivals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('subtitle');
            $table->string('image'); 
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('latest_arrivals');
    }
};
