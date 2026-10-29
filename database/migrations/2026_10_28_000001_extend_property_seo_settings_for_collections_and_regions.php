<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_seo_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->nullable()->change();
            $table->foreignId('collection_id')->nullable()->unique()
                ->constrained('collections')->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->unique()
                ->constrained('regions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('property_seo_settings')->whereNull('property_id')->delete();

        Schema::table('property_seo_settings', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropForeign(['region_id']);
        });
        Schema::table('property_seo_settings', function (Blueprint $table) {
            $table->dropUnique('property_seo_settings_collection_id_unique');
            $table->dropUnique('property_seo_settings_region_id_unique');
        });
        Schema::table('property_seo_settings', function (Blueprint $table) {
            $table->dropColumn(['collection_id', 'region_id']);
            $table->unsignedBigInteger('property_id')->nullable(false)->change();
        });
    }
};
