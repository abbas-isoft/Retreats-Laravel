<?php

namespace Tests\Feature;

use App\Models\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CollectionNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
        DB::table('collections')->insert(['id' => 1, 'title' => 'Festive', 'description' => 'Original description']);
        $migration = require database_path('migrations/2026_10_13_000001_add_show_in_navigation_to_collections_table.php');
        $migration->up();
        Schema::create('collection_property', function (Blueprint $table) {
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('property_id');
        });
        Schema::create('property_content_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_content_id');
        });
        Schema::create('property_seo_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collection_id')->nullable();
        });
    }

    public function test_existing_and_new_collections_default_to_visible(): void
    {
        $this->assertTrue(Collection::findOrFail(1)->show_in_navigation);
        $new = Collection::create(['title' => 'Easter']);
        $this->assertTrue($new->fresh()->show_in_navigation);
        $this->getJson('/api/collections')->assertOk()->assertJsonPath('data.0.show_in_navigation', true);
    }

    public function test_hide_and_unhide_preserve_collection_and_detail_access(): void
    {
        foreach ([false, false, true] as $visible) {
            $this->patchJson('/api/admin/collections/1/navigation', ['show_in_navigation' => $visible])
                ->assertOk()->assertJsonPath('data.show_in_navigation', $visible);
            $this->assertSame($visible, Collection::findOrFail(1)->show_in_navigation);
            $this->getJson('/api/collections')->assertOk()
                ->assertJsonPath('data.0.id', 1)->assertJsonPath('data.0.show_in_navigation', $visible);
            $this->getJson('/api/admin/collections/1')->assertOk()
                ->assertJsonPath('data.show_in_navigation', $visible);
            $this->getJson('/api/filter-by-collection/1')->assertOk();
        }
        $this->assertSame('Festive', Collection::findOrFail(1)->title);
        $this->assertSame('Original description', Collection::findOrFail(1)->description);
    }

    public function test_invalid_and_missing_values_cannot_change_visibility(): void
    {
        foreach ([[], ['show_in_navigation' => 'invalid'], ['show_in_navigation' => null]] as $payload) {
            $this->patchJson('/api/admin/collections/1/navigation', $payload)
                ->assertUnprocessable()->assertJsonValidationErrors('show_in_navigation');
        }
        $this->assertTrue(Collection::findOrFail(1)->show_in_navigation);
        $this->patchJson('/api/admin/collections/999/navigation', ['show_in_navigation' => false])
            ->assertNotFound();
    }
}
