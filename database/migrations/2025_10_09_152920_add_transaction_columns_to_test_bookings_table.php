<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('test_bookings', function (Blueprint $table) {
            $table->string('stripe_transaction_id')->nullable()->after('payment_status');
            $table->string('receipt_url')->nullable()->after('stripe_transaction_id');
        });
    }

    public function down()
    {
        Schema::table('test_bookings', function (Blueprint $table) {
            $table->dropColumn(['stripe_transaction_id', 'receipt_url']);
        });
    }
};
