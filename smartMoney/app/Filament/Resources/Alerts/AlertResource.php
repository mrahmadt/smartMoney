<?php

namespace App\Filament\Resources\Alerts;

use App\Filament\Resources\Alerts\Pages\CreateAlert;
use App\Filament\Resources\Alerts\Pages\EditAlert;
use App\Filament\Resources\Alerts\Pages\ListAlerts;
use App\Filament\Resources\Alerts\Pages\ViewAlert;
use App\Filament\Resources\Alerts\Schemas\AlertForm;
use App\Filament\Resources\Alerts\Schemas\AlertInfolist;
use App\Filament\Resources\Alerts\Tables\AlertsTable;
use App\Models\Alert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static ?string $recordTitleAttribute = 'Alert';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.alerts');
    }

    public static function getModelLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.alert');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.alerts');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_read', false)->where('user_id', auth()->id())->count();
        return $count > 0 ? (string) $count : null;
    }


    public static function form(Schema $schema): Schema
    {
        return AlertForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        // update is_read to 1 when viewing the alert
        $schema->getRecord()->update([
            'is_read' => 1,
        ]);
        return AlertInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AlertsTable::configure($table);
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
            'index' => ListAlerts::route('/'),
            'view' => ViewAlert::route('/{record}'),
            'edit' => EditAlert::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
