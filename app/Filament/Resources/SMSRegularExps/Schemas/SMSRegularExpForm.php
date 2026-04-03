<?php

namespace App\Filament\Resources\SMSRegularExps\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class SMSRegularExpForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
Select::make('sender_id')
    ->label('Sender')
    ->relationship('sender', 'sender')
    ->searchable()
    ->preload()
    ->required(),                TextInput::make('transactionType')
                    ->required(),
                Textarea::make('regularExp')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('regularExpMD5')
                    ->required(),
                Toggle::make('stripNewLines')
                    ->required(),
                TextInput::make('createdBy'),
                TextInput::make('data'),
                Toggle::make('is_active')
                    ->required()->default(true),
                Toggle::make('is_validTransaction')
                    ->required()->default(true),
                Toggle::make('is_validRegularExp')
                    ->required()->default(true),
            ]);
    }
}
