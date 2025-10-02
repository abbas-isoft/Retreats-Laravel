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
    Schema::table('test_bookings', function (Blueprint $table) {
        $table->string('deposit_status')
              ->default('pending')
              ->after('damage_deposit_intent_id');
    });
}

public function down()
{
    Schema::table('test_bookings', function (Blueprint $table) {
        $table->dropColumn('deposit_status');
    });
}
};
