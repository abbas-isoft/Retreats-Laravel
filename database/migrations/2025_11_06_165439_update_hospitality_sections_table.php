<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('hospitality_sections', function (Blueprint $table) {
            // ✅ SEO Fields
            $table->string('meta_title')->nullable()->after('subtitle');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->text('meta_keywords')->nullable()->after('meta_description');

            $table->string('og_title')->nullable()->after('meta_keywords');
            $table->text('og_description')->nullable()->after('og_title');
            $table->json('og_images')->nullable()->after('og_description'); // array of images

            $table->string('canonical_url')->nullable()->after('og_images');
            $table->longText('schema_markup')->nullable()->after('canonical_url');

            $table->boolean('robots_txt')->default(1)->after('schema_markup'); // index = 1, noindex = 0
            $table->boolean('is_active')->default(1)->after('robots_txt');
        });
    }

    public function down()
    {
        Schema::table('hospitality_sections', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'meta_keywords',
                'og_title',
                'og_description',
                'og_images',
                'canonical_url',
                'schema_markup',
                'robots_txt',
                'is_active',
            ]);
        });
    }
};
