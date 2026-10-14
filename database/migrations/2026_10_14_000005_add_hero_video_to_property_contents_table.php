<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_contents', function (Blueprint $table) {
            $table->text('hero_video_url')->nullable();
            $table->text('hero_video_thumbnail_url')->nullable();
            $table->boolean('hero_video_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('property_contents', function (Blueprint $table) {
            $table->dropColumn(['hero_video_url', 'hero_video_thumbnail_url', 'hero_video_enabled']);
        });
    }
};
