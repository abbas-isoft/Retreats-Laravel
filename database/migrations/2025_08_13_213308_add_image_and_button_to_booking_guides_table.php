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
    Schema::table('booking_guides', function (Blueprint $table) {
        $table->string('image')->nullable();
        $table->string('enquire_now_button')->nullable();
    });
}

public function down()
{
    Schema::table('booking_guides', function (Blueprint $table) {
        $table->dropColumn(['image', 'enquire_now_button']);
    });
}
};
