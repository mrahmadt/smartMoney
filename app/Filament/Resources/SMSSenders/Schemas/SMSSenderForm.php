<?php

namespace App\Filament\Resources\SMSSenders\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SMSSenderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sender')
                    ->required(),
                Toggle::make('is_active')
                    ->required()->default(true),
            ]);
    }
}
