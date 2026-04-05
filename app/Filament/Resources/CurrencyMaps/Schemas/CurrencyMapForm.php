<?php

namespace App\Filament\Resources\CurrencyMaps\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CurrencyMapForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('ISO Code')
                    ->required()
                    ->maxLength(3)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Name')
                    ->required(),
                TagsInput::make('aliases')
                    ->label('Aliases')
                    ->placeholder('Add alias (e.g. ريال)')
                    ->columnSpanFull(),
            ]);
    }
}
