<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\EditTransactions;
use App\Models\Category;
use App\Services\TransactionCache;
use Filament\Actions\BulkActionGroup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class RecentTransactions extends TableWidget
{
    protected function getTableHeading(): string
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return __('widget.recent_transactions');
    }

    protected function getRecentTransactions(): array
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        $limit = 5;
        $transactions = TransactionCache::getMonthlyTransactions();
        $categoryMap = Category::translationMap();

        $allTransactions = [];
        foreach ($transactions as $transaction) {
            $rawName = $transaction->category_name ?? null;
            $category_name = $rawName ? ($categoryMap[$rawName] ?? $rawName) : __('widget.uncategorized');
            $allTransactions[] = [
                'transaction_journal_id' => $transaction->transaction_journal_id,
                'description' => $transaction->description ?? '',
                'date' => $transaction->date ?? '',
                'destination_name' => $transaction->destination_name ?? __('widget.unknown'),
                'Category' => $category_name,
                'Amount' => $transaction->amount,
                'type' => $transaction->type ?? 'withdrawal',
            ];
        }

        usort($allTransactions, fn ($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));

        return array_slice($allTransactions, 0, $limit, true);
    }

    public function table(Table $table): Table
    {
        $rows = $this->getRecentTransactions();

        return $table
            ->records(fn () => collect($rows))
            ->recordUrl(fn ($record) => EditTransactions::getUrl([
                'transactionId' => $record['transaction_journal_id'],
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('destination_name')->label(__('widget.merchant'))->description(fn ($record) => date('D M-d g:ia', strtotime($record['date']))),
                Tables\Columns\TextColumn::make('Amount')->label(__('widget.amount'))->formatStateUsing(fn ($state, $record) => number_format(($record['type'] === 'withdrawal' ? $state * -1 : $state), 0, '.', ','))->color(fn ($record) => $record['type'] === 'withdrawal' ? 'danger' : 'success'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
