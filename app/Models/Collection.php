<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Collection extends Model
{
    protected $fillable = ['title', 'description', 'image'];

    protected $attributes = ['show_in_navigation' => true];

    protected $casts = ['show_in_navigation' => 'boolean'];

    public function getImageAttribute($value)
    {
        if (!$value) {
            return $value;
        }

        $adminUrl = rtrim(config('app.admin_url') ?? '', '/');
        $path = ltrim($value, '/');

        // Repair old admin-domain URLs only when the file exists on this backend.
        if ($adminUrl && str_starts_with($value, $adminUrl . '/storage/')) {
            $path = substr($value, strlen($adminUrl) + 1);
        }

        if (str_starts_with($path, 'storage/')) {
            $diskPath = substr($path, strlen('storage/'));
            if (Storage::disk('public')->exists($diskPath)) {
                return Storage::disk('public')->url($diskPath);
            }
        }

        // Keep existing CMS-hosted and external images working.
        return str_starts_with($value, 'http') ? $value : $adminUrl . '/' . $path;
    }

    public function properties()
{
    return $this->belongsToMany(
        \App\Models\PropertyContent::class,
        'collection_property',
        'collection_id',
        'property_id'
    );
}
}
