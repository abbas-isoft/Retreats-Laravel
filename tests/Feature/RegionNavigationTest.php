<?php

namespace Tests\Feature;

use App\Models\Region;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegionNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
        DB::table('regions')->insert(['id' => 7, 'name' => 'South East', 'description' => 'Original']);
        $migration = require database_path('migrations/2026_10_13_000002_add_show_in_navigation_to_regions_table.php');
        $migration->up();
    }

    public function test_existing_and_new_regions_default_to_visible(): void
    {
        $this->assertTrue(Region::findOrFail(7)->show_in_navigation);
        $new = Region::create(['name' => 'France']);
        $this->assertTrue($new->fresh()->show_in_navigation);
        $this->getJson('/api/admin/regions')->assertOk()->assertJsonPath('data.0.show_in_navigation', true);
    }

    public function test_hide_and_unhide_preserve_region_and_active_status(): void
    {
        foreach ([false, false, true] as $visible) {
            $this->patchJson('/api/admin/regions/7/navigation', ['show_in_navigation' => $visible])
                ->assertOk()->assertJsonPath('data.id', 7)->assertJsonPath('data.show_in_navigation', $visible);
            $this->assertSame($visible, Region::findOrFail(7)->show_in_navigation);
            $this->getJson('/api/admin/regions')->assertOk()
                ->assertJsonPath('data.0.id', 7)->assertJsonPath('data.0.show_in_navigation', $visible);
        }
        $region = Region::findOrFail(7);
        $this->assertSame('South East', $region->name);
        $this->assertSame('Original', $region->description);
        $this->assertSame(0, (int) $region->active);
    }

    public function test_invalid_or_missing_values_and_unknown_regions_are_rejected(): void
    {
        foreach ([[], ['show_in_navigation' => 'invalid'], ['show_in_navigation' => null]] as $payload) {
            $this->patchJson('/api/admin/regions/7/navigation', $payload)
                ->assertUnprocessable()->assertJsonValidationErrors('show_in_navigation');
        }
        $this->assertTrue(Region::findOrFail(7)->show_in_navigation);
        $this->patchJson('/api/admin/regions/999/navigation', ['show_in_navigation' => false])
            ->assertNotFound();
    }
}
