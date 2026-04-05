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
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use App\Ai\Agents\SuggestAlternativeCategories;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

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
                    ->options(fn () => Category::orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->id => $c->translatedName()]))
                    ->searchable()
                    ->required(),
                Select::make('alternative_category_ids')
                    ->label(__('menu.alternative_categories'))
                    ->multiple()
                    ->searchable()
                    ->options(fn () => Category::orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->id => $c->translatedName()]))
                    ->helperText(__('menu.alternative_categories_hint')),
                Actions::make([
                    Action::make('suggestAlternatives')
                        ->label(__('menu.suggest_alternatives'))
                        ->icon('heroicon-o-sparkles')
                        ->color('gray')
                        ->visible(fn ($get) => $get('category_id') !== null)
                        ->action(function ($get, $set, $livewire) {
                            $categoryId = $get('category_id');
                            if (!$categoryId) {
                                Notification::make()
                                    ->title(__('menu.select_category_first'))
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $category = Category::find($categoryId);
                            if (!$category) return;

                            $storeName = $get('account_name') ?? '';

                            $exampleStores = CategoryMapping::where('category_id', $categoryId)
                                ->where('account_name', '!=', $storeName)
                                ->limit(10)
                                ->pluck('account_name')
                                ->toArray();

                            $allCategories = Category::where('id', '!=', $categoryId)
                                ->orderBy('name')
                                ->pluck('name')
                                ->toArray();

                            if (empty($allCategories)) {
                                Notification::make()
                                    ->title(__('menu.no_categories_available'))
                                    ->warning()
                                    ->send();
                                return;
                            }

                            try {
                                $agent = new SuggestAlternativeCategories();
                                $agent->categoryName = $category->name;
                                $agent->storeName = $storeName;
                                $agent->exampleStores = $exampleStores;
                                $agent->allCategories = $allCategories;

                                $model = Setting::get('parsesms_category_model');
                                $response = $agent->prompt(
                                    "Suggest alternative categories for store: {$storeName}",
                                    model: $model
                                );
                                Log::debug('LLM suggestAlternatives response', ['text' => $response->text]);

                                $output = json_decode($response->text, true);
                                if (json_last_error() !== JSON_ERROR_NONE || !isset($output['categories'])) {
                                    Notification::make()
                                        ->title(__('menu.no_alternatives_suggested'))
                                        ->info()
                                        ->send();
                                    return;
                                }

                                $suggestedNames = array_filter(array_map('trim', explode(',', $output['categories'])));
                                $suggestedIds = Category::whereIn('name', $suggestedNames)->pluck('id')->toArray();

                                if (empty($suggestedIds)) {
                                    Notification::make()
                                        ->title(__('menu.no_alternatives_suggested'))
                                        ->info()
                                        ->send();
                                    return;
                                }

                                $current = $get('alternative_category_ids') ?? [];
                                $merged = array_values(array_unique(array_merge($current, $suggestedIds)));
                                $set('alternative_category_ids', $merged);

                                $names = implode(', ', $suggestedNames);
                                Notification::make()
                                    ->title(__('menu.alternatives_suggested'))
                                    ->body($names)
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Log::debug('LLM suggestAlternatives error', ['error' => $e->getMessage()]);
                                Notification::make()
                                    ->title(__('menu.ai_error'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
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
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->category?->translatedName()),
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
