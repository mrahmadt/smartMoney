<?php

namespace App\Filament\Resources\CategoryMappings;

use App\Filament\Resources\CategoryMappings\Pages\CreateCategoryMapping;
use App\Filament\Resources\CategoryMappings\Pages\EditCategoryMapping;
use App\Filament\Resources\CategoryMappings\Pages\ListCategoryMappings;
use App\Models\Category;
use App\Models\CategoryMapping;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CategoryMappingResource extends Resource
{
    protected static ?string $model = CategoryMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 15;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.config');
    }

    public static function getModelLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.category_mapping');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.category_mappings');
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
                TextInput::make('account_name')
                    ->label(__('menu.merchant_name'))
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('category_id')
                    ->label(__('widget.category'))
                    ->options(Category::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return $table
            ->columns([
                TextColumn::make('account_name')
                    ->label(__('menu.merchant_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('widget.category'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('updated_at')
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
            'index' => ListCategoryMappings::route('/'),
            'create' => CreateCategoryMapping::route('/create'),
            'edit' => EditCategoryMapping::route('/{record}/edit'),
        ];
    }
}
