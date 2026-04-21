<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking_steps', function (Blueprint $table) {
            $table->text('cta_text')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('booking_steps', function (Blueprint $table) {
            $table->dropColumn('cta_text');
        });
    }
};

