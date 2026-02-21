<?php

namespace App\Filament\Resources\Alerts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->weight(fn ($record) => $record->is_read == 0 ? 'bold' : 'normal'),
                TextColumn::make('message')
                    ->limit(150)
                    ->wrap()
    ->formatStateUsing(fn ($state) => nl2br(e($state)))
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->weight(fn ($record) => $record->is_read == 0 ? 'bold' : 'normal'),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
