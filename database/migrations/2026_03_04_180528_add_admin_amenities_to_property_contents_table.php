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
        Schema::table('property_contents', function (Blueprint $table) {
            // Adding new column for Admin managed amenities
            $table->json('admin_amenities')->nullable()->after('amenities');
        });
    }

    public function down()
    {
        Schema::table('property_contents', function (Blueprint $table) {
            $table->dropColumn('admin_amenities');
        });
    }
};
