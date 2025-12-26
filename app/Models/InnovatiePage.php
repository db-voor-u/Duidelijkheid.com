<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Model voor de Innovatie pagina hero content en SEO instellingen.
 * 
 * Geconsolideerd schema - alle page models hebben nu dezelfde velden.
 */
class InnovatiePage extends Model
{
    protected $fillable = [
        // Content / Hero
        'title',
        'hero_title',
        'hero_subtitle',
        'intro',
        'content',
        'image_path',
        'hero_image_path',

        // SEO
        'meta_title',
        'meta_description',
        'seo_image_path',
        'canonical_url',

        // Status
        'robots_index',
        'robots_follow',
        'published',
        'updated_by',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean',
        'published' => 'boolean',
    ];

    protected $attributes = [
        'robots_index' => true,
        'robots_follow' => true,
        'published' => true,
    ];

    // Alias voor consistentie met andere page models
    protected function imagePath(): Attribute
    {
        return Attribute::get(fn() => $this->hero_image_path ?? $this->attributes['image_path'] ?? null);
    }
}
