<?php

namespace App\Filament\Resources\Accounts;

use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Models\Account;
use App\Models\SMSSender;
use App\Models\User;
use App\Services\fireflyIII;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Illuminate\Support\Facades\Auth;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.config');
    }

    public static function getModelLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.account');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.accounts');
    }

    public static function canAccess(): bool
    {
        return Auth::id() === 1;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        $firefly = new fireflyIII();
        $budgets = [];
        $budgetResponse = $firefly->getBudgets();
        if ($budgetResponse && isset($budgetResponse->data)) {
            foreach ($budgetResponse->data as $budget) {
                $budgets[$budget->id] = $budget->attributes->name;
            }
        }

        return $schema
            ->components([
                TextInput::make('firefly_account_name')
                    ->label(__('menu.firefly_account_name'))
                    ->disabled(),
                TextInput::make('currency_code')
                    ->label(__('menu.currency_code'))
                    ->disabled(),
                Select::make('user_id')
                    ->label(__('menu.user'))
                    ->options(User::pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
                Select::make('sender_id')
                    ->label(__('menu.sender'))
                    ->options(SMSSender::pluck('sender', 'id'))
                    ->searchable()
                    ->nullable(),
                Repeater::make('shortcodes')
                    ->label(__('menu.shortcodes'))
                    ->schema([
                        TextInput::make('shortcode')
                            ->label(__('menu.shortcodes'))
                            ->required(),
                        Select::make('budget_id')
                            ->label(__('widget.budget'))
                            ->options($budgets)
                            ->searchable()
                            ->nullable()
                            ->helperText(__('menu.shortcode_budget_hint')),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel(__('menu.add_shortcode'))
                    ->columnSpanFull(),
                Select::make('budget_id')
                    ->label(__('widget.budget'))
                    ->options($budgets)
                    ->searchable()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return $table
            ->columns([
                TextColumn::make('firefly_account_name')
                    ->label(__('menu.firefly_account_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('currency_code')
                    ->label(__('menu.currency_code')),
                TextColumn::make('user.name')
                    ->label(__('menu.user')),
                TextColumn::make('sender.sender')
                    ->label(__('menu.sender')),
                TextColumn::make('shortcodes')
                    ->label(__('menu.shortcodes'))
                    ->formatStateUsing(fn ($record) => implode(', ', $record->getShortcodeList())),
                TextColumn::make('budget_id')
                    ->label(__('widget.budget')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }
}
