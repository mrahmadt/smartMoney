<?php

namespace App\Filament\Widgets;

use Filament\Actions\BulkActionGroup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use App\Services\fireflyIII;
use Illuminate\Support\Facades\Auth;

class TopCategories extends TableWidget
{
    protected function getTopCategories(): array
    {
                $cacheKey = 'top_categories_' . Auth::id();
        $cachedData = cache()->get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $start = date('Y-m-01');
        $end = date('Y-m-t');
        $budget_id = 1;

        $firefly = new fireflyIII();
        $limit = 5;
        $filter = [];
        $budget_id = Auth::user()->budget_id;
        if ($budget_id != null) {
            $filter['budget_id'] = $budget_id;
        }

        $categories = [];
        $transactions = [];
        for ($page = 1; $page <= 10; $page++) {
            $output = $firefly->getTransactions(start: $start, end: $end, filter: $filter, limit: 200, page: $page);
            if(empty($output)){ break; }
            if(isset($output->data)){
                foreach($output->data as $transaction){
                    if(isset($transaction->attributes->transactions)){
                        $transactions = array_merge($transactions, $transaction->attributes->transactions);
                    }else{
                        $transactions[] = $transaction;
                    }
                }
            }else{
                $transactions = array_merge($transactions, $output);
            }
        }

            foreach ($transactions as $transaction) {
                $category_name = $transaction->category_name ?? 'Uncategorized';
                if (!isset($categories[$category_name])) {
                    $categories[$category_name] = ['Category' => $category_name, 'Amount' => 0];
                }
                $categories[$category_name]['Amount'] += abs($transaction->amount);
            }
        usort($categories, fn($a, $b) => $b['Amount'] <=> $a['Amount']);
        if(count($categories) > $limit){
            $otherCategories = array_slice($categories, $limit, null, true);
            $categories = array_slice($categories, 0, $limit , true);
            $categories['Other'] = ['Category' => 'Other', 'Amount' => array_sum(array_column($otherCategories, 'Amount'))];
        }
        cache()->put($cacheKey, $categories, now()->addHour());
        return $categories;
    }

    public function table(Table $table): Table
    {
        $rows = $this->getTopCategories();

        return $table
            ->records(fn() => collect($rows))
            ->columns([
                Tables\Columns\TextColumn::make('Category')->label('Category'),
                Tables\Columns\TextColumn::make('Amount')->label('Amount')->formatStateUsing(fn($state) => number_format(($state*-1), 0, '.', ','))->color('danger'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
