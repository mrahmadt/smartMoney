<?php

namespace App\Filament\Widgets;

use Filament\Actions\BulkActionGroup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use App\Services\fireflyIII;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\EditTransactions;

class TopTransactions extends TableWidget
{
    protected function getTopTransactions(): array
    {
        $cacheKey = 'top_transactions_' . Auth::id();
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


        $allTransactions = [];
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
                $allTransactions[] = ['transaction_journal_id' => $transaction->transaction_journal_id, 'description' => $transaction->description ?? '', 'date' => $transaction->date ?? '', 'destination_name' => $transaction->destination_name ?? 'Unknown', 'Category' => $category_name, 'Amount' => $transaction->amount];
            }

        usort($allTransactions, fn($a, $b) => $b['Amount'] <=> $a['Amount']);
        $allTransactions = array_slice($allTransactions, 0, $limit , true);
        cache()->put($cacheKey, $allTransactions, now()->addHour());
        return $allTransactions;
    }

    public function table(Table $table): Table
    {
        $rows = $this->getTopTransactions();
        return $table
            ->records(fn() => collect($rows))
            ->recordUrl(fn ($record) => EditTransactions::getUrl([
            'transactionId' => $record['transaction_journal_id']
        ]))
            ->columns([
                Tables\Columns\TextColumn::make('destination_name')->label('Merchant')->description(fn($record) => date('D M-d g:ia', strtotime($record['date']))),
                Tables\Columns\TextColumn::make('Amount')->label('Amount')->formatStateUsing(fn($state) => number_format(($state*-1), 0, '.', ','))->color('danger'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
