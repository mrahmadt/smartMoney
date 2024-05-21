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
use Illuminate\Database\Eloquent\Model;
use App\Helpers\fireflyIII;
use Closure;
use Illuminate\Support\Str;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;
    protected static ?string $navigationGroup = 'SMS';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $modelLabel = 'Accounts';
    protected static ?int $navigationSort = 3;

    protected static $ffaccounts = [];
    protected static $ffbudgets = [];
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('FF_account_id')
                    ->required()
                    ->label('Firefly-III Account')
                    ->options(
                        function (Model $record = null){
                            $accounts = [];
                            if(!static::$ffaccounts){
                                $fireflyIII = new fireflyIII();
                                static::$ffaccounts = $fireflyIII->getAccounts();
                            }
                            foreach(static::$ffaccounts->data as $account){
                                    $accounts[$account->id] = $account->attributes->name;
                            }
                            return $accounts;
                        }
                    )
                    ,
                Forms\Components\TextInput::make('account_code')
                    ->label('SMS Account Code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('sms_sender')
                    ->label('SMS Sender')
                    ->maxLength(255),
                Forms\Components\TextInput::make('budget_id')
                    ->label('Firefly-III Budget')
                    ->numeric(),
                Forms\Components\Toggle::make('sendTransactionAlert')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name'),
                Forms\Components\Toggle::make('defaultAccount')
                    ->required(),
                Forms\Components\TagsInput::make('tags')->columnSpanFull()
                ,
                Forms\Components\KeyValue::make('values')->addActionLabel('Add Value')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('FF_account_name')
                ->label('Firefly-III Account')
                ->formatStateUsing(function (Model $record){
                    if(!static::$ffaccounts){
                        $fireflyIII = new fireflyIII();
                        static::$ffaccounts = $fireflyIII->getAccounts();
                    }
                    foreach(static::$ffaccounts->data as $account){
                        if($account->id == $record->FF_account_id){
                            return $account->attributes->name;
                        }
                    }
                    return 'Unknown';
                }),
                // Tables\Columns\TextColumn::make('FF_account_name')
                //     ->label('Firefly-III Account')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('account_code')
                    ->label('SMS Account Code')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('sms_sender')
                //     ->label('SMS Sender')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('budget_id')
                    ->label('FireFly-III Budget')
                    ->formatStateUsing(function (Model $record){
                        if(!static::$ffbudgets){
                            $fireflyIII = new fireflyIII();
                            static::$ffbudgets = $fireflyIII->getBudgets(limit: 500);
                        }
                        foreach(static::$ffbudgets->data as $ffbudgets){
                            if($ffbudgets->id == $record->budget_id){
                                return $ffbudgets->attributes->name;
                            }
                        }
                        return 'Unknown';
                    }),
                Tables\Columns\ToggleColumn::make('sendTransactionAlert')
                    ->label('Transaction Alert')
                    ,
                    // ->boolean(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('defaultAccount')
                    ->label('Default?')
                    ,
                    // ->boolean(),
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
