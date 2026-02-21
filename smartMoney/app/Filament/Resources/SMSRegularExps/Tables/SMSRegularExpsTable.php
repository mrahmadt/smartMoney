<?php

namespace App\Filament\Resources\SMSRegularExps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SMSRegularExpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
TextColumn::make('sender.sender')
    ->label('Sender')
    ->searchable()
    ->sortable(),
                    TextColumn::make('transactionType')
                    ->searchable(),
                TextColumn::make('regularExpMD5')
                ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('stripNewLines')
                    ->boolean(),
                TextColumn::make('createdBy')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('is_validTransaction')
                    ->boolean(),
                IconColumn::make('is_validRegularExp')
                    ->boolean(),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
