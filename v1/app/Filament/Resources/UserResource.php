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
use PhpParser\Node\Expr\AssignOp\Mod;
use App\Helpers\fireflyIII;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static $ffbudgets = [];

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
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('budgets')
                    ->multiple()
                    ->options(function (){
                        $budgets = [];
                        $firefly = new fireflyIII();
                        $ffbudgets = $firefly->getBudgets(limit: 300);
                        foreach($ffbudgets->data as $budget){
                            $budgets[$budget->id] = $budget->attributes->name;
                        }
                        return $budgets;
                    })
                    ,
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
                Tables\Columns\TextColumn::make('budgets')
                    ->formatStateUsing(function (Model $record){
                        if(!is_array($record->budgets)) return '-';
                        if(!static::$ffbudgets){
                            $fireflyIII = new fireflyIII();
                            static::$ffbudgets = $fireflyIII->getBudgets(limit: 500);
                        }
                        $names = [];
                        foreach(static::$ffbudgets->data as $ffbudgets){
                            if(in_array($ffbudgets->id, $record->budgets)){
                                $names[] = $ffbudgets->attributes->name;
                            }
                        }
                        return implode(', ', $names);
                    }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
