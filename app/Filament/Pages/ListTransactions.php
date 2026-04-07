<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Category;
use App\Services\fireflyIII;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ListTransactions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.list-transactions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return __('menu.transactions');
    }

    public function getTitle(): string
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return __('menu.transactions');
    }

    protected function getTransactions(): array
    {
        $start = date('Y-m-d', strtotime('-30 days'));
        $end = date('Y-m-d');

        $firefly = new fireflyIII;
        $filter = Account::getTransactionFilter();
        $allTransactions = [];
        $transactions = [];

        $output = $firefly->getTransactions(start: $start, end: $end, filter: $filter, limit: 50, page: 1);
        if (empty($output)) {
            return [];
        }
        if (isset($output->data)) {
            foreach ($output->data as $transaction) {
                if (isset($transaction->attributes->transactions)) {
                    $transactions = array_merge($transactions, $transaction->attributes->transactions);
                } else {
                    $transactions[] = $transaction;
                }
            }
        } else {
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
        app()->setLocale(Auth::user()->language ?? 'en');
        $rows = $this->getTransactions();

        return $table
            ->records(fn () => collect($rows))
            ->recordUrl(fn ($record) => EditTransactions::getUrl([
                'transactionId' => $record['transaction_journal_id'],
            ]))

            ->columns([
                TextColumn::make('destination_name')->label(__('widget.merchant')),
                TextColumn::make('amount')
                    ->label(__('widget.amount'))
                    ->formatStateUsing(function ($state, $record) {
                        $amount = ($record['type'] === 'withdrawal' || $record['type'] === 'transfer')
                            ? $state * -1
                            : $state;

                        return number_format($amount, 0, '.', ',');
                    })
                    ->color(fn ($record) => ($record['type'] === 'withdrawal' || $record['type'] === 'transfer') ? 'danger' : 'success'),
                TextColumn::make('date')->label(__('widget.date'))->formatStateUsing(fn ($state) => date('D M-d g:ia', strtotime($state)))->color('primary'),
                TextColumn::make('category_name')->label(__('widget.category'))
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return __('widget.uncategorized');
                        }
                        static $map;
                        $map ??= Category::translationMap();

                        return $map[$state] ?? $state;
                    }),
            ]);
    }
}
