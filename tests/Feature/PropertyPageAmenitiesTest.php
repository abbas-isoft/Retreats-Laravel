<?php

namespace Tests\Feature;

use App\Models\PropertyContent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PropertyPageAmenitiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('property_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->json('amenities')->nullable();
            $table->json('admin_amenities')->nullable();
            $table->timestamps();
        });
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        DB::table('amenities')->insert([
            ['id' => 1, 'name' => 'Internet'], ['id' => 2, 'name' => 'Kitchen'],
        ]);
        DB::table('property_contents')->insert([
            ['id' => 123, 'property_id' => 452891, 'amenities' => '["Internet","Kitchen"]', 'admin_amenities' => '[1]'],
            ['id' => 124, 'property_id' => 452892, 'amenities' => '[]', 'admin_amenities' => '[]'],
        ]);
        $migration = require database_path('migrations/2026_10_14_000001_add_property_page_amenities_to_property_contents_table.php');
        $migration->up();
    }

    public function test_read_options_and_save_independent_selection_in_order(): void
    {
        $url = '/api/admin/properties/452891/page-amenities';
        $this->getJson($url)->assertOk()->assertJsonPath('data.property_page_amenities', null)
            ->assertJsonCount(2, 'data.available_amenities');
        $this->putJson($url, ['property_page_amenities' => [2, 1]])->assertOk()
            ->assertJsonPath('data.property_page_amenities', [2, 1]);
        $property = PropertyContent::findOrFail(123);
        $this->assertSame([2, 1], $property->property_page_amenities);
        $this->assertSame(['Internet', 'Kitchen'], $property->amenities);
        $this->assertSame('[1]', $property->admin_amenities);
        $this->assertSame([['id' => 2, 'name' => 'Kitchen'], ['id' => 1, 'name' => 'Internet']], $property->propertyPageAmenityDetails());
        $this->assertNull(PropertyContent::findOrFail(124)->property_page_amenities);
        $property->update(['amenities' => ['Updated Hostaway list']]);
        $this->assertSame([2, 1], $property->fresh()->property_page_amenities);
    }

    public function test_empty_selection_and_reset_have_distinct_meanings(): void
    {
        $url = '/api/admin/properties/452891/page-amenities';
        $this->putJson($url, ['property_page_amenities' => []])->assertOk()
            ->assertJsonPath('data.property_page_amenities', []);
        $this->assertSame([], PropertyContent::find(123)->propertyPageAmenityDetails());
        $this->putJson($url, ['property_page_amenities' => null])->assertOk()
            ->assertJsonPath('data.property_page_amenities', null);
    }

    public function test_invalid_unknown_and_duplicate_ids_do_not_save(): void
    {
        foreach ([[], ['property_page_amenities' => [999]], ['property_page_amenities' => [1, 1]],
            ['property_page_amenities' => ['Kitchen']]] as $payload) {
            $this->putJson('/api/admin/properties/452891/page-amenities', $payload)->assertUnprocessable();
        }
        $this->assertNull(PropertyContent::find(123)->property_page_amenities);
        $this->getJson('/api/admin/properties/123/page-amenities')->assertNotFound();
        $this->putJson('/api/admin/properties/999/page-amenities', ['property_page_amenities' => []])->assertNotFound();
    }
}
