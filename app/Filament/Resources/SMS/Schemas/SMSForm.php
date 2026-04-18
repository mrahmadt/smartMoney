<?php

namespace App\Filament\Resources\SMS\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Novadaemon\FilamentPrettyJson\Form\PrettyJsonField;

class SMSForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sender')
                    ->required(),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull()
                    ->rows(10)
                    ->extraInputAttributes(function ($record) {
                        if ($record && preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{0590}-\x{05FF}]/u', $record->message)) {
                            return ['style' => 'direction: rtl; text-align: right;'];
                        }

                        return [];
                    }),
                Toggle::make('is_valid')
                    ->required(),
                Toggle::make('is_processed')
                    ->required(),

                PrettyJsonField::make('content')->columnSpanFull()
                    ->extraAttributes([
                        'style' => 'word-wrap: break-word; white-space: pre-wrap;',
                    ]),
                PrettyJsonField::make('errors')
                    ->columnSpanFull()
                    ->extraAttributes([
                        'style' => 'word-wrap: break-word; white-space: pre-wrap;',
                    ]),
            ]);
    }
}
