<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0);
        });
        foreach (DB::table('regions')->orderBy('id')->pluck('id') as $position => $id) {
            DB::table('regions')->where('id', $id)->update(['sort_order' => $position + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
