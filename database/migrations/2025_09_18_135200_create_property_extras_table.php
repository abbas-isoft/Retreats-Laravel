<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyExtrasTable extends Migration
{
    public function up(): void
    {
        Schema::create('property_extras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('category')->nullable();
            $table->unsignedBigInteger('extra_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('img')->nullable();
            $table->decimal('price_rate', 10, 2)->default(0);
            $table->string('price_unit')->nullable();
            $table->string('price_unit_label')->nullable();
            $table->decimal('price_total', 10, 2)->default(0);
            $table->string('currency_symbol', 10)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_included')->default(false);
            $table->integer('qty')->default(0);
            $table->json('available')->nullable(); 
            $table->timestamps();

            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_extras');
    }
}
