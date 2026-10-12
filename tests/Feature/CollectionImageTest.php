<?php

namespace Tests\Feature;

use App\Models\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollectionImageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.admin_url' => 'https://admin.example.com',
            'filesystems.disks.public.url' => 'https://api.example.com/storage',
        ]);
        Storage::fake('public', ['url' => 'https://api.example.com/storage']);
    }

    public function test_upload_returns_url_for_backend_storage(): void
    {
        $image = UploadedFile::fake()->createWithContent('tile.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
        $response = $this->post('/api/upload-image', ['image' => $image], ['Accept' => 'application/json']);
        $response->assertOk()->assertJsonPath('status', true);
        $url = $response->json('image_url');
        $this->assertStringStartsWith('https://api.example.com/storage/images/', $url);
        Storage::disk('public')->assertExists(substr($url, strlen('https://api.example.com/storage/')));
    }

    public function test_local_collection_files_use_backend_url_including_old_admin_urls(): void
    {
        Storage::disk('public')->put('images/tile.jpg', 'image-content');
        foreach (['storage/images/tile.jpg', 'https://admin.example.com/storage/images/tile.jpg'] as $value) {
            $collection = new Collection(['image' => $value]);
            $this->assertSame('https://api.example.com/storage/images/tile.jpg', $collection->image);
        }
    }

    public function test_cms_and_external_images_are_preserved_when_not_stored_locally(): void
    {
        foreach ([
            'storage/images/legacy.jpg' => 'https://admin.example.com/storage/images/legacy.jpg',
            'https://admin.example.com/storage/images/legacy.jpg' => 'https://admin.example.com/storage/images/legacy.jpg',
            'https://cdn.example.com/image.jpg' => 'https://cdn.example.com/image.jpg',
        ] as $value => $expected) {
            $this->assertSame($expected, (new Collection(['image' => $value]))->image);
        }
        $this->assertNull((new Collection(['image' => null]))->image);
    }
}
