<?php

namespace App\Filament\Resources\SMS\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                    ->rows(10),
                                Toggle::make('is_valid')
                    ->required(),
                Toggle::make('is_processed')
                    ->required(),

                PrettyJsonField::make('content')->columnSpanFull(),
                PrettyJsonField::make('errors')->columnSpanFull(),
            ]);
    }
}
