<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('FF_account_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('FF_account_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('FF_account_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('sms_sender')
                    ->maxLength(255),
                Forms\Components\TextInput::make('budget_id')
                    ->numeric(),
                Forms\Components\Toggle::make('sendTransactionAlert')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name'),
                Forms\Components\Toggle::make('defaultAccount')
                    ->required(),
                Forms\Components\TextInput::make('tags'),
                Forms\Components\TextInput::make('values'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('FF_account_name')
                    ->label('Firefly-III Account')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_code')
                    ->label('SMS Account Code')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('sms_sender')
                //     ->label('SMS Sender')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('budget_id')
                    ->label('FireFly-III Budget')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('sendTransactionAlert')
                    ->label('Transaction Alert')
                    ->boolean(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('defaultAccount')
                    ->label('Default?')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
