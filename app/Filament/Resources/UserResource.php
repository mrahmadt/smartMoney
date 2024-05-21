<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                // Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('budgets')
                    ->maxLength(255),
                Forms\Components\Toggle::make('accessAllBudgets')
                    ->required(),
                // Forms\Components\TextInput::make('accounts')
                    // ->maxLength(255),
                // Forms\Components\Toggle::make('accessAllAccounts')
                    // ->required(),
                Forms\Components\Toggle::make('is_admin')
                    ->required(),
                Forms\Components\Toggle::make('alertViaEmail')
                    ->required(),
                Forms\Components\Toggle::make('alertNewBillCreation')
                    ->required(),
                Forms\Components\Toggle::make('alertBillOverAmountPercentage')
                    ->required(),
                Forms\Components\Toggle::make('alertAbnormalTransaction')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                // Tables\Columns\TextColumn::make('email_verified_at')
                //     ->dateTime()
                //     ->toggleable(isToggledHiddenByDefault: true)
                //     ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('budgets')
                    ->searchable(),
                Tables\Columns\IconColumn::make('accessAllBudgets')
                    ->boolean(),
                // Tables\Columns\TextColumn::make('accounts')
                // ->toggleable(isToggledHiddenByDefault: true)
                //     ->searchable(),
                // Tables\Columns\IconColumn::make('accessAllAccounts')
                // ->toggleable(isToggledHiddenByDefault: true)
                //     ->boolean(),
                Tables\Columns\IconColumn::make('is_admin')
                    ->boolean(),
                Tables\Columns\IconColumn::make('alertViaEmail')
                    ->boolean(),
                Tables\Columns\IconColumn::make('alertNewBillCreation')
                    ->label('Alert Bill Creation')
                    ->boolean(),
                Tables\Columns\IconColumn::make('alertBillOverAmountPercentage')
                    ->label('Alert Bills')
                    ->boolean(),
                Tables\Columns\IconColumn::make('alertAbnormalTransaction')
                    ->label('Alert Abnormal Trans')
                    ->boolean(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
