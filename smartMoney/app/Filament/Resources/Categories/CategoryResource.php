<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 14;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.config');
    }

    public static function getModelLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.category');
    }

    public static function getPluralModelLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.categories');
    }

    public static function canAccess(): bool
    {
        return Auth::id() === 1;
    }

    public static function form(Schema $schema): Schema
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('menu.name'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
                Toggle::make('enable_prompt')
                    ->label(__('menu.enable_prompt'))
                    ->default(true)
                    ->live(),
                Textarea::make('category_prompt')
                    ->label(__('menu.category_prompt'))
                    ->hint(__('menu.category_prompt_hint'))
                    ->rows(1)
                    ->maxLength(200)
                    ->columnSpanFull()
                    ->visible(fn ($get) => $get('enable_prompt')),
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
                TextColumn::make('mappings_count')
                    ->label(__('menu.category_mappings'))
                    ->counts('mappings')
                    ->sortable(),
                TextColumn::make('enable_prompt')
                    ->label(__('menu.enable_prompt'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('menu.yes') : __('menu.no'))
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('widget.date'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
