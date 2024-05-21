<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SMSResource\Pages;
use App\Filament\Resources\SMSResource\RelationManagers;
use App\Models\SMS;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SMSResource extends Resource
{
    protected static ?string $model = SMS::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sender')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('content'),
                Forms\Components\Toggle::make('is_valid')
                    ->required(),
                Forms\Components\Toggle::make('is_processed')
                    ->required(),
                Forms\Components\TextInput::make('errors'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_valid')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_processed')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListSMS::route('/'),
            'create' => Pages\CreateSMS::route('/create'),
            'edit' => Pages\EditSMS::route('/{record}/edit'),
        ];
    }
}
