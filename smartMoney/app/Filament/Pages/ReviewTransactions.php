<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\CategoryMapping;
use App\Models\PendingCategoryReview;
use App\Services\fireflyIII;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class ReviewTransactions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.review-transactions';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?int $navigationSort = 3;

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

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.config');
    }

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
                TextColumn::make('transaction_description')
                    ->label(__('widget.description'))
                    ->limit(40),
                TextColumn::make('transaction_amount')
                    ->label(__('widget.amount'))
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 0, '.', ',') . ' ' . ($record->currency_code ?? ''))
                    ->color('danger'),
                TextColumn::make('currentCategory.name')
                    ->label(__('widget.category'))
                    ->badge()
                    ->color('primary'),
                TextColumn::make('alternative_category_ids')
                    ->label(__('menu.alternative_categories'))
                    ->formatStateUsing(function ($state, $record) {
                        $ids = $record->alternative_category_ids ?? [];
                        if (empty($ids)) return '-';
                        $categories = Category::whereIn('id', $ids)->pluck('name', 'id');
                        $buttons = [];
                        foreach ($categories as $id => $name) {
                            $escapedName = e($name);
                            $setDefaultLabel = e(__('menu.set_as_default'));
                            $buttons[] = '<div class="inline-flex flex-col items-center gap-0.5 mb-1">'
                                . '<span class="fi-badge fi-color-success inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset cursor-pointer hover:opacity-80" style="background-color: rgb(220 252 231); color: rgb(22 101 52); ring-color: rgb(187 247 208);" wire:click="applyAlternative(' . $record->id . ', ' . $id . ')">' . $escapedName . '</span>'
                                . '<button wire:click="setDefault(' . $record->id . ', ' . $id . ')" class="text-[10px] text-gray-500 hover:text-primary-600 hover:underline cursor-pointer">' . $setDefaultLabel . '</button>'
                                . '</div>';
                        }
                        return new HtmlString('<div class="flex flex-wrap gap-2">' . implode('', $buttons) . '</div>');
                    }),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('dismiss')
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

        if (!$review) {
            Notification::make()
                ->title(__('menu.review_already_processed'))
                ->warning()
                ->send();
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

        if (!$review || !$review->categoryMapping) {
            Notification::make()
                ->title(__('menu.review_already_processed'))
                ->warning()
                ->send();
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
            $mapping = $review->categoryMapping;
            $oldCategoryId = (int) $mapping->category_id;
            $alternatives = array_map('intval', $mapping->alternative_category_ids ?? []);

            // Move old default to alternatives
            if (!in_array($oldCategoryId, $alternatives, true)) {
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

            $firefly = new fireflyIII();
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
