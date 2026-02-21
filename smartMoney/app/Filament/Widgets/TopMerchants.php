<?php

namespace App\Filament\Widgets;

use Filament\Actions\BulkActionGroup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use App\Services\fireflyIII;
use Illuminate\Support\Facades\Auth;

class TopMerchants extends TableWidget
{
    protected function getTopMerchants(): array
    {
                $cacheKey = 'top_merchants_' . Auth::id();
        $cachedData = cache()->get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        $start = date('Y-m-01');
        $end = date('Y-m-t');
        $budget_id = 1;

        $firefly = new fireflyIII();
        $limit = 10;

        $filter = [];
        $budget_id = Auth::user()->budget_id;
        if ($budget_id != null) {
            $filter['budget_id'] = $budget_id;
        }
        $merchants = [];
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
                    $destination_name = $transaction->destination_name ?? 'Unknown';
                    if (!isset($merchants[$destination_name])) {
                        $merchants[$destination_name] = ['destination_name' => $destination_name, 'Category' => $transaction->category_name ?? null, 'Amount' => 0];
                    }
                    $merchants[$destination_name]['Amount'] += ($transaction->amount);
            }
        usort($merchants, fn($a, $b) => $b['Amount'] <=> $a['Amount']);
        $merchants = array_slice($merchants, 0, $limit, true);
        cache()->put($cacheKey, $merchants, now()->addHour());
        return $merchants;
    }

    public function table(Table $table): Table
    {
        $rows = $this->getTopMerchants();
        return $table
            ->records(fn() => collect($rows))
            ->columns([
                Tables\Columns\TextColumn::make('destination_name')->label('Merchant')->description(fn($record) => $record['Category']),
                Tables\Columns\TextColumn::make('Amount')->label('Amount')->formatStateUsing(fn($state) => number_format(($state * -1), 0, '.', ','))->color('danger'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
