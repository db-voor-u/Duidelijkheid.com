<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @method static ContactPage firstOrCreate(array $attributes, array $values = [])
 */
class ContactPage extends Model
{
    protected $table = 'contact_pages';
    public $timestamps = true;

    protected $fillable = [
        // Content / hero
        'title',
        'hero_title',
        'hero_subtitle',
        'intro',
        'content',
        'form_title',
        'form_content',
        'button_text',
        'hero_image_path',
        'seo_image_path',

        // SEO
        'meta_title',
        'meta_description',
        'canonical_url',
        'seo_title',
        'seo_description',

        // Form / routing
        'show_form',
        'recipient_email',
        'additional_recipients',

        // Status
        'robots_index',
        'robots_follow',
        'published',
        'updated_by',
        // 'id' is verwijderd, dit lost de Guarded assignment fout op
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean',
        'published' => 'boolean',
        'show_form' => 'boolean',
        'additional_recipients' => 'array', // json/jsonb
    ];

    protected $attributes = [
        'robots_index' => true,
        'robots_follow' => true,
        'published' => true,
        'show_form' => true,
    ];

    // Alias voor frontend
    protected function imagePath(): Attribute
    {
        return Attribute::get(fn() => $this->hero_image_path);
    }

    protected function metaTitle(): Attribute
    {
        return Attribute::get(
            fn() =>
            $this->attributes['meta_title']
            ?? $this->attributes['seo_title']
            ?? null
        );
    }

    protected function metaDescription(): Attribute
    {
        return Attribute::get(
            fn() =>
            $this->attributes['meta_description']
            ?? $this->attributes['seo_description']
            ?? null
        );
    }
}
