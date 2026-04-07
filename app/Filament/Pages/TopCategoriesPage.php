<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SpendingCategoriesChart;
use App\Models\Category;
use App\Services\TransactionCache;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TopCategoriesPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.top-categories';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return __('menu.top_categories');
    }

    public function getTitle(): string
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return __('menu.top_categories');
    }

    protected function getFooterWidgets(): array
    {
        return [
            SpendingCategoriesChart::class,
        ];
    }

    protected function getTopCategories(): array
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        $limit = 30;
        $transactions = TransactionCache::getMonthlyTransactions();
        $categoryMap = Category::translationMap();

        $categories = [];
        foreach ($transactions as $transaction) {
            $rawName = $transaction->category_name ?? null;
            $category_name = $rawName ? ($categoryMap[$rawName] ?? $rawName) : __('widget.uncategorized');
            if (! isset($categories[$category_name])) {
                $categories[$category_name] = ['Category' => $category_name, 'Amount' => 0, 'count' => 0];
            }
            $categories[$category_name]['Amount'] += abs($transaction->amount);
            $categories[$category_name]['count']++;
        }
        usort($categories, fn ($a, $b) => $b['Amount'] <=> $a['Amount']);

        return array_slice($categories, 0, $limit, true);
    }

    public function table(Table $table): Table
    {
        $rows = $this->getTopCategories();

        return $table
            ->records(fn () => collect($rows))
            ->columns([
                Tables\Columns\TextColumn::make('Category')
                    ->label(__('widget.category')),
                Tables\Columns\TextColumn::make('count')
                    ->label(__('widget.count')),
                Tables\Columns\TextColumn::make('Amount')
                    ->label(__('widget.amount'))
                    ->formatStateUsing(fn ($state) => number_format($state * -1, 0, '.', ','))
                    ->color('danger'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
