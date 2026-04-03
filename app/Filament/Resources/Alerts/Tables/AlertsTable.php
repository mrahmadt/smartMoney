<?php

namespace App\Filament\Resources\Alerts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AlertsTable
{
    public static function configure(Table $table): Table
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('widget.title'))
                    ->weight(fn ($record) => $record->is_read == 0 ? 'bold' : 'normal'),
                TextColumn::make('topic')
                    ->label(__('menu.topic'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'transaction' => 'gray',
                        'abnormal' => 'danger',
                        'report' => 'warning',
                        'subscription' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('message')
                    ->label(__('widget.message'))
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
                SelectFilter::make('topic')
                    ->label(__('menu.topic'))
                    ->options([
                        // 'all' => __('menu.topic_all'),
                        'transaction' => __('menu.topic_transaction'),
                        'hide_transaction' => __('menu.topic_hide_transaction'),
                        'abnormal' => __('menu.topic_abnormal'),
                        'report' => __('menu.topic_report'),
                        'subscription' => __('menu.topic_subscription'),
                    ])
                    // ->default('hide_transaction')
                    ->query(function ($query, array $data) {
                        // dd($data['value']);
                        $value = $data['value'] ?? null;
                        if ($value === 'hide_transaction') {
                            $query->where(function ($q) {
                                $q->whereNull('topic')->orWhere('topic', '!=', 'transaction');
                            });
                        } elseif ($value === 'all' || $value == null) {
                            // no filter
                        } else {
                            $query->where('topic', $value);
                        }
                    }),
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
