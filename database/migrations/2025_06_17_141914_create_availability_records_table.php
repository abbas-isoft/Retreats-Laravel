<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('availability_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id')->unique();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('availability')->nullable();
            $table->string('change_over')->nullable();
            $table->integer('max_stay')->nullable();
            $table->integer('min_stay')->nullable();
            $table->integer('min_prior_notify')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('availability_records');
    }
};
