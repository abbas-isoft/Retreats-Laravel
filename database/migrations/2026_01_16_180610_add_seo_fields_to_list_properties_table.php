<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('list_properties', function (Blueprint $table) {
            // Open Graph
            $table->string('og_url')->nullable()->after('is_active');
            $table->string('og_type')->nullable()->after('og_url');
            $table->string('og_site_name')->nullable()->after('og_type');
            $table->integer('og_image_width')->nullable()->after('og_site_name');
            $table->integer('og_image_height')->nullable()->after('og_image_width');

            // Twitter
            $table->string('twitter_card')->nullable()->after('og_image_height');
            $table->string('twitter_title')->nullable()->after('twitter_card');
            $table->string('twitter_description')->nullable()->after('twitter_title');
            $table->string('twitter_image')->nullable()->after('twitter_description');
            $table->string('twitter_site')->nullable()->after('twitter_image');
        });
    }

    public function down(): void
    {
        Schema::table('list_properties', function (Blueprint $table) {
            $table->dropColumn([
                'og_url', 'og_type', 'og_site_name', 'og_image_width', 'og_image_height',
                'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image', 'twitter_site'
            ]);
        });
    }
};
