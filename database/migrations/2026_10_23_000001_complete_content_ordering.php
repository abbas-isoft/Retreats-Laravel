<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve configured orders; legacy zero positions follow insertion order.
        foreach (DB::table('featured_arrivals')->orderBy('id')->pluck('id') as $filterId) {
            $seen = [];
            $position = 0;
            foreach (DB::table('featured_arrival_property')->where('featured_arrival_id', $filterId)
                ->orderBy('sort_order')->orderBy('id')->get() as $row) {
                if (isset($seen[$row->property_id])) {
                    DB::table('featured_arrival_property')->where('id', $row->id)->delete();

                    continue;
                }
                $seen[$row->property_id] = true;
                DB::table('featured_arrival_property')->where('id', $row->id)->update(['sort_order' => ++$position]);
            }
        }

        // No immutable key exists for this destination. Name is used only here.
        $exploreId = DB::table('regions')->where('name', 'Explore All Stays')->orderBy('id')->value('id');
        $ids = DB::table('regions')->orderBy('sort_order')->orderBy('id')->pluck('id');
        if ($exploreId !== null) {
            $ids = $ids->reject(fn ($id) => $id == $exploreId)->prepend($exploreId)->values();
        }
        foreach ($ids as $position => $id) {
            DB::table('regions')->where('id', $id)->update(['sort_order' => $position + 1]);
        }
        Schema::table('featured_arrival_property', function (Blueprint $table) {
            $table->unique(['featured_arrival_id', 'property_id'], 'arrival_property_unique');
            $table->index(['featured_arrival_id', 'sort_order'], 'arrival_property_order_index');
        });
        Schema::table('regions', fn (Blueprint $table) => $table->index('sort_order', 'regions_order_index'));
    }

    public function down(): void
    {
        Schema::table('featured_arrival_property', function (Blueprint $table) {
            $table->dropUnique('arrival_property_unique');
            $table->dropIndex('arrival_property_order_index');
        });
        Schema::table('regions', fn (Blueprint $table) => $table->dropIndex('regions_order_index'));
    }
};
