<?php

namespace App\Filament\Resources\SMS;

use App\Filament\Resources\SMS\Pages\CreateSMS;
use App\Filament\Resources\SMS\Pages\EditSMS;
use App\Filament\Resources\SMS\Pages\ListSMS;
use App\Filament\Resources\SMS\Schemas\SMSForm;
use App\Filament\Resources\SMS\Tables\SMSTable;
use App\Models\SMS;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SMSResource extends Resource
{
    protected static ?string $model = SMS::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'SMS';
protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return SMSForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SMSTable::configure($table);
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
            'index' => ListSMS::route('/'),
            'create' => CreateSMS::route('/create'),
            'edit' => EditSMS::route('/{record}/edit'),
        ];
    }

        public static function canAccess(): bool
{
    return auth()->user()->id == 1;
}

}
