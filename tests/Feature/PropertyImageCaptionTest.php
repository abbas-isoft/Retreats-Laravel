<?php

namespace Tests\Feature;

use App\Http\Resources\PropertyContentImageResource;
use App\Jobs\FetchPropertyContentsJob;
use App\Models\PropertyContent;
use App\Models\PropertyContentImage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PropertyImageCaptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once database_path('migrations/2025_05_28_125600_create_property_contents_table.php');
        (new \CreatePropertyContentsTable())->up();
        Schema::table('property_contents', function (Blueprint $table) {
            $table->unsignedBigInteger('hostaway_id')->nullable();
        });
        Schema::create('property_content_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_content_id');
            $table->string('photo_id')->nullable();
            $table->string('url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_14_000004_add_caption_to_property_content_images_table.php'))->up();
        Schema::create('property_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_content_id');
        });
        Schema::create('property_room_beds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_room_id');
        });
        Http::preventStrayRequests();
    }

    private function sync(array $images): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['api.hostaway.com/v1/listings*' => Http::response(['result' => [[
            'id' => 452891, 'name' => 'Milton House', 'listingImages' => $images,
        ]]], 200)]);
        (new FetchPropertyContentsJob())->handle();
    }

    public function test_existing_photo_caption_updates_without_replacing_its_file_or_row(): void
    {
        $property = PropertyContent::create(['property_id' => 452891]);
        $image = $property->images()->create(['photo_id' => '12345', 'url' => 'storage/images/existing.webp']);
        $this->sync([['id' => 12345, 'caption' => 'Front elevation – Norfolk',
            'url' => 'https://127.0.0.1:1/not-downloaded.jpg']]);
        $image->refresh();
        $this->assertSame('Front elevation – Norfolk', $image->caption);
        $this->assertSame('storage/images/existing.webp', $image->getRawOriginal('url'));
        $this->assertSame(1, PropertyContentImage::count());
        $this->assertSame('Front elevation – Norfolk', (new PropertyContentImageResource($image))->resolve()['caption']);
        $this->sync([['id' => 12345, 'caption' => 'Updated caption']]);
        $this->assertSame('Updated caption', $image->fresh()->caption);
        $this->sync([['id' => 12345, 'caption' => null]]);
        $this->assertNull($image->fresh()->caption);
        $this->assertSame(1, PropertyContentImage::count());
    }

    public function test_new_images_store_caption_and_missing_caption_returns_null(): void
    {
        $this->sync([['id' => 100, 'caption' => 'The dining room'], ['id' => 101]]);
        $this->assertSame(2, PropertyContentImage::count());
        $this->assertSame('The dining room', PropertyContentImage::where('photo_id', '100')->firstOrFail()->caption);
        $image = PropertyContentImage::where('photo_id', '101')->firstOrFail();
        $this->assertArrayHasKey('caption', (new PropertyContentImageResource($image))->resolve());
        $this->assertNull($image->caption);
    }

    public function test_booking_engine_caption_is_preferred_and_blank_values_fall_back(): void
    {
        $property = PropertyContent::create(['property_id' => 452891]);
        $image = $property->images()->create(['photo_id' => '339921602', 'url' => 'storage/images/existing.webp']);
        $description = 'Saham Grove Hall front elevation at Norfolk - holiday home perfect for group getaway';
        $this->sync([
            ['id' => 339921602, 'caption' => '211015-SahamGroveHall-026.jpg', 'bookingEngineCaption' => $description],
            ['id' => 102, 'caption' => 'Bedroom', 'bookingEngineCaption' => 'Booking engine bedroom'],
            ['id' => 103, 'caption' => 'Kitchen', 'bookingEngineCaption' => '   '],
            ['id' => 104, 'caption' => 'Garden', 'bookingEngineCaption' => null],
        ]);
        $this->assertSame($description, $image->fresh()->caption);
        $this->assertSame('storage/images/existing.webp', $image->fresh()->getRawOriginal('url'));
        $this->assertSame('Booking engine bedroom', PropertyContentImage::where('photo_id', '102')->firstOrFail()->caption);
        $this->assertSame('Kitchen', PropertyContentImage::where('photo_id', '103')->firstOrFail()->caption);
        $this->assertSame('Garden', PropertyContentImage::where('photo_id', '104')->firstOrFail()->caption);
    }
}
