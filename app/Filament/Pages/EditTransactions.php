<?php

namespace App\Filament\Pages;

use App\Services\fireflyIII;
use Filament\Actions\Action;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TagsInput;

class EditTransactions extends Page implements HasForms
{
    use InteractsWithForms;
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.edit-transactions';

    protected static ?string $slug = 'transactions/{transactionId}/edit';

    public function getTitle(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        return __('menu.edit_transaction');
    }

    public string $transactionId;
    public $budgets;
    public $accounts;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(string $transactionId): void
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        $this->transactionId = $transactionId;

        $this->data = $this->fetchTransaction();

        
        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    // TextInput::make('transaction_journal_id'),
                    TextInput::make('description')->label(__('widget.description'))->required(),
                    // TextInput::make('type'),

                    Select::make('type')
                        ->label(__('widget.type'))
                        ->options([
                            'withdrawal' => __('widget.withdrawal'),
                            'transfer' => __('widget.transfer'),
                            'deposit' => __('widget.deposit'),
                        ])->required(),
                    TextInput::make('amount')->label(__('widget.amount'))->required()->formatStateUsing(fn($state) => number_format($state, 2, '.', ','))->suffix(fn() => $this->data['currency_code'] ?? ''),

                    Select::make('source_id')->label(__('widget.account'))->required()
                        ->options(fn() => $this->accounts),


                    TextInput::make('destination_name')->label(__('widget.destination'))->required(),

                    DateTimePicker::make('date')
                        ->label(__('widget.date'))
                        ->seconds(false)->required(),

                    Select::make('budget_id')->label(__('widget.budget'))
                        ->options(fn() => $this->budgets),

                    TextInput::make('category_name')->label(__('widget.category')),
                    TagsInput::make('tags')->label(__('widget.tags')),


                    Textarea::make('notes')
                        ->label(__('widget.notes'))->rows(9)
                        ->extraAttributes(['style' => 'direction: ltr; text-align: left;']),



                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        SchemaActions::make([
                            Action::make('save')
                                ->label(__('widget.save'))
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }


    public function save(): void
    {
        $state = $this->form->getState();

        $firefly = new fireflyIII();
        $output = $firefly->updateTransaction($this->transactionId, $state);
        Notification::make()
            ->success()
            ->title(__('widget.saved'))
            ->send();
    }

    private function fetchTransaction(): array
    {
        $firefly = new fireflyIII();

        $transaction = $firefly->getTransaction($this->transactionId);

        if (empty($transaction)) {
            return [];
        }

        $transaction = json_decode(json_encode($transaction), true);
        $budgets = [];
        $budgetsFF = $firefly->getBudgets();
        if (!empty($budgetsFF)) {
            $budgetsFF = $budgetsFF->data;
            foreach ($budgetsFF as $budgetFF) {
                $budgets[$budgetFF->id] = $budgetFF->attributes->name;
            }
        }
        $this->budgets = $budgets;

        $accountsFF = $firefly->getAccounts(type:'asset');
        if (!empty($accountsFF)) {
            $accountsFF = $accountsFF->data;
            foreach ($accountsFF as $accountFF) {
                $accounts[$accountFF->id] = $accountFF->attributes->name . ' (' . $accountFF->attributes->currency_code . ')';
            }
        }
        $this->accounts = $accounts;
        // $categories = $firefly->getCategories();
        return $transaction;
    }
}
