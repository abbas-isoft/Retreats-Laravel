<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('homepage_contents', function (Blueprint $table) {
            $table->longText('background_image')->change();
        });
    }

    public function down()
    {
        Schema::table('homepage_contents', function (Blueprint $table) {
            $table->string('background_image')->change();
        });
    }
};
