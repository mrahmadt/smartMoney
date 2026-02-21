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
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Enums\VerticalAlignment;
use Novadaemon\FilamentPrettyJson\PrettyJson;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class SMSResource extends Resource
{
    protected static ?string $model = SMS::class;
    protected static ?string $navigationGroup = 'SMS';

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $modelLabel = 'SMS';
    protected static ?int $navigationSort = 1;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sender')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_valid')
                    ->label('Valid')
                    ->required(),
                Forms\Components\Toggle::make('is_processed')
                    ->label('Processed')
                    ->required(),
                PrettyJson::make('content')
                ->columnSpanFull()
                // ->formatStateUsing(fn (Model $record): string => nl2br($record->content))
                ,
                PrettyJson::make('errors')
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sender')
                    ->searchable()
                    ->sortable()
                    ->verticalAlignment(VerticalAlignment::Start)
                    ,
                Tables\Columns\TextColumn::make('message')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->wrap()
                    ->width('30%')
                    ->html()
                    ->formatStateUsing(fn (Model $record): string => nl2br($record->message))
                    ->toggleable(isToggledHiddenByDefault: false),
                    Tables\Columns\IconColumn::make('is_valid')
                    ->boolean()
                    ->label('Valid')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_processed')
                    ->boolean()->verticalAlignment(VerticalAlignment::Start)
                    ->label('Processed')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('errors')
                    ->getStateUsing(fn (Model $record): bool => is_null($record->errors) ? false: true)
                    ->boolean()
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->label('Errors')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_valid')
                ->label('Valid')
                ->options([
                    '1' => 'Yes',
                    '0' => 'No',
                ]),
                SelectFilter::make('is_processed')
                ->label('Processed')
                ->options([
                    '1' => 'Yes',
                    '0' => 'No',
                ]),
                TernaryFilter::make('errors')
                ->label('Errors')
                ->placeholder('All')
                ->trueLabel('With errors')
                ->falseLabel('Without errors')
                ->queries(
                    true: fn (Builder $query) => $query->whereNotNull('errors'),
                    false: fn (Builder $query) => $query->whereNull('errors'),
                    blank: fn (Builder $query) => $query,
                )
                ,
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('created_at', 'DESC'))
            ->persistSortInSession()
            ;
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
            // 'create' => Pages\CreateSMS::route('/create'),
            // 'edit' => Pages\EditSMS::route('/{record}/edit'),
            'view' => Pages\ViewSMS::route('/{record}'),
        ];
    }
}
