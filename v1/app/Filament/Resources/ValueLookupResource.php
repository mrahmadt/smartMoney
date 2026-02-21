<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValueLookupResource\Pages;
use App\Filament\Resources\ValueLookupResource\RelationManagers;
use App\Models\SMS\ValueLookup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ValueLookupResource extends Resource
{
    protected static ?string $model = ValueLookup::class;
    protected static ?string $navigationGroup = 'SMS';
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-vertical';
    protected static ?string $modelLabel = 'Value Lookup';
    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('replaceWith')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->searchable(),
                Tables\Columns\TextColumn::make('replaceWith')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListValueLookups::route('/'),
            'create' => Pages\CreateValueLookup::route('/create'),
            'edit' => Pages\EditValueLookup::route('/{record}/edit'),
        ];
    }
}
