<?php

namespace App\Filament\Resources\Keywords;

use App\Filament\Resources\Keywords\Pages\ManageKeywords;
use App\Models\Keyword;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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

class KeywordResource extends Resource
{
    protected static ?string $model = Keyword::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Keyword';
protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('keyword')
                    ->required(),
                Toggle::make('is_regularExp')
                    ->required(),
                TextInput::make('replaceWith'),
                Select::make('keyword_type')
                    ->options([
            'phone' => 'Phone',
            'passcodes' => 'Passcodes',
            'misc' => 'Misc',
            'date' => 'Date',
            'url' => 'Url',
            'ignore' => 'Ignore',
            'breaks' => 'Breaks',
            'replace' => 'Replace',
        ])
                    ->default('ignore')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('channel')
                    ->required()
                    ->default('sms'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Keyword')
            ->columns([
                TextColumn::make('keyword')
                    ->searchable(),
                IconColumn::make('is_regularExp')
                    ->boolean(),
                TextColumn::make('replaceWith')
                    ->searchable(),
                TextColumn::make('keyword_type')
                    ->badge(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('channel')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ManageKeywords::route('/'),
        ];
    }
    public static function canAccess(): bool
{
    return auth()->user()->id == 1;
}
}
