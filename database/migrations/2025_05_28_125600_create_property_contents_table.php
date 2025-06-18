<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyContentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('property_contents', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('property_id')->unsigned();
            $table->string('customer_property_id')->nullable();
            $table->boolean('active')->default(false);
            $table->string('property_name')->nullable();
            $table->string('subcaption')->nullable();
            $table->text('description')->nullable();
            $table->text('accommodations_summary')->nullable();
            $table->text('location_description')->nullable();
            $table->string('changeover')->nullable();
            $table->string('second')->nullable();
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('post_code')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('property_type')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('epcRating')->nullable();
            $table->integer('bedroom_count')->default(0);
            $table->integer('bathroom_count')->default(0);
            $table->float('area')->nullable();
            $table->string('area_unit')->nullable();
            $table->json('amenities')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_contents');
    }
}
