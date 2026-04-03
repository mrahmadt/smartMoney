<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PendingCategoryReview extends Model
{
    protected $fillable = [
        'firefly_transaction_id',
        'firefly_journal_id',
        'account_name',
        'category_mapping_id',
        'current_category_id',
        'alternative_category_ids',
        'user_id',
        'budget_id',
        'transaction_amount',
        'currency_code',
        'transaction_date',
        'transaction_description',
        'status',
    ];

    protected $casts = [
        'alternative_category_ids' => 'array',
        'transaction_amount' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function categoryMapping(): BelongsTo
    {
        return $this->belongsTo(CategoryMapping::class);
    }

    public function currentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'current_category_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser(Builder $query, $user): Builder
    {
        if ($user->id === 1) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id);
            if ($user->budget_id) {
                $q->orWhere('budget_id', $user->budget_id);
            }
        });
    }

    public function getAlternativeCategories(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->alternative_category_ids ?? [];
        if (empty($ids)) {
            return new \Illuminate\Database\Eloquent\Collection();
        }
        return Category::whereIn('id', $ids)->get();
    }
}
