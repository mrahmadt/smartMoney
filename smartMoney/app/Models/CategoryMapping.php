<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryMapping extends Model
{
    protected $fillable = ['account_name', 'category_id', 'alternative_category_ids'];

    protected $casts = [
        'alternative_category_ids' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function alternativeCategories(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->alternative_category_ids ?? [];
        if (empty($ids)) {
            return new \Illuminate\Database\Eloquent\Collection();
        }
        return Category::whereIn('id', $ids)->get();
    }

    public function hasAlternatives(): bool
    {
        return !empty($this->alternative_category_ids);
    }

    /**
     * Lookup category name by merchant/account name (case-insensitive).
     */
    public static function lookup(string $accountName): ?string
    {
        $mapping = static::with('category')
            ->whereRaw('LOWER(account_name) = ?', [strtolower(trim($accountName))])
            ->first();

        return $mapping?->category?->name;
    }

    /**
     * Lookup the full CategoryMapping model by merchant/account name (case-insensitive).
     */
    public static function lookupMapping(string $accountName): ?self
    {
        return static::with('category')
            ->whereRaw('LOWER(account_name) = ?', [strtolower(trim($accountName))])
            ->first();
    }

    /**
     * Store a mapping. Creates the Category if it doesn't exist.
     */
    public static function storeMapping(string $accountName, string $categoryName): void
    {
        $accountName = trim($accountName);
        $categoryName = trim($categoryName);

        if ($accountName === '' || $categoryName === '') return;

        $category = Category::firstOrCreate(
            ['name' => $categoryName]
        );

        static::updateOrCreate(
            ['account_name' => $accountName],
            ['category_id' => $category->id]
        );
    }
}
