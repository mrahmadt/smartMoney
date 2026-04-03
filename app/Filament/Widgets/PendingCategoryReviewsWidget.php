<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\PendingCategoryReview;
use App\Filament\Pages\ReviewTransactions;
use App\Services\fireflyIII;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PendingCategoryReviewsWidget extends Widget
{
    protected string $view = 'filament.widgets.pending-category-reviews';

    protected int|string|array $columnSpan = 'full';

    public array $reviews = [];

    public function mount(): void
    {
        app()->setLocale(Auth::user()->language ?? 'en');
        $this->loadReviews();
    }

    protected function loadReviews(): void
    {
        $reviews = PendingCategoryReview::pending()
            ->forUser(Auth::user())
            ->with(['currentCategory'])
            ->orderByDesc('transaction_date')
            ->take(5)
            ->get();

        // Preload all alternative category names in one query
        $allAltIds = $reviews->pluck('alternative_category_ids')->flatten()->unique()->filter()->values();
        $categoryMap = $allAltIds->isNotEmpty()
            ? Category::whereIn('id', $allAltIds)->pluck('name', 'id')
            : collect();

        $this->reviews = $reviews->map(function ($review) use ($categoryMap) {
                $altCategories = [];
                foreach ($review->alternative_category_ids ?? [] as $id) {
                    if ($categoryMap->has($id)) {
                        $altCategories[(int) $id] = $categoryMap[$id];
                    }
                }
                return [
                    'id' => $review->id,
                    'account_name' => $review->account_name,
                    'amount' => number_format($review->transaction_amount, 0, '.', ','),
                    'currency_code' => $review->currency_code ?? '',
                    'date' => $review->transaction_date->format('M d, g:ia'),
                    'current_category' => $review->currentCategory?->name ?? 'Unknown',
                    'alternatives' => $altCategories,
                    'firefly_transaction_id' => $review->firefly_transaction_id,
                ];
            })
            ->toArray();
    }

    public function applyAlternative(int $reviewId, int $categoryId): void
    {
        $review = PendingCategoryReview::pending()
            ->forUser(Auth::user())
            ->find($reviewId);

        if (!$review) {
            Notification::make()->title(__('menu.review_already_processed'))->warning()->send();
            return;
        }

        // Validate category is in the review's alternatives
        $allowedIds = array_map('intval', $review->alternative_category_ids ?? []);
        if (!in_array($categoryId, $allowedIds, true)) {
            return;
        }

        $category = Category::find($categoryId);
        if (!$category) return;

        try {
            $firefly = new fireflyIII();
            $firefly->updateTransaction($review->firefly_transaction_id, [
                'category_name' => $category->name,
            ]);

            $review->update(['status' => 'dismissed']);

            Notification::make()
                ->title(__('menu.transaction_updated', ['category' => $category->name]))
                ->success()
                ->send();

            $this->loadReviews();
        } catch (\Exception $e) {
            Log::error('Failed to update Firefly III transaction', ['error' => $e->getMessage(), 'review_id' => $reviewId]);
            Notification::make()
                ->title(__('menu.update_failed'))
                ->danger()
                ->send();
        }
    }

    public function dismissReview(int $reviewId): void
    {
        $review = PendingCategoryReview::pending()
            ->forUser(Auth::user())
            ->find($reviewId);

        if ($review) {
            $review->update(['status' => 'dismissed']);
            Notification::make()->title(__('menu.dismissed'))->info()->send();
            $this->loadReviews();
        }
    }

    public static function canView(): bool
    {
        return PendingCategoryReview::pending()
            ->forUser(Auth::user())
            ->exists();
    }
}
