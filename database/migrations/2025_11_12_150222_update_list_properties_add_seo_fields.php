<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('list_properties', function (Blueprint $table) {
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->json('og_images')->nullable();
            $table->text('canonical_url')->nullable();
            $table->longText('schema_markup')->nullable();
            $table->boolean('robots_txt')->default(true);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('list_properties', function (Blueprint $table) {
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
