<?php

namespace Tests\Feature;

use App\Http\Resources\PropertyContentResource;
use App\Models\PropertyContent;
use App\Models\PropertySeoSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PropertySeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('property_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('description')->nullable();
            $table->timestamps();
        });
        DB::table('property_contents')->insert([
            ['id' => 123, 'property_id' => 452891, 'description' => 'Hostaway description'],
            ['id' => 124, 'property_id' => 452892, 'description' => 'Other property'],
        ]);
        $migration = require database_path('migrations/2026_10_13_000003_create_property_seo_settings_table.php');
        $migration->up();
    }

    public function test_read_defaults_without_creating_a_row(): void
    {
        $this->getJson('/api/admin/properties/452891/seo')->assertOk()
            ->assertJsonPath('data.seo.meta_title', null)
            ->assertJsonPath('data.seo.robots_index', true);
        $this->assertSame(0, PropertySeoSetting::count());
    }

    public function test_save_update_clear_and_property_isolation(): void
    {
        $url = '/api/admin/properties/452891/seo';
        $this->putJson($url, [
            'meta_title' => 'Milton House', 'meta_description' => 'Group stays in Bedford',
            'canonical_url' => 'https://groupretreats.co.uk/property/452891-milton-house',
            'schema_markup' => '{"@context":"https://schema.org","@type":"LodgingBusiness"}',
            'robots_index' => false, 'og_image_width' => 1200,
        ])->assertOk()->assertJsonPath('data.seo.robots_index', false);
        $this->putJson($url, ['meta_title' => null])->assertOk()
            ->assertJsonPath('data.seo.meta_title', null)
            ->assertJsonPath('data.seo.meta_description', 'Group stays in Bedford');
        $this->assertSame(1, PropertySeoSetting::count());
        $this->getJson('/api/admin/properties/452892/seo')->assertOk()
            ->assertJsonPath('data.seo.meta_description', null);
        $this->assertSame('Hostaway description', PropertyContent::find(123)->description);
        // A subsequent content sync changes content, but leaves separate SEO intact.
        PropertyContent::find(123)->update(['description' => 'Updated Hostaway description']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.seo.meta_description', 'Group stays in Bedford');
    }

    public function test_invalid_fields_unknown_properties_and_local_ids_are_rejected(): void
    {
        $this->putJson('/api/admin/properties/452891/seo', [
            'canonical_url' => 'javascript:alert(1)', 'schema_markup' => '<script>bad</script>',
            'robots_index' => null, 'og_image_width' => -1,
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'canonical_url', 'schema_markup', 'robots_index', 'og_image_width',
        ]);
        $this->putJson('/api/admin/properties/452891/seo', [])->assertUnprocessable();
        $this->getJson('/api/admin/properties/123/seo')->assertNotFound();
        $this->putJson('/api/admin/properties/999/seo', ['meta_title' => 'Unknown'])->assertNotFound();
        $this->assertSame(0, PropertySeoSetting::count());
    }

    public function test_property_resource_returns_its_own_seo_and_disabled_settings(): void
    {
        PropertySeoSetting::create(['property_id' => 452891, 'meta_title' => 'Milton SEO', 'is_active' => false]);
        $property = PropertyContent::with('seoSettings')->find(123);
        foreach (['images', 'rooms', 'beds', 'reviews', 'content_listings'] as $relation) {
            $property->setRelation($relation, collect());
        }
        $property->setRelation('listing', null);
        $data = (new PropertyContentResource($property))->resolve();
        $this->assertSame('Milton SEO', $data['seo']['meta_title']);
        $this->assertFalse($data['seo']['is_active']);
        $this->assertSame(452891, (int) $data['property_id']);
    }

    public function test_existing_property_detail_endpoint_includes_seo(): void
    {
        foreach (['content_listings', 'reviews'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id');
                $table->timestamps();
            });
        }
        Schema::create('property_content_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_content_id');
            $table->integer('sort_order')->default(0);
        });
        Schema::create('property_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_content_id');
        });
        Schema::create('property_room_beds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_room_id');
        });
        PropertySeoSetting::create(['property_id' => 452891, 'meta_title' => 'Milton detail title']);
        $this->getJson('/api/property-content/452891')->assertOk()
            ->assertJsonPath('data.0.seo.meta_title', 'Milton detail title')
            ->assertJsonPath('data.0.description', 'Hostaway description');
        $this->getJson('/api/property-content/452892')->assertOk()
            ->assertJsonStructure(['data' => [['seo' => ['meta_title', 'meta_description', 'canonical_url', 'robots_index', 'is_active']]]])
            ->assertJsonPath('data.0.seo.meta_title', null)
            ->assertJsonPath('data.0.seo.robots_index', true)
            ->assertJsonPath('data.0.seo.is_active', true);
    }
}
