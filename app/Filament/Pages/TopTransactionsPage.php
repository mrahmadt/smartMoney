<?php

namespace App\Filament\Pages;

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

class TopTransactionsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.list-transactions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return __('menu.top_transactions');
    }

    public function getTitle(): string
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return __('menu.top_transactions');
    }

    protected function getTopTransactions(): array
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        $limit = 50;
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
            ];
        }

        usort($allTransactions, fn ($a, $b) => $b['Amount'] <=> $a['Amount']);

        return array_slice($allTransactions, 0, $limit, true);
    }

    public function table(Table $table): Table
    {
        $rows = $this->getTopTransactions();

        return $table
            ->records(fn () => collect($rows))
            ->recordUrl(fn ($record) => EditTransactions::getUrl([
                'transactionId' => $record['transaction_journal_id'],
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('destination_name')
                    ->label(__('widget.merchant'))
                    ->description(fn ($record) => $record['Category']),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('widget.date'))
                    ->formatStateUsing(fn ($state) => date('D M-d g:ia', strtotime($state)))
                    ->color('primary'),
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
