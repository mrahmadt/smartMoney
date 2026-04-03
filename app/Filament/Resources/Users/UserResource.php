<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Services\fireflyIII;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 16;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.config');
    }

    public static function getModelLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.users');
    }

    public static function canAccess(): bool
    {
        return Auth::id() === 1;
    }

    public static function form(Schema $schema): Schema
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        $budgets = [];
        $firefly = new fireflyIII();
        $budgetResponse = $firefly->getBudgets(limit: 300);
        if ($budgetResponse && isset($budgetResponse->data)) {
            foreach ($budgetResponse->data as $budget) {
                $budgets[$budget->id] = $budget->attributes->name;
            }
        }

        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('menu.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('menu.email'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('password')
                    ->label(__('menu.password'))
                    ->password()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('budget_id')
                    ->label(__('widget.budget'))
                    ->options($budgets)
                    ->searchable()
                    ->nullable(),
                Select::make('language')
                    ->label(__('menu.language'))
                    ->options([
                        'en' => 'English',
                        'ar' => 'العربية',
                    ])
                    ->default('en'),
                Toggle::make('alert_via_email')
                    ->label(__('menu.alert_via_email')),
                Toggle::make('mfa_required')
                    ->label(__('menu.mfa_required')),
            ]);
    }

    public static function table(Table $table): Table
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('menu.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('menu.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('language')
                    ->label(__('menu.language')),
                IconColumn::make('alert_via_email')
                    ->label(__('menu.alert_via_email'))
                    ->boolean(),
                IconColumn::make('mfa_required')
                    ->label(__('menu.mfa_required'))
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
