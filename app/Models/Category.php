<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'type'];

    /**
     * Blogs die tot deze categorie behoren (hoofdblog)
     */
    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    /**
     * Over Ons blogs die tot deze categorie behoren
     */
    public function overOnsBlogs(): HasMany
    {
        return $this->hasMany(OverOnsBlog::class);
    }

    // Helper to auto-generate slug if missing
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
