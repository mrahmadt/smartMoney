<?php

namespace App\Filament\Resources\Alerts\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use App\Filament\Pages\EditTransactions;
use Novadaemon\FilamentPrettyJson\Form\PrettyJsonField;

class AlertInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Title')
                    ->weight('bold'),
                TextEntry::make('message')
                    ->label('Message')
                    ->weight('bold')
                    ->html()
                    ->formatStateUsing(fn ($state) => nl2br(e($state)))
                    ->columnSpanFull(),
                
                // PrettyJsonField::make('data')->columnSpanFull(),
                TextEntry::make('data')
                    ->label('Data')
                    ->columnSpanFull(),
                TextEntry::make('transaction_journal_id')
                    ->label('_')
                    ->weight('bold')
                    ->numeric()
                    ->formatStateUsing(fn ($record) => $record['transaction_journal_id'] ? 'View Transaction' : '-')
                    ->placeholder('-')
                    ->url(fn ($record) => $record['transaction_journal_id'] ? EditTransactions::getUrl([
                        'transactionId' => $record['transaction_journal_id']
                    ]) : null)
                    ->columnSpanFull(),

                    TextEntry::make('created_at')
                    ->label('Created At')
                    ->weight('bold')
                    ->dateTime()
                    ->placeholder('-')->columnSpanFull(),
            ]);
    }
}
