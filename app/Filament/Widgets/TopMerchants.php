<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Services\TransactionCache;
use Filament\Actions\BulkActionGroup;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class TopMerchants extends TableWidget
{
    protected function getTableHeading(): string
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return __('widget.top_merchants');
    }

    protected function getTopMerchants(): array
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        $limit = 10;
        $transactions = TransactionCache::getMonthlyTransactions();
        $categoryMap = Category::translationMap();

        $merchants = [];
        foreach ($transactions as $transaction) {
            $destination_name = $transaction->destination_name ?? __('widget.unknown');
            if (! isset($merchants[$destination_name])) {
                $rawCat = $transaction->category_name ?? null;
                $merchants[$destination_name] = ['destination_name' => $destination_name, 'Category' => $rawCat ? ($categoryMap[$rawCat] ?? $rawCat) : null, 'Amount' => 0];
            }
            $merchants[$destination_name]['Amount'] += ($transaction->amount);
        }
        usort($merchants, fn ($a, $b) => $b['Amount'] <=> $a['Amount']);

        return array_slice($merchants, 0, $limit, true);
    }

    public function table(Table $table): Table
    {
        $rows = $this->getTopMerchants();

        return $table
            ->records(fn () => collect($rows))
            ->columns([
                Tables\Columns\TextColumn::make('destination_name')->label(__('widget.merchant'))->description(fn ($record) => $record['Category']),
                Tables\Columns\TextColumn::make('Amount')->label(__('widget.amount'))->formatStateUsing(fn ($state) => number_format(($state * -1), 0, '.', ','))->color('danger'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
