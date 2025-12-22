<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_contents', function (Blueprint $table) {

           
            
            $table->boolean('is_video')->default(false)->after('background_video');

        
            $table->string('meta_title')->nullable()->after('is_video');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');

            
            $table->string('og_title')->nullable()->after('meta_keywords');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');

            
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->text('schema_markup')->nullable()->after('canonical_url');
            $table->boolean('robots_txt')->default(true)->after('schema_markup');
            $table->boolean('is_active')->default(true)->after('robots_txt');

          
        });
    }

    public function down(): void
    {
        Schema::table('homepage_contents', function (Blueprint $table) {
            $table->dropColumn([
                'background_video',
                'is_video',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'og_title',
                'og_description',
                'og_image',
                'canonical_url',
                'schema_markup',
                'robots_txt',
                'is_active',
            ]);
        });
    }
};
