<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\fireflyIII;

class Category extends Model
{
    protected $fillable = ['name', 'category_prompt', 'enable_prompt'];

    protected $casts = [
        'enable_prompt' => 'boolean',
    ];

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
