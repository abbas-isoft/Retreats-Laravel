<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FeaturedArrivalOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('property_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('property_name');
            $table->json('amenities')->nullable();
        });
        foreach ([101, 102, 103, 104] as $id) {
            DB::table('property_contents')->insert(['property_id' => $id, 'property_name' => 'House ' . $id, 'amenities' => '[]']);
        }
        Schema::create('property_content_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_content_id');
            $table->unsignedBigInteger('photo_id')->nullable();
            $table->string('url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        (require database_path('migrations/2025_10_29_174012_create_latest_arrivals_table.php'))->up();
        (require database_path('migrations/2026_10_14_000002_add_sort_order_to_featured_arrival_property_table.php'))->up();
    }

    private function createSection(array $ids): int
    {
        $response = $this->postJson('/api/admin/featured-arrivals', [
            'title' => 'Latest Arrivals', 'description' => 'New houses', 'property_ids' => $ids,
        ])->assertOk();
        $this->assertSame($ids, array_column($response->json('data.properties'), 'property_id'));
        return $response->json('data.id');
    }

    public function test_create_update_and_get_use_submitted_order(): void
    {
        $id = $this->createSection([103, 101, 102]);
        $this->getJson('/api/admin/featured-arrivals/' . $id)->assertOk()
            ->assertJsonPath('data.properties.0.property_id', 103)
            ->assertJsonPath('data.properties.0.sort_order', 1);
        $this->putJson('/api/admin/featured-arrivals/' . $id, [
            'title' => 'Latest Arrivals', 'property_ids' => [104, 102, 101, 103],
        ])->assertOk()->assertJsonPath('data.properties.0.property_id', 104);
        $this->assertSame([104, 102, 101, 103], array_column(
            $this->getJson('/api/admin/featured-arrivals/' . $id)->json('data.properties'), 'property_id'
        ));
    }

    public function test_reorder_preserves_content_membership_and_other_sections(): void
    {
        $id = $this->createSection([101, 102, 103]);
        $other = $this->createSection([101, 103]);
        $this->patchJson('/api/admin/featured-arrivals/' . $id . '/order', ['property_ids' => [103, 102, 101]])
            ->assertOk()->assertJsonPath('data.title', 'Latest Arrivals')
            ->assertJsonPath('data.description', 'New houses');
        $this->assertSame([103, 102, 101], array_column(
            $this->getJson('/api/admin/featured-arrivals/' . $id)->json('data.properties'), 'property_id'
        ));
        $this->getJson('/api/admin/featured-arrivals/' . $other)->assertOk()
            ->assertJsonPath('data.properties.0.property_id', 101);
        $this->assertSame(5, DB::table('featured_arrival_property')->count());
    }

    public function test_invalid_orders_do_not_change_saved_order(): void
    {
        $id = $this->createSection([101, 102, 103]);
        foreach ([[], [101, 102], [101, 102, 104], [101, 101, 103]] as $ids) {
            $this->patchJson('/api/admin/featured-arrivals/' . $id . '/order', ['property_ids' => $ids])
                ->assertUnprocessable();
        }
        $this->getJson('/api/admin/featured-arrivals/' . $id)->assertOk()
            ->assertJsonPath('data.properties.0.property_id', 101);
        $this->patchJson('/api/admin/featured-arrivals/999/order', ['property_ids' => [101]])->assertNotFound();
    }

    public function test_exact_section_eight_payload_matches_get_despite_stale_pivot_rows(): void
    {
        DB::table('featured_arrivals')->insert(['id' => 8, 'title' => 'Latest Arrivals']);
        $mapping = [86 => 457567, 89 => 457750, 97 => 470518, 117 => 514914, 96 => 466952];
        foreach ($mapping as $localId => $hostawayId) {
            DB::table('property_contents')->insert([
                'id' => $localId, 'property_id' => $hostawayId,
                'property_name' => 'House ' . $hostawayId, 'amenities' => '[]',
            ]);
            DB::table('featured_arrival_property')->insert([
                'featured_arrival_id' => 8, 'property_id' => (string) $hostawayId, 'sort_order' => 0,
            ]);
        }
        // Old selection points to a property no longer present after a sync.
        $staleId = DB::table('featured_arrival_property')->insertGetId([
            'featured_arrival_id' => 8, 'property_id' => '575651', 'sort_order' => 0,
        ]);
        $url = '/api/admin/featured-arrivals/8';
        $order = [457750, 457567, 470518, 514914, 466952];
        $this->getJson($url)->assertOk()->assertJsonCount(5, 'data.properties');
        $response = $this->patchJson($url . '/order', ['property_ids' => $order])->assertOk();
        $this->assertSame($order, array_column($response->json('data.properties'), 'property_id'));
        $this->assertSame([1, 2, 3, 4, 5], array_column($response->json('data.properties'), 'sort_order'));
        $this->assertSame($order, array_column($this->getJson($url)->json('data.properties'), 'property_id'));
        $this->patchJson($url . '/order', ['property_ids' => array_map('strval', $order)])->assertOk();
        foreach ([array_slice($order, 1), [...$order, 575651], [...$order, $order[0]],
            ['0457750', ...$order], array_keys($mapping)] as $ids) {
            $this->patchJson($url . '/order', ['property_ids' => $ids])->assertUnprocessable();
        }
        $this->assertSame(6, DB::table('featured_arrival_property')->where('featured_arrival_id', 8)->count());
        $this->assertSame(0, DB::table('featured_arrival_property')->where('id', $staleId)->value('sort_order'));
    }
}
