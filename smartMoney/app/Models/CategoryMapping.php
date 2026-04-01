<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryMapping extends Model
{
    protected $fillable = ['account_name', 'category_id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
