<?php

namespace App\Filament\Resources\SMSRegularExps;

use App\Filament\Resources\SMSRegularExps\Pages\CreateSMSRegularExp;
use App\Filament\Resources\SMSRegularExps\Pages\EditSMSRegularExp;
use App\Filament\Resources\SMSRegularExps\Pages\ListSMSRegularExps;
use App\Filament\Resources\SMSRegularExps\Schemas\SMSRegularExpForm;
use App\Filament\Resources\SMSRegularExps\Tables\SMSRegularExpsTable;
use App\Models\SMSRegularExp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SMSRegularExpResource extends Resource
{
    protected static ?string $model = SMSRegularExp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'SMSRegularExp';
protected static ?int $navigationSort = 13;

    public static function form(Schema $schema): Schema
    {
        return SMSRegularExpForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SMSRegularExpsTable::configure($table);
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
            'index' => ListSMSRegularExps::route('/'),
            'create' => CreateSMSRegularExp::route('/create'),
            'edit' => EditSMSRegularExp::route('/{record}/edit'),
        ];
    }
        public static function canAccess(): bool
{
    return auth()->user()->id == 1;
}
}
