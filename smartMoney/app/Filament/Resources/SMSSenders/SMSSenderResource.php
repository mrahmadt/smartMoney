<?php

namespace App\Filament\Resources\SMSSenders;

use App\Filament\Resources\SMSSenders\Pages\CreateSMSSender;
use App\Filament\Resources\SMSSenders\Pages\EditSMSSender;
use App\Filament\Resources\SMSSenders\Pages\ListSMSSenders;
use App\Filament\Resources\SMSSenders\Schemas\SMSSenderForm;
use App\Filament\Resources\SMSSenders\Tables\SMSSendersTable;
use App\Models\SMSSender;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SMSSenderResource extends Resource
{
    protected static ?string $model = SMSSender::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'SMSSender';
protected static ?int $navigationSort = 14;

    public static function form(Schema $schema): Schema
    {
        return SMSSenderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SMSSendersTable::configure($table);
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
            'index' => ListSMSSenders::route('/'),
            'create' => CreateSMSSender::route('/create'),
            'edit' => EditSMSSender::route('/{record}/edit'),
        ];
    }
        public static function canAccess(): bool
{
    return auth()->user()->id == 1;
}
}
