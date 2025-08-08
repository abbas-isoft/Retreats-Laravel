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
    $table->boolean('dog_selected')->default(false);
    $table->unsignedInteger('dog_count')->default(0);
    $table->boolean('hamper')->default(false);
    $table->boolean('firewood')->default(false);
    $table->boolean('decoration')->default(false);
    $table->boolean('accepted_terms')->default(false);
    $table->boolean('accepted_rules')->default(false);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_bookings', function (Blueprint $table) {
            //
        });
    }
};
