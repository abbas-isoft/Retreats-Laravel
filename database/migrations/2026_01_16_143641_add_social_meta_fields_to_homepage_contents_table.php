<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_contents', function (Blueprint $table) {

            // ===== Open Graph =====
            $table->string('og_url')->nullable()->after('is_active');
            $table->string('og_type')->default('website')->after('og_url');
            $table->string('og_site_name')->nullable()->after('og_type');

            // Best & Safe Dimension (FB / LinkedIn)
            $table->unsignedSmallInteger('og_image_width')->default(1200)->after('og_image');
            $table->unsignedSmallInteger('og_image_height')->default(630)->after('og_image_width');

            // ===== Twitter =====
            $table->string('twitter_card')->default('summary_large_image')->after('og_image_height');
            $table->string('twitter_title')->nullable()->after('twitter_card');
            $table->text('twitter_description')->nullable()->after('twitter_title');
            $table->string('twitter_image')->nullable()->after('twitter_description');
            $table->string('twitter_site')->nullable()->after('twitter_image');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_contents', function (Blueprint $table) {

            // Open Graph
            $table->dropColumn([
                'og_title',
                'og_description',
                'og_url',
                'og_type',
                'og_site_name',
                'og_image_width',
                'og_image_height',
            ]);

            // Twitter
            $table->dropColumn([
                'twitter_card',
                'twitter_title',
                'twitter_description',
                'twitter_image',
                'twitter_site',
            ]);
        });
    }
};
