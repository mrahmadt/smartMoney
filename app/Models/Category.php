<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\fireflyIII;

class Category extends Model
{
    protected $fillable = ['name', 'translations', 'category_prompt', 'enable_prompt'];

    protected $casts = [
        'enable_prompt' => 'boolean',
        'translations' => 'array',
    ];

    /**
     * Get the translated name for the current locale.
     * Falls back to the English name if no translation exists.
     */
    public function translatedName(): string
    {
        $locale = app()->getLocale();
        if (!empty($this->translations[$locale])) {
            return $this->translations[$locale];
        }
        return $this->name;
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(CategoryMapping::class);
    }

    /**
     * Sync categories from Firefly III. Inserts new ones, never deletes (user may have custom).
     */
    public static function syncFromFirefly(): array
    {
        $firefly = new fireflyIII();
        $remoteCategories = $firefly->getCategories();
        $created = 0;

        foreach ($remoteCategories as $name) {
            $name = trim($name);
            if ($name === '') continue;

            $exists = static::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists();
            if (!$exists) {
                static::create(['name' => $name]);
                $created++;
            }
        }

        return compact('created');
    }
}
