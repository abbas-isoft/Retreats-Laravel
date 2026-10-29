<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\PropertySeoSetting;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CollectionRegionSeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(new User(['id' => 1]));
        Gate::define('manage-content-seo', fn ($user) => true);

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('show_in_navigation')->default(true);
            $table->timestamps();
        });
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('show_in_navigation')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('property_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
        });
        Schema::create('collection_property', function (Blueprint $table) {
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('property_id');
        });
        Schema::create('region_property', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('property_id');
            $table->timestamps();
        });

        (require database_path('migrations/2026_10_13_000003_create_property_seo_settings_table.php'))->up();
        (require database_path('migrations/2026_10_28_000001_extend_property_seo_settings_for_collections_and_regions.php'))->up();

        DB::table('collections')->insert([
            ['id' => 1, 'title' => 'Festive Stays', 'description' => 'Collection description'],
            ['id' => 2, 'title' => 'Summer Stays', 'description' => 'No SEO'],
        ]);
        DB::table('regions')->insert([
            ['id' => 7, 'name' => 'South East', 'description' => 'Region description', 'sort_order' => 1],
            ['id' => 8, 'name' => 'France', 'description' => 'No SEO', 'sort_order' => 2],
        ]);
        DB::table('property_contents')->insert(['id' => 1, 'property_id' => 101]);
    }

    public function test_collection_seo_can_be_created_updated_and_cleared(): void
    {
        Cache::forever('collections', 'stale');
        Cache::forever('collection.1', 'stale');

        $this->putJson('/api/admin/collections/1/seo', [
            'meta_title' => '<b>Festive retreats</b>',
            'meta_description' => 'Large festive houses',
            'canonical_url' => 'https://groupretreats.co.uk/collections/festive',
            'schema_markup' => '{"@context":"https://schema.org","@type":"CollectionPage"}',
        ])->assertOk()
            ->assertJsonPath('data.seo.meta_title', 'Festive retreats')
            ->assertJsonPath('data.seo.og_type', 'website')
            ->assertJsonPath('data.seo.og_site_name', 'Group Retreats')
            ->assertJsonPath('data.seo.og_image_width', 1200)
            ->assertJsonPath('data.seo.twitter_card', 'summary_large_image')
            ->assertJsonPath('data.seo.robots_index', true)
            ->assertJsonPath('data.seo.schema_markup.@type', 'CollectionPage');

        $this->putJson('/api/admin/collections/1/seo', [
            'meta_title' => '   ',
            'canonical_url' => '',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.seo.meta_title', null)
            ->assertJsonPath('data.seo.canonical_url', null)
            ->assertJsonPath('data.seo.meta_description', 'Large festive houses')
            ->assertJsonPath('data.seo.is_active', false);

        $seo = PropertySeoSetting::where('collection_id', 1)->firstOrFail();
        $this->assertNull($seo->meta_title);
        $this->assertNull($seo->canonical_url);
        $this->assertFalse(Cache::has('collections'));
        $this->assertFalse(Cache::has('collection.1'));
    }

    public function test_destination_seo_can_be_created_updated_and_cleared(): void
    {
        Cache::forever('regions', 'stale');
        Cache::forever('region.7', 'stale');

        $this->putJson('/api/admin/regions/7/seo', [
            'meta_title' => 'South East retreats',
            'og_type' => 'website',
            'twitter_card' => 'summary',
            'robots_index' => false,
        ])->assertOk()->assertJsonPath('data.seo.robots_index', false);

        $this->putJson('/api/admin/regions/7/seo', [
            'meta_title' => '',
            'twitter_site' => ' @groupretreats ',
        ])->assertOk()
            ->assertJsonPath('data.seo.meta_title', null)
            ->assertJsonPath('data.seo.twitter_site', '@groupretreats')
            ->assertJsonPath('data.seo.robots_index', false);

        $this->assertNull(PropertySeoSetting::where('region_id', 7)->value('meta_title'));
        $this->assertFalse(Cache::has('regions'));
        $this->assertFalse(Cache::has('region.7'));
    }

    public function test_existing_collection_and_region_save_endpoints_persist_nested_seo_transactionally(): void
    {
        $this->postJson('/api/admin/collections', [
            'title' => 'New Collection',
            'description' => 'New description',
            'property_ids' => [101],
            'seo' => ['meta_title' => 'New collection SEO'],
        ])->assertOk()->assertJsonPath('data.seo.meta_title', 'New collection SEO');

        $this->postJson('/api/admin/region', [
            'name' => 'Scotland',
            'description' => 'Scottish destinations',
            'property_ids' => [101],
            'seo' => ['meta_title' => 'Scotland SEO'],
        ])->assertOk()->assertJsonPath('data.seo.meta_title', 'Scotland SEO');

        $this->putJson('/api/admin/region/7', [
            'name' => 'South East',
            'seo' => ['meta_description' => 'Updated destination SEO'],
        ])->assertOk()
            ->assertJsonPath('data.name', 'South East')
            ->assertJsonPath('data.seo.meta_description', 'Updated destination SEO');
    }

    public function test_collection_and_region_responses_include_seo_or_null_without_changing_existing_fields(): void
    {
        PropertySeoSetting::create(['collection_id' => 1, 'meta_title' => 'Festive SEO', 'is_active' => false]);
        PropertySeoSetting::create(['region_id' => 7, 'meta_title' => 'South East SEO']);
        $this->assertSame('Festive SEO', PropertySeoSetting::where('collection_id', 1)->value('meta_title'));
        $this->assertSame('Festive SEO', Collection::with('seoSetting')->findOrFail(1)->seoSetting?->meta_title);

        $this->getJson('/api/collections')->assertOk()
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.title', 'Festive Stays')
            ->assertJsonPath('data.0.description', 'Collection description')
            ->assertJsonPath('data.0.seo.meta_title', 'Festive SEO')
            ->assertJsonPath('data.0.seo.is_active', false)
            ->assertJsonPath('data.1.seo', null);

        $this->getJson('/api/admin/collections/1')->assertOk()
            ->assertJsonPath('data.name', 'Festive Stays')
            ->assertJsonPath('data.properties', [])
            ->assertJsonPath('data.seo.meta_title', 'Festive SEO');

        $this->getJson('/api/admin/regions')->assertOk()
            ->assertJsonPath('data.0.id', 7)
            ->assertJsonPath('data.0.name', 'South East')
            ->assertJsonPath('data.0.sort_order', 1)
            ->assertJsonPath('data.0.seo.meta_title', 'South East SEO')
            ->assertJsonPath('data.1.seo', null);
    }

    public function test_invalid_urls_schema_types_cards_and_dimensions_are_rejected(): void
    {
        $this->putJson('/api/admin/collections/1/seo', [
            'canonical_url' => 'javascript:alert(1)',
            'og_image' => '/relative.jpg',
            'og_type' => 'invalid',
            'twitter_card' => 'invalid',
            'og_image_width' => 0,
            'schema_markup' => '<script>bad</script>',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'canonical_url', 'og_image', 'og_type', 'twitter_card', 'og_image_width', 'schema_markup',
        ]);

        $this->assertSame(0, PropertySeoSetting::count());
    }

    public function test_unauthorized_users_cannot_modify_direct_or_nested_seo(): void
    {
        auth()->forgetGuards();
        $this->putJson('/api/admin/collections/1/seo', ['meta_title' => 'Denied'])->assertUnauthorized();
        $this->postJson('/api/admin/collections', [
            'title' => 'Denied', 'property_ids' => [], 'seo' => ['meta_title' => 'Denied'],
        ])->assertUnauthorized();

        $this->actingAs(new User(['id' => 2]));
        Gate::define('manage-content-seo', fn ($user) => false);
        $this->putJson('/api/admin/regions/7/seo', ['meta_title' => 'Denied'])->assertForbidden();
        $this->putJson('/api/admin/region/7', [
            'name' => 'South East', 'seo' => ['meta_title' => 'Denied'],
        ])->assertForbidden();

        $this->assertSame(0, PropertySeoSetting::count());
    }

    public function test_collection_and_region_lists_eager_load_seo_in_one_query_each(): void
    {
        Collection::create(['title' => 'Third Collection']);
        Region::create(['name' => 'Wales']);
        PropertySeoSetting::create(['collection_id' => 1, 'meta_title' => 'Collection SEO']);
        PropertySeoSetting::create(['region_id' => 7, 'meta_title' => 'Region SEO']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/api/collections')->assertOk();
        $collectionSeoQueries = collect(DB::getQueryLog())
            ->filter(fn ($query) => str_contains($query['query'], 'property_seo_settings'));
        $this->assertCount(1, $collectionSeoQueries);

        DB::flushQueryLog();
        $this->getJson('/api/admin/regions')->assertOk();
        $regionSeoQueries = collect(DB::getQueryLog())
            ->filter(fn ($query) => str_contains($query['query'], 'property_seo_settings'));
        $this->assertCount(1, $regionSeoQueries);
    }

    public function test_owner_extension_migration_rolls_back_without_losing_property_seo(): void
    {
        PropertySeoSetting::create(['property_id' => 452891, 'meta_title' => 'Property SEO']);
        PropertySeoSetting::create(['collection_id' => 1, 'meta_title' => 'Collection SEO']);

        $migration = require database_path('migrations/2026_10_28_000001_extend_property_seo_settings_for_collections_and_regions.php');
        $migration->down();

        $this->assertFalse(Schema::hasColumn('property_seo_settings', 'collection_id'));
        $this->assertFalse(Schema::hasColumn('property_seo_settings', 'region_id'));
        $this->assertSame('Property SEO', DB::table('property_seo_settings')->value('meta_title'));
        $this->assertSame(1, DB::table('property_seo_settings')->count());
    }
}
