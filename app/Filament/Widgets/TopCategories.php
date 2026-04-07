<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Services\TransactionCache;
use Filament\Actions\BulkActionGroup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class TopCategories extends TableWidget
{
    protected function getTableHeading(): string
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return __('widget.top_categories');
    }

    protected function getTopCategories(): array
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        $limit = 5;
        $transactions = TransactionCache::getMonthlyTransactions();
        $categoryMap = Category::translationMap();

        $categories = [];
        foreach ($transactions as $transaction) {
            $rawName = $transaction->category_name ?? null;
            $category_name = $rawName ? ($categoryMap[$rawName] ?? $rawName) : __('widget.uncategorized');
            if (! isset($categories[$category_name])) {
                $categories[$category_name] = ['Category' => $category_name, 'Amount' => 0];
            }
            $categories[$category_name]['Amount'] += abs($transaction->amount);
        }
        usort($categories, fn ($a, $b) => $b['Amount'] <=> $a['Amount']);
        if (count($categories) > $limit) {
            $otherCategories = array_slice($categories, $limit, null, true);
            $categories = array_slice($categories, 0, $limit, true);
            $categories['Other'] = ['Category' => __('widget.other'), 'Amount' => array_sum(array_column($otherCategories, 'Amount'))];
        }

        return $categories;
    }

    public function table(Table $table): Table
    {
        $rows = $this->getTopCategories();

        return $table
            ->records(fn () => collect($rows))
            ->columns([
                Tables\Columns\TextColumn::make('Category')->label(__('widget.category')),
                Tables\Columns\TextColumn::make('Amount')->label(__('widget.amount'))->formatStateUsing(fn ($state) => number_format(($state * -1), 0, '.', ','))->color('danger'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
