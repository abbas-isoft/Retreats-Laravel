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
        Schema::table('test_bookings', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('supercontrol_response');
            $table->string('stripe_payment_method_id')->nullable()->after('stripe_customer_id');
            $table->string('upfront_payment_intent_id')->nullable()->after('stripe_payment_method_id');
            $table->string('remaining_payment_intent_id')->nullable()->after('upfront_payment_intent_id');
            $table->string('damage_deposit_intent_id')->nullable()->after('remaining_payment_intent_id');

            $table->integer('amount_cents')->default(0)->after('value'); // total
            $table->integer('upfront_cents')->default(0)->after('amount_cents');
            $table->integer('remaining_cents')->default(0)->after('upfront_cents');
            $table->integer('damage_deposit_cents')->default(0)->after('remaining_cents');

            $table->timestamp('remaining_payment_scheduled_at')->nullable()->after('damage_deposit_cents');
            $table->timestamp('deposit_hold_scheduled_at')->nullable()->after('remaining_payment_scheduled_at');

            $table->enum('payment_status', ['pending','upfront_paid','fully_paid','failed'])->default('pending')->after('deposit_hold_scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_customer_id','stripe_payment_method_id','upfront_payment_intent_id',
                'remaining_payment_intent_id','damage_deposit_intent_id','amount_cents',
                'upfront_cents','remaining_cents','damage_deposit_cents',
                'remaining_payment_scheduled_at','deposit_hold_scheduled_at','payment_status'
            ]);
        });
    }
};
