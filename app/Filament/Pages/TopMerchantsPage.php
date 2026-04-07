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

class TopMerchantsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.list-transactions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return __('menu.top_merchants');
    }

    public function getTitle(): string
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return __('menu.top_merchants');
    }

    protected function getTopMerchants(): array
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        $limit = 30;
        $transactions = TransactionCache::getMonthlyTransactions();
        $categoryMap = Category::translationMap();

        $merchants = [];
        foreach ($transactions as $transaction) {
            $destination_name = $transaction->destination_name ?? __('widget.unknown');
            if (! isset($merchants[$destination_name])) {
                $rawCat = $transaction->category_name ?? null;
                $merchants[$destination_name] = [
                    'destination_name' => $destination_name,
                    'Category' => $rawCat ? ($categoryMap[$rawCat] ?? $rawCat) : null,
                    'Amount' => 0,
                    'count' => 0,
                ];
            }
            $merchants[$destination_name]['Amount'] += $transaction->amount;
            $merchants[$destination_name]['count']++;
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
                Tables\Columns\TextColumn::make('destination_name')
                    ->label(__('widget.merchant'))
                    ->description(fn ($record) => $record['Category']),
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
