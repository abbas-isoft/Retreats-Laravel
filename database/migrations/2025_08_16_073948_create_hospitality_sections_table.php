<?php

// database/migrations/xxxx_xx_xx_create_hospitality_sections_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHospitalitySectionsTable extends Migration
{
    public function up()
    {
        Schema::create('hospitality_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('subtitle');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hospitality_sections');
    }
}
