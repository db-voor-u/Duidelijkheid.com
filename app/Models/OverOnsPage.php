<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Model voor de 'Over Ons' pagina (hero content en SEO instellingen).
 * De tabel 'over_ons_pages' moet al bestaan.
 * * @method static OverOnsPage firstOrCreate(array $attributes, array $values = [])
 */
class OverOnsPage extends Model
{
    protected $table = 'over_ons_pages';
    public $timestamps = true;

    protected $fillable = [
        // Content / hero
        'title',
        'hero_title',
        'hero_subtitle',
        'intro',
        'content',
        'hero_image_path',
        'seo_image_path',

        // SEO
        'meta_title',
        'meta_description',
        'canonical_url',
        'seo_title',
        'seo_description',

        // Status
        'robots_index',
        'robots_follow',
        'published',
        'updated_by',
        'id',
    ];

    protected $casts = [
        'robots_index'  => 'boolean',
        'robots_follow' => 'boolean',
        'published'     => 'boolean',
    ];

    protected $attributes = [
        'robots_index'  => true,
        'robots_follow' => true,
        'published'     => true,
    ];

    // --- Accessors (Aliases) ---

    // Alias voor frontend
    protected function imagePath(): Attribute
    {
        return Attribute::get(fn () => $this->hero_image_path);
    }

    protected function metaTitle(): Attribute
    {
        return Attribute::get(fn () =>
            $this->attributes['meta_title']
            ?? $this->attributes['seo_title']
            ?? null
        );
    }

    protected function metaDescription(): Attribute
    {
        return Attribute::get(fn () =>
            $this->attributes['meta_description']
            ?? $this->attributes['seo_description']
            ?? null
        );
    }
}
