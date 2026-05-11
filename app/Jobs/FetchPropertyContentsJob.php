<?php

namespace App\Jobs;

use App\Models\PropertyContent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchPropertyContentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        try {

            // $response = Http::withHeaders([
            //     'Authorization' => 'Bearer ' . config('services.hostaway.token'),
            //     'Cache-Control' => 'no-cache',
            // ])->get("https://api.hostaway.com/v1/listings");

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                'Cache-Control' => 'no-cache',
            ])->get('https://api.hostaway.com/v1/listings', [
                'attachObjects' => ['bookingEngineUrls'],
                'isBookingEngineActive' => true,
            ]);

            if (!$response->successful()) {
                Log::error('Hostaway API request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return;
            }

            $properties = $response->json('result', []);

            if (empty($properties)) {
                Log::warning('No properties returned from Hostaway API.');
                return;
            }
            $hostawayIds = collect($properties)->pluck('id')->toArray();

            foreach ($properties as $propertyData) {

                $isBookingEngineActive = 0;

                if (
                    isset($propertyData['bookingEngineUrls']) &&
                    is_array($propertyData['bookingEngineUrls']) &&
                    count($propertyData['bookingEngineUrls']) >= 2
                ) {
                    $isBookingEngineActive = 1;
                }
                $images = $propertyData['listingImages'] ?? [];
                $beds   = $propertyData['listingBedTypes'] ?? [];

                $securityDeposit = 0;
                if (isset($propertyData['customFieldValues'])) {
                    foreach ($propertyData['customFieldValues'] as $customField) {

                        if (
                            isset($customField['customField']['name']) &&
                            strtolower($customField['customField']['name']) === 'security deposit' &&
                            isset($customField['value'])
                        ) {
                            $securityDeposit = (float) $customField['value'];

                            break;
                        }
                    }
                }

                PropertyContent::whereNotIn('property_id', $hostawayIds)->delete();

                Log::info('Deleted properties not found in Hostaway anymore');


                $listingAmenities = $propertyData['listingAmenities'] ?? [];
                $amenities = collect($listingAmenities)->pluck('amenityName')->toArray();


                $content = PropertyContent::where('property_id', $propertyData['id'])->first();

                // Data for update/insert
                $data = [
                    'hostaway_id'          => $propertyData['id'],
                    'customer_property_id' => $propertyData['id'],
                    'property_type'        => $propertyData['propertyTypeId'] ?? null,
                    'latitude'             => $propertyData['lat'] ?? null,
                    'longitude'            => $propertyData['lng'] ?? null,
                    'security_deposit'     => $securityDeposit ?? 0,
                    'active' => $isBookingEngineActive,
                    'property_name'        => $propertyData['name'] ?? null,
                    'subcaption'           => $propertyData['externalListingName'] ?? null,
                    'description'          => $propertyData['description'] ?? null,
                    'address_line'         => $propertyData['street'] ?? null,
                    'city'                 => $propertyData['city'] ?? null,
                    'country'              => $propertyData['country'] ?? null,
                    'post_code'            => $propertyData['zipcode'] ?? null,
                    'bedroom_count'        => $propertyData['bedroomsNumber'] ?? 0,
                    'bathroom_count'       => $propertyData['bathroomsNumber'] ?? 0,
                    'area'                 => $propertyData['squareMeters'] ?? 0,
                    'area_unit'            => 'sqm',
                    'amenities'            => json_encode($amenities),
                ];

                if ($content) {

                    $content->update($data);
                    Log::info("Updated Existing PropertyContent: {$propertyData['id']}");
                } else {
                    $content = PropertyContent::create(array_merge($data, [
                        'property_id' => $propertyData['id']
                    ]));
                    Log::info("Inserted New PropertyContent: {$propertyData['id']}");
                }

                if (!empty($images)) {        
                    // Get existing images map [photo_id => id] to avoid re-fetching existing ones
                    $existingImages = $content->images()->pluck('id', 'photo_id')->toArray();
                    $newImageIds = [];

                    // Define the storage path for property images
                    $storagePath = public_path('storage/property_images');
                    if (!file_exists($storagePath)) {
                        mkdir($storagePath, 0755, true);
                    }

                    foreach ($images as $index => $img) {
                        $photoId = $img['id'] ?? null;
                        
                        // If image already exists in DB, update its sort_order and skip re-processing
                        if ($photoId && isset($existingImages[$photoId])) {
                            $existingImageId = $existingImages[$photoId];
                            $content->images()->where('id', $existingImageId)->update(['sort_order' => $img['sortOrder']]);
                            $newImageIds[] = $existingImageId;
                            continue;
                        }

                        $originalUrl = $img['url'] ?? null;
                        $finalUrl = $originalUrl; // Default to original URL if optimization fails

                        if ($originalUrl) {
                            try {
                                // Generate a unique filename (WebP)
                                $filename = 'prop_' . $content->property_id . '_' . ($photoId ?? uniqid()) . '.webp';
                                $localPath = $storagePath . '/' . $filename;
                                $publicUrl = asset('storage/property_images/' . $filename);

                                // Check if file already exists locally to avoid re-processing
                                if (!file_exists($localPath)) {
                                    // Get image content
                                    $imageContent = @file_get_contents($originalUrl);

                                    if ($imageContent) {
                                        $sourceImage = @imagecreatefromstring($imageContent);

                                        if ($sourceImage) {
                                            // Get original dimensions
                                            $width = imagesx($sourceImage);
                                            $height = imagesy($sourceImage);

                                            // Target width (e.g., 1200px for web display)
                                            $targetWidth = 1200;

                                            if ($width > $targetWidth) {
                                                // Calculate new height maintaining aspect ratio
                                                $targetHeight = floor($height * ($targetWidth / $width));

                                                // Create new true color image
                                                $optimizedImage = imagecreatetruecolor($targetWidth, $targetHeight);

                                                // Fill with white background (optional, good for consistency)
                                                $white = imagecolorallocate($optimizedImage, 255, 255, 255);
                                                imagefilledrectangle($optimizedImage, 0, 0, $targetWidth, $targetHeight, $white);

                                                // Resize
                                                imagecopyresampled($optimizedImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

                                                // Save as WebP with 75% quality
                                                imagewebp($optimizedImage, $localPath, 75);

                                                // Free memory
                                                imagedestroy($sourceImage);
                                                imagedestroy($optimizedImage);

                                                // Update final URL to local optimized path
                                                $finalUrl = $publicUrl;
                                            } else {
                                                // If image is already small, just convert and save as WebP
                                                imagewebp($sourceImage, $localPath, 75);
                                                imagedestroy($sourceImage);
                                                $finalUrl = $publicUrl;
                                            }
                                        }
                                    }
                                } else {
                                    // File exists, just use the public URL
                                    $finalUrl = $publicUrl;
                                }
                            } catch (\Exception $e) {
                                Log::warning("Image optimization failed for Property {$content->property_id}: " . $e->getMessage());
                                // Fallback to original URL is already set
                            }
                        }

                        $newImage = $content->images()->create([
                            'photo_id' => $photoId,
                            'url'      => $finalUrl,
                            'sort_order' => $index,
                        ]);
                        $newImageIds[] = $newImage->id;
                    }

                    // Delete images that are no longer present in the API response
                    $content->images()->whereNotIn('id', $newImageIds)->delete();
                }

                $roomsFromApi = $propertyData['rooms'] ?? [];


                $content->rooms()->each(function ($room) {
                    $room->beds()->delete();
                });
                $content->rooms()->delete();


                if (!empty($roomsFromApi)) {

                    foreach ($roomsFromApi as $r) {
                        $room = $content->rooms()->create([
                            'name' => $r['name'] ?? 'Room',
                            'type' => $r['type'] ?? null,
                        ]);

                        if (!empty($r['beds'])) {
                            foreach ($r['beds'] as $bed) {
                                $room->beds()->create([
                                    'bed_type' => $bed['bedTypeId'] ?? null,
                                    'quantity' => $bed['quantity'] ?? 1,
                                ]);
                            }
                        }
                    }
                } else {
                    if (!empty($beds)) {
                        $room = $content->rooms()->create([
                            'name' => 'Bedroom',
                            'type' => 'bedroom',
                        ]);

                        foreach ($beds as $bed) {
                            $room->beds()->create([
                                'bed_type' => $bed['bedTypeId'] ?? null,
                                'quantity' => $bed['quantity'] ?? 1,
                            ]);
                        }
                    }
                }


                Log::info("Property sync completed", [
                    'property_id'      => $content->property_id,
                    'images_count'     => count($images),
                    'beds_count'       => count($beds),
                    'amenities_count'  => count($amenities),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while fetching Hostaway properties', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}
