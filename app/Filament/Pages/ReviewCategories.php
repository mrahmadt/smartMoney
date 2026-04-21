<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\PendingCategoryReview;
use App\Services\fireflyIII;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class ReviewCategories extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.review-categories';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return __('menu.review_categories');
    }

    public function getTitle(): string
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return __('menu.review_categories');
    }

    // public static function getNavigationGroup(): string|\UnitEnum|null
    // {
    //     app()->setLocale(auth()->user()->language ?? 'en');
    //     return __('menu.config');
    // }

    public static function getNavigationBadge(): ?string
    {
        $count = PendingCategoryReview::pending()
            ->forUser(Auth::user())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function mount(): void
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        // Clean up orphaned reviews
        PendingCategoryReview::pending()
            ->whereDoesntHave('categoryMapping')
            ->update(['status' => 'dismissed']);
    }

    public function table(Table $table): Table
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return $table
            ->query(
                PendingCategoryReview::pending()
                    ->forUser(Auth::user())
                    ->with(['currentCategory', 'categoryMapping'])
                    ->orderByDesc('transaction_date')
            )
            ->columns([
                TextColumn::make('transaction_date')
                    ->label(__('widget.date'))
                    ->dateTime('M d, g:ia')
                    ->sortable(),
                TextColumn::make('account_name')
                    ->label(__('menu.merchant_name'))
                    ->searchable(),
                TextColumn::make('transaction_amount')
                    ->label(__('widget.amount'))
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 0, '.', ',').' '.($record->currency_code ?? ''))
                    ->color('danger'),
                TextColumn::make('currentCategory.name')
                    ->label(__('widget.category'))
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($record) => $record->currentCategory?->translatedName()),
                TextColumn::make('alternative_category_ids')
                    ->label(__('menu.alternative_categories'))
                    ->html()
                    ->getStateUsing(function ($record) {
                        $ids = $record->alternative_category_ids ?? [];
                        if (empty($ids)) {
                            return '-';
                        }
                        $categories = Category::whereIn('id', $ids)->get();
                        $buttons = [];
                        foreach ($categories as $category) {
                            $escapedName = e($category->translatedName());
                            $buttons[] = '<span style="display:inline-block;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;border-radius:6px;padding:4px 14px;font-size:12px;font-weight:500;cursor:pointer;margin:4px 4px 4px 0;" wire:click="applyAlternative('.$record->id.', '.$category->id.')" onmouseover="this.style.opacity=\'0.7\'" onmouseout="this.style.opacity=\'1\'">'.$escapedName.'</span>';
                        }

                        return new HtmlString(implode(' ', $buttons));
                    }),
            ])
            ->recordActions([
                Action::make('dismiss')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->label(__('menu.dismiss'))
                    ->requiresConfirmation(false)
                    ->action(fn (PendingCategoryReview $record) => $this->dismissReview($record)),
            ]);
    }

    public function applyAlternative(int $reviewId, int $categoryId): void
    {
        $review = PendingCategoryReview::pending()
            ->forUser(Auth::user())
            ->find($reviewId);

        if (! $review) {
            Notification::make()
                ->title(__('menu.review_already_processed'))
                ->warning()
                ->send();

            return;
        }

        // Validate category is in the review's alternatives
        $allowedIds = array_map('intval', $review->alternative_category_ids ?? []);
        if (! in_array($categoryId, $allowedIds, true)) {
            return;
        }

        $category = Category::find($categoryId);
        if (! $category) {
            return;
        }

        try {
            $firefly = new fireflyIII;
            $firefly->updateTransaction($review->firefly_transaction_id, [
                'category_name' => $category->name,
            ]);

            $review->update(['status' => 'dismissed']);

            Notification::make()
                ->title(__('menu.transaction_updated', ['category' => $category->name]))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Failed to update Firefly III transaction', ['error' => $e->getMessage(), 'review_id' => $reviewId]);
            Notification::make()
                ->title(__('menu.update_failed'))
                ->danger()
                ->send();
        }
    }

    public function setDefault(int $reviewId, int $categoryId): void
    {
        $review = PendingCategoryReview::pending()
            ->forUser(Auth::user())
            ->with('categoryMapping')
            ->find($reviewId);

        if (! $review || ! $review->categoryMapping) {
            Notification::make()
                ->title(__('menu.review_already_processed'))
                ->warning()
                ->send();

            return;
        }

        // Validate category is in the review's alternatives
        $allowedIds = array_map('intval', $review->alternative_category_ids ?? []);
        if (! in_array($categoryId, $allowedIds, true)) {
            return;
        }

        $category = Category::find($categoryId);
        if (! $category) {
            return;
        }

        try {
            $mapping = $review->categoryMapping;
            $oldCategoryId = (int) $mapping->category_id;
            $alternatives = array_map('intval', $mapping->alternative_category_ids ?? []);

            // Move old default to alternatives
            if (! in_array($oldCategoryId, $alternatives, true)) {
                $alternatives[] = $oldCategoryId;
            }

            // Remove the new default from alternatives
            $alternatives = array_values(array_filter($alternatives, fn ($id) => $id !== $categoryId));

            DB::beginTransaction();

            $mapping->update([
                'category_id' => $categoryId,
                'alternative_category_ids' => $alternatives,
            ]);

            $review->update(['status' => 'dismissed']);

            $firefly = new fireflyIII;
            $firefly->updateTransaction($review->firefly_transaction_id, [
                'category_name' => $category->name,
            ]);

            DB::commit();

            Notification::make()
                ->title(__('menu.default_changed', ['store' => $review->account_name, 'category' => $category->name]))
                ->success()
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to set default category', ['error' => $e->getMessage(), 'review_id' => $reviewId]);
            Notification::make()
                ->title(__('menu.update_failed'))
                ->danger()
                ->send();
        }
    }

    public function dismissReview(PendingCategoryReview $record): void
    {
        $record->update(['status' => 'dismissed']);

        Notification::make()
            ->title(__('menu.dismissed'))
            ->info()
            ->send();
    }
}
