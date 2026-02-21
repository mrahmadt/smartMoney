<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Services\fireflyIII;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\EditTransactions;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class ListTransactions extends Page implements HasTable
{
    use InteractsWithTable;
    protected string $view = 'filament.pages.list-transactions';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
protected static ?int $navigationSort = 2;

protected function getTransactions(): array
    {
        $start = date('Y-m-01');
        $end = date('Y-m-t');
        $budget_id = 1;

        $firefly = new fireflyIII();
        $filter = [];
        $budget_id = Auth::user()->budget_id;
        if ($budget_id != null) {
            $filter['budget_id'] = $budget_id;
        }
        $allTransactions = [];
        $transactions = [];

            $output = $firefly->getTransactions(start: $start, end: $end, filter: $filter, limit: 50, page: 1);
            if(empty($output)){ return []; }
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

            foreach ($transactions as $transaction) {
                $allTransactions[] = [
                    'transaction_journal_id' => $transaction->transaction_journal_id ?? '',
                    'type' => $transaction->type,
                    'date' => $transaction->date,
                    'currency_code' => $transaction->currency_code,
                    'amount' => $transaction->amount,
                    'source_name' => $transaction->source_name,
                    'destination_name' => $transaction->destination_name,
                    'notes' => $transaction->notes,
                    'tags' => $transaction->tags,
                    'budget_name' => $transaction->budget_name,
                    'subscription_name' => $transaction->subscription_name,
                    'category_name' => $transaction->category_name,
                    'description' => $transaction->description ?? '',
                    ];
            }

        return $allTransactions;
    }
    public function table(Table $table): Table
    {
        $rows = $this->getTransactions();
        return $table
            ->records(fn() => collect($rows))
                    ->recordUrl(fn ($record) => EditTransactions::getUrl([
            'transactionId' => $record['transaction_journal_id']
        ]))

            ->columns([
                Tables\Columns\TextColumn::make('destination_name')->label('Merchant'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(function ($state, $record) {
                        $amount = ($record['type'] === 'withdrawal' || $record['type'] === 'transfer') 
                            ? $state * -1 
                            : $state;
                        return number_format($amount, 0, '.', ',');
                    })
                    ->color(fn($record) => ($record['type'] === 'withdrawal' || $record['type'] === 'transfer') ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('date')->label('Date')->formatStateUsing(fn($state) => date('D M-d g:ia', strtotime($state)))->color('primary'),
                Tables\Columns\TextColumn::make('category_name')->label('Category'),
            ])
            ;
    }
}
