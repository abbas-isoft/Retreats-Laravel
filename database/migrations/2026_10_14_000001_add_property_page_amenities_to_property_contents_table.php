<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_contents', function (Blueprint $table) {
            $table->json('property_page_amenities')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('property_contents', function (Blueprint $table) {
            $table->dropColumn('property_page_amenities');
        });
    }
};
