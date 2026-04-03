<?php

namespace App\Filament\Resources\Alerts\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use App\Filament\Pages\EditTransactions;
use Novadaemon\FilamentPrettyJson\Infolist\PrettyJsonEntry;

class AlertInfolist
{
    public static function configure(Schema $schema): Schema
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label(__('widget.title'))
                    ->weight('bold'),
                TextEntry::make('message')
                    ->label(__('widget.message'))
                    ->weight('bold')
                    ->html()
                    ->formatStateUsing(fn ($state) => nl2br(e($state)))
                    ->columnSpanFull(),
                
                PrettyJsonEntry::make('data')->columnSpanFull()->extraAttributes([
                        'style' => 'word-wrap: break-word; white-space: pre-wrap;',
                    ]),
                // PrettyJsonField::make('data')
                //     ->label(__('widget.data'))
                //     // ->html()
                //     ->getStateUsing(fn ($record) => is_array($record->data) ? json_encode($record->data) : ($record->data))
                //     ->columnSpanFull(),
                TextEntry::make('transaction_journal_id')
                    ->label('_')
                    ->weight('bold')
                    ->numeric()
                    ->formatStateUsing(fn ($record) => $record['transaction_journal_id'] ? __('widget.view_transaction') : '-')
                    ->placeholder('-')
                    ->url(fn ($record) => $record['transaction_journal_id'] ? EditTransactions::getUrl([
                        'transactionId' => $record['transaction_journal_id']
                    ]) : null)
                    ->columnSpanFull(),

                    TextEntry::make('created_at')
                    ->label(__('widget.created_at'))
                    ->weight('bold')
                    ->dateTime()
                    ->placeholder('-')->columnSpanFull(),
            ]);
    }

    private static function renderArray(array $data, int $indent = 0): string
    {
        $html = '';
        $pad = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $indent);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $html .= $pad . '<strong>' . e($key) . ':</strong><br>' . self::renderArray($value, $indent + 1);
            } else {
                $html .= $pad . '<strong>' . e($key) . ':</strong> ' . e($value) . '<br>';
            }
        }
        return $html;
    }
}
