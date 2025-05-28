<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_configuration_id')->constrained()->onDelete('cascade');
            $table->string('deposit_type')->nullable();
            $table->decimal('deposit_value', 8, 2)->nullable();
            $table->decimal('security_deposit_value', 8, 2)->nullable();
            $table->integer('balance_due_days')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
    }
};
