<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_seo_settings', function (Blueprint $table) {
            $table->id();
            // Hostaway property ID remains stable when content is re-synchronised.
            $table->unsignedBigInteger('property_id')->unique();
            foreach ([
                'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
                'og_title', 'og_description', 'og_image', 'og_url', 'og_type', 'og_site_name',
                'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image', 'twitter_site',
            ] as $field) {
                $table->text($field)->nullable();
            }
            $table->longText('schema_markup')->nullable();
            $table->unsignedInteger('og_image_width')->nullable();
            $table->unsignedInteger('og_image_height')->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_seo_settings');
    }
};
