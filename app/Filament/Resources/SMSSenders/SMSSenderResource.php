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
use Illuminate\Support\Facades\Auth;

class SMSSenderResource extends Resource
{
    protected static ?string $model = SMSSender::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'SMSSender';
    protected static ?int $navigationSort = 14;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.config');
    }

    public static function getModelLabel(): string
    {
        return __('menu.sms_sender');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.sms_senders');
    }

    public static function canAccess(): bool
    {
        return Auth::id() === 1;
    }

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
}
