<?php

namespace App\Filament\Resources\SMS\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SMSTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sender')
                                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('message')
                    // ->limit(50)
                    ->wrap()
                    ->searchable(),
                IconColumn::make('is_valid')
                    ->boolean(),
                IconColumn::make('is_processed')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_valid')
                    ->label('Valid'),
                TernaryFilter::make('is_processed')
                    ->label('Processed'),
                SelectFilter::make('sender')
                    ->options(fn () => \App\Models\SMS::query()->distinct()->pluck('sender', 'sender')->toArray()),
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
}
