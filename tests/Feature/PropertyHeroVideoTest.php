<?php

namespace Tests\Feature;

use App\Http\Resources\PropertyContentResource;
use App\Models\PropertyContent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyHeroVideoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('property_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        DB::table('property_contents')->insert([
            ['id' => 123, 'property_id' => 452891, 'description' => 'Original'],
            ['id' => 124, 'property_id' => 452892, 'description' => 'Other'],
        ]);
        (require database_path('migrations/2026_10_14_000005_add_hero_video_to_property_contents_table.php'))->up();
        Storage::fake('public', ['url' => 'https://api.example.com/storage']);
    }

    private function url(): string
    {
        return '/api/admin/properties/452891/hero-video';
    }

    public function test_save_relative_paths_return_full_urls_and_disable_without_losing_media(): void
    {
        $this->getJson($this->url())->assertOk()->assertJsonPath('data.hero_video.enabled', false);
        $this->putJson($this->url(), [
            'hero_video_url' => 'storage/videos/house.mp4',
            'hero_video_thumbnail_url' => 'storage/images/poster.jpg',
            'hero_video_enabled' => true,
        ])->assertOk()->assertJsonPath('data.hero_video.url', 'https://api.example.com/storage/videos/house.mp4');
        $property = PropertyContent::findOrFail(123);
        $this->assertSame('storage/videos/house.mp4', $property->getRawOriginal('hero_video_url'));
        $this->assertSame('storage/images/poster.jpg', $property->getRawOriginal('hero_video_thumbnail_url'));
        $this->putJson($this->url(), ['hero_video_enabled' => false])->assertOk()
            ->assertJsonPath('data.hero_video_url', 'storage/videos/house.mp4');
        $this->assertSame('Original', $property->fresh()->description);
        $this->getJson('/api/admin/properties/452892/hero-video')->assertOk()->assertJsonPath('data.hero_video.enabled', false);
        $property->update(['description' => 'Synced']);
        $this->assertSame('storage/videos/house.mp4', $property->fresh()->hero_video_url);
    }

    public function test_invalid_paths_and_enable_without_media_are_rejected(): void
    {
        foreach ([
            ['hero_video_enabled' => true],
            ['hero_video_url' => 'https://youtube.com/video.mp4'],
            ['hero_video_url' => 'storage/videos/../../secret.mp4'],
            ['hero_video_thumbnail_url' => 'https://api.example.com/storage/images/poster.jpg'],
            [],
        ] as $body) {
            $this->putJson($this->url(), $body)->assertUnprocessable();
        }
        $this->assertFalse(PropertyContent::find(123)->hero_video_enabled);
        $this->getJson('/api/admin/properties/123/hero-video')->assertNotFound();
    }

    public function test_upload_returns_relative_paths_and_does_not_enable_video(): void
    {
        $thumbnail = UploadedFile::fake()->createWithContent('poster.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
        $response = $this->post($this->url() . '/upload', [
            'video' => UploadedFile::fake()->create('house.mp4', 64, 'video/mp4'),
            'thumbnail' => $thumbnail,
        ], ['Accept' => 'application/json'])->assertOk();
        $videoPath = $response->json('data.video_path');
        $thumbnailPath = $response->json('data.thumbnail_path');
        $this->assertStringStartsWith('storage/videos/', $videoPath);
        $this->assertStringStartsWith('storage/images/', $thumbnailPath);
        Storage::disk('public')->assertExists(substr($videoPath, 8));
        Storage::disk('public')->assertExists(substr($thumbnailPath, 8));
        $this->assertFalse(PropertyContent::find(123)->hero_video_enabled);
        $this->putJson($this->url(), [
            'hero_video_url' => $videoPath, 'hero_video_thumbnail_url' => $thumbnailPath, 'hero_video_enabled' => true,
        ])->assertOk()->assertJsonPath('data.hero_video.enabled', true);
    }

    public function test_property_resource_includes_hero_video_object(): void
    {
        $property = PropertyContent::find(123);
        foreach (['images', 'rooms', 'beds', 'reviews', 'content_listings'] as $relation) {
            $property->setRelation($relation, collect());
        }
        $property->setRelation('listing', null);
        $data = (new PropertyContentResource($property))->resolve();
        $this->assertSame(['enabled' => false, 'url' => null, 'thumbnail_url' => null], $data['hero_video']);
    }
}
