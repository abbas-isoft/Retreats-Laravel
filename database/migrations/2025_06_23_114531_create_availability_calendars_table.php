<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailabilityCalendarsTable extends Migration
{
    public function up()
    {
        Schema::create('availability_calendars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('availability');
            $table->text('change_over'); 
            $table->text('max_stay');    
            $table->integer('min_prior_notify');
            $table->text('min_stay');    
            $table->timestamps();
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('availability_calendars');
    }
}

