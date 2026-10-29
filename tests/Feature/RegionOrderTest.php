<?php

namespace Tests\Feature;

use App\Models\Region;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegionOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(new \App\Models\User(['id' => 1]));
        \Illuminate\Support\Facades\Gate::define('manage-content-order', fn ($user) => true);
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('show_in_navigation')->default(true);
            $table->timestamps();
        });
        DB::table('regions')->insert([
            ['id' => 7, 'name' => 'South East', 'show_in_navigation' => true],
            ['id' => 19, 'name' => 'Explore All Stays', 'show_in_navigation' => true],
            ['id' => 29, 'name' => 'France', 'show_in_navigation' => false],
        ]);
        (require database_path('migrations/2026_10_14_000003_add_sort_order_to_regions_table.php'))->up();
        Schema::create('region_property', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('property_id');
            $table->timestamps();
        });
        DB::table('region_property')->insert(['region_id' => 7, 'property_id' => 452891]);
        Schema::create('property_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            foreach (['property_name', 'subcaption', 'description', 'accommodations_summary',
                'location_description', 'address_line', 'city', 'country'] as $field) {
                $table->text($field)->nullable();
            }
        });
        DB::table('property_contents')->insert(['property_id' => 452891]);
        Schema::create('property_seo_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('region_id')->nullable();
        });
    }

    public function test_backfill_indexes_rollback_and_hidden_detail(): void
    {
        (require database_path('migrations/2025_10_29_174012_create_latest_arrivals_table.php'))->up();
        (require database_path('migrations/2026_10_14_000002_add_sort_order_to_featured_arrival_property_table.php'))->up();
        $migration = require database_path('migrations/2026_10_23_000001_complete_content_ordering.php');
        $migration->up();
        $this->getJson('/api/admin/regions')->assertOk()->assertJsonPath('data.0.id', 19);
        $this->getJson('/api/admin/region/29')->assertOk()
            ->assertJsonPath('data.id', 29)
            ->assertJsonPath('data.show_in_navigation', false);
        $migration->down();
        $this->assertSame(3, Region::count());
    }

    public function test_order_routes_require_authentication_and_authorization(): void
    {
        auth()->forgetGuards();
        $this->patchJson('/api/admin/regions/order', ['region_ids' => [7, 19, 29]])->assertUnauthorized();
        $this->patchJson('/api/admin/featured-arrivals/1/order', ['property_ids' => []])->assertUnauthorized();
        $this->actingAs(new \App\Models\User(['id' => 2]));
        \Illuminate\Support\Facades\Gate::define('manage-content-order', fn ($user) => false);
        $this->patchJson('/api/admin/regions/order', ['region_ids' => [7, 19, 29]])->assertForbidden();
        $this->patchJson('/api/admin/featured-arrivals/1/order', ['property_ids' => []])->assertForbidden();
    }

    public function test_reorder_persists_explore_first_and_preserves_visibility_and_assignments(): void
    {
        Cache::forever('regions', 'stale');
        $this->patchJson('/api/admin/regions/order', ['region_ids' => ['19', 7, '29']])
            ->assertOk()->assertJsonPath('data.0.id', 19)->assertJsonPath('data.0.sort_order', 1);
        $response = $this->getJson('/api/admin/regions')->assertOk();
        $this->assertSame([19, 7, 29], array_column($response->json('data'), 'id'));
        $this->assertSame([1, 2, 3], array_column($response->json('data'), 'sort_order'));
        $this->assertFalse(Region::find(29)->show_in_navigation);
        $this->assertSame(1, DB::table('region_property')->where('region_id', 7)->count());
        $this->assertSame(3, Region::count());
        $this->assertFalse(Cache::has('regions'));
    }

    public function test_new_region_appends_and_edit_keeps_position(): void
    {
        $this->patchJson('/api/admin/regions/order', ['region_ids' => [19, 7, 29]])->assertOk();
        $new = $this->postJson('/api/admin/region', ['name' => 'New Destination', 'property_ids' => [452891]])
            ->assertOk()->assertJsonPath('data.sort_order', 4)->json('data.id');
        $this->putJson('/api/admin/region/'.$new, ['name' => 'Renamed Destination'])
            ->assertOk()->assertJsonPath('data.sort_order', 4);
        $response = $this->getJson('/api/admin/regions')->assertOk();
        $this->assertSame([19, 7, 29, $new], array_column($response->json('data'), 'id'));
    }

    public function test_missing_extra_duplicate_and_local_property_ids_are_rejected(): void
    {
        foreach ([[], [19, 7], [19, 7, 29, 999], [19, 7, 7], ['019', 19, 7, 29], [452891, 7, 29]] as $ids) {
            $this->patchJson('/api/admin/regions/order', ['region_ids' => $ids])->assertUnprocessable();
        }
        $this->patchJson('/api/admin/regions/order', [])->assertUnprocessable();
        $this->assertSame([7, 19, 29], array_column($this->getJson('/api/admin/regions')->json('data'), 'id'));
    }

    public function test_regions_use_id_as_the_stable_order_fallback(): void
    {
        DB::table('regions')->whereIn('id', [7, 19])->update(['sort_order' => 1]);

        $response = $this->getJson('/api/admin/regions')->assertOk();

        $this->assertSame([7, 19, 29], array_column($response->json('data'), 'id'));
    }
}
