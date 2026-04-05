<?php

namespace App\Filament\Resources\CurrencyMaps;

use App\Filament\Resources\CurrencyMaps\Pages\CreateCurrencyMap;
use App\Filament\Resources\CurrencyMaps\Pages\EditCurrencyMap;
use App\Filament\Resources\CurrencyMaps\Pages\ListCurrencyMaps;
use App\Filament\Resources\CurrencyMaps\Schemas\CurrencyMapForm;
use App\Filament\Resources\CurrencyMaps\Tables\CurrencyMapsTable;
use App\Models\CurrencyMap;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CurrencyMapResource extends Resource
{
    protected static ?string $model = CurrencyMap::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?int $navigationSort = 16;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.config');
    }

    public static function getModelLabel(): string
    {
        return 'Currency Map';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Currency Maps';
    }

    public static function canAccess(): bool
    {
        return Auth::id() === 1;
    }

    public static function form(Schema $schema): Schema
    {
        return CurrencyMapForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CurrencyMapsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurrencyMaps::route('/'),
            'create' => CreateCurrencyMap::route('/create'),
            'edit' => EditCurrencyMap::route('/{record}/edit'),
        ];
    }
}
