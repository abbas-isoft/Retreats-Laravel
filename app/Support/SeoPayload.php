<?php

namespace App\Support;

use Illuminate\Validation\Rule;

final class SeoPayload
{
    public const FIELDS = [
        'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
        'og_title', 'og_description', 'og_image', 'og_url', 'og_type', 'og_site_name',
        'og_image_width', 'og_image_height', 'twitter_card', 'twitter_title',
        'twitter_description', 'twitter_image', 'twitter_site', 'robots_index',
        'schema_markup', 'is_active',
    ];

    private const PLAIN_TEXT_FIELDS = [
        'meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description',
        'og_site_name', 'twitter_title', 'twitter_description', 'twitter_site',
    ];

    public static function rules(string $prefix = ''): array
    {
        $field = fn (string $name): string => $prefix.$name;

        return [
            $field('meta_title') => ['sometimes', 'nullable', 'string', 'max:255'],
            $field('meta_description') => ['sometimes', 'nullable', 'string', 'max:2000'],
            $field('meta_keywords') => ['sometimes', 'nullable', 'string', 'max:2000'],
            $field('canonical_url') => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            $field('og_title') => ['sometimes', 'nullable', 'string', 'max:255'],
            $field('og_description') => ['sometimes', 'nullable', 'string', 'max:2000'],
            $field('og_image') => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            $field('og_url') => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            $field('og_type') => ['sometimes', 'nullable', 'string', Rule::in([
                'website', 'article', 'profile', 'book', 'business.business', 'place', 'product',
                'music.song', 'music.album', 'music.playlist', 'music.radio_station',
                'video.movie', 'video.episode', 'video.tv_show', 'video.other',
            ])],
            $field('og_site_name') => ['sometimes', 'nullable', 'string', 'max:255'],
            $field('og_image_width') => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100000'],
            $field('og_image_height') => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100000'],
            $field('twitter_card') => ['sometimes', 'nullable', 'string', Rule::in([
                'summary', 'summary_large_image', 'app', 'player',
            ])],
            $field('twitter_title') => ['sometimes', 'nullable', 'string', 'max:255'],
            $field('twitter_description') => ['sometimes', 'nullable', 'string', 'max:2000'],
            $field('twitter_image') => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            $field('twitter_site') => ['sometimes', 'nullable', 'string', 'max:255'],
            $field('robots_index') => ['sometimes', 'boolean'],
            $field('schema_markup') => ['sometimes', 'nullable', 'string', 'json', 'max:100000'],
            $field('is_active') => ['sometimes', 'boolean'],
        ];
    }

    public static function sanitize(array $values): array
    {
        $sanitized = [];

        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }

            $value = $values[$field];
            if (is_string($value)) {
                $value = trim($value);
                if (in_array($field, self::PLAIN_TEXT_FIELDS, true)) {
                    $value = trim(strip_tags($value));
                }
                if ($value === '') {
                    $value = null;
                }
            }

            $sanitized[$field] = $value;
        }

        return $sanitized;
    }
}
