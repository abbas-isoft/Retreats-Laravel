<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionSectionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('collection_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique();
            $table->string('title');
            $table->text('description');
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_sections');
    }
}
