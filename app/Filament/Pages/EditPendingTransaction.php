<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Category;
use App\Models\PendingTransaction;
use App\Models\SMS;
use App\Models\Transaction;
use App\Services\fireflyIII;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditPendingTransaction extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.edit-pending-transaction';

    protected static ?string $slug = 'review-transactions/{record}/edit';

    public string $record;

    public ?PendingTransaction $pendingTransaction = null;

    public array $budgets = [];

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        return Auth::id() === 1;
    }

    public function getTitle(): string
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return __('menu.edit_pending_transaction');
    }

    public function mount(string $record): void
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        $this->record = $record;
        $this->pendingTransaction = PendingTransaction::with('sms')->findOrFail($record);

        $firefly = new fireflyIII;
        $budgetsFF = $firefly->getBudgets();
        if (! empty($budgetsFF->data)) {
            foreach ($budgetsFF->data as $budget) {
                $this->budgets[$budget->id] = $budget->attributes->name;
            }
        }

        $this->form->fill([
            'type' => $this->pendingTransaction->type,
            'amount' => $this->pendingTransaction->amount,
            'currency' => $this->pendingTransaction->currency,
            'date' => $this->pendingTransaction->date,
            'description' => $this->pendingTransaction->description,
            'notes' => $this->pendingTransaction->notes,
            'category_name' => $this->pendingTransaction->category_name,
            'source_account' => $this->pendingTransaction->source_account_id ?? $this->pendingTransaction->source_account_name,
            'destination_account' => $this->pendingTransaction->destination_account_id ?? $this->pendingTransaction->destination_account_name,
            'tags' => $this->pendingTransaction->tags ?? [],
            'budget_id' => $this->pendingTransaction->budget_id,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $accounts = Account::pluck('firefly_account_name', 'firefly_account_id')->toArray();
        $categories = Category::all()->pluck('name', 'name')->toArray();

        return $schema
            ->components([
                Form::make([
                    Select::make('type')
                        ->label(__('menu.type'))
                        ->options([
                            'withdrawal' => 'Withdrawal',
                            'deposit' => 'Deposit',
                            'transfer' => 'Transfer',
                        ])
                        ->required(),
                    TextInput::make('amount')
                        ->label(__('widget.amount'))
                        ->numeric()
                        ->required(),
                    TextInput::make('currency')
                        ->label(__('menu.currency'))
                        ->maxLength(3)
                        ->required(),
                    DateTimePicker::make('date')
                        ->label(__('widget.date'))
                        ->required(),
                    TextInput::make('description')
                        ->label(__('menu.description'))
                        ->extraInputAttributes(['dir' => 'auto']),
                    Select::make('category_name')
                        ->label(__('widget.category'))
                        ->options($categories)
                        ->searchable(),
                    Select::make('source_account')
                        ->label(__('menu.source'))
                        ->options($accounts)
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label(__('menu.source_name'))
                                ->required(),
                        ])
                        ->createOptionUsing(fn (array $data): string => $data['name'])
                        ->getOptionLabelUsing(function ($value) use ($accounts) {
                            return $accounts[$value] ?? $value;
                        }),
                    Select::make('destination_account')
                        ->label(__('menu.destination'))
                        ->options($accounts)
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label(__('menu.destination_name'))
                                ->required(),
                        ])
                        ->createOptionUsing(fn (array $data): string => $data['name'])
                        ->getOptionLabelUsing(function ($value) use ($accounts) {
                            return $accounts[$value] ?? $value;
                        }),
                    TagsInput::make('tags')
                        ->label(__('menu.tags')),
                    Select::make('budget_id')
                        ->label(__('menu.budget'))
                        ->options(fn () => $this->budgets)
                        ->searchable(),
                    Textarea::make('notes')
                        ->label(__('menu.notes'))
                        ->rows(6)
                        ->extraInputAttributes(['dir' => 'auto']),

                ])
                    ->livewireSubmitHandler('submit')
                    ->footer([
                        SchemaActions::make([
                            Action::make('submit')
                                ->label(__('menu.submit_to_firefly'))
                                ->submit('submit')
                                ->color('success')
                                ->icon('heroicon-o-paper-airplane'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $record = $this->pendingTransaction;

        $fireflyTransaction = [
            'type' => $data['type'],
            'amount' => (float) $data['amount'],
            'currency_code' => $data['currency'],
            'date' => $data['date'] instanceof \DateTimeInterface ? $data['date']->toIso8601String() : $data['date'],
        ];

        if (! empty($data['description'])) {
            $fireflyTransaction['description'] = $data['description'];
        }
        if (! empty($data['notes'])) {
            $fireflyTransaction['notes'] = $data['notes'];
        }
        if (! empty($data['category_name'])) {
            $fireflyTransaction['category_name'] = $data['category_name'];
        }
        if (! empty($data['tags'])) {
            $fireflyTransaction['tags'] = $data['tags'];
        }
        if (! empty($data['budget_id'])) {
            $fireflyTransaction['budget_id'] = (int) $data['budget_id'];
        }

        // Resolve source: numeric = account ID, string = account name
        $accounts = Account::pluck('firefly_account_name', 'firefly_account_id')->toArray();

        $sourceValue = $data['source_account'] ?? null;
        if ($sourceValue && is_numeric($sourceValue) && isset($accounts[(int) $sourceValue])) {
            $fireflyTransaction['source_id'] = (int) $sourceValue;
        } elseif (! empty($sourceValue)) {
            $fireflyTransaction['source_name'] = $sourceValue;
        }

        $destValue = $data['destination_account'] ?? null;
        if ($destValue && is_numeric($destValue) && isset($accounts[(int) $destValue])) {
            $fireflyTransaction['destination_id'] = (int) $destValue;
        } elseif (! empty($destValue)) {
            $fireflyTransaction['destination_name'] = $destValue;
        }

        try {
            $firefly = new fireflyIII;
            $result = $firefly->newTransaction($fireflyTransaction);

            if (isset($result->exception) || isset($result->errors) || isset($result->message)) {
                $errorParts = [];
                if (isset($result->message)) {
                    $errorParts[] = $result->message;
                }
                if (isset($result->errors)) {
                    foreach ((array) $result->errors as $field => $messages) {
                        foreach ((array) $messages as $msg) {
                            $errorParts[] = "[{$field}] {$msg}";
                        }
                    }
                }
                $errorMsg = implode(' | ', $errorParts) ?: 'Unknown error';

                $record->update(['error_message' => $errorMsg, 'reason' => 'error']);

                Notification::make()
                    ->title(__('menu.transaction_submit_failed'))
                    ->body($errorMsg)
                    ->danger()
                    ->send();

                return;
            }

            if (isset($result->data->id)) {
                $transactionId = $result->data->id;
                $attributes = $result->data->attributes->transactions[0];

                if ($record->sms_id) {
                    SMS::where('id', $record->sms_id)->update([
                        'is_processed' => true,
                        'transaction_id' => $transactionId,
                    ]);
                }

                Transaction::postCreationActions(
                    attributes: $attributes,
                    transaction: $data,
                    smsId: $record->sms_id,
                );

                $record->delete();

                Notification::make()
                    ->title(__('menu.transaction_submitted'))
                    ->success()
                    ->send();

                $this->redirect(ReviewTransactions::getUrl());
            } else {
                $record->update(['error_message' => 'Unknown error from Firefly', 'reason' => 'error']);

                Notification::make()
                    ->title(__('menu.transaction_submit_failed'))
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Failed to submit pending transaction', ['error' => $e->getMessage(), 'record_id' => $record->id]);

            $record->update(['error_message' => $e->getMessage(), 'reason' => 'error']);

            Notification::make()
                ->title(__('menu.transaction_submit_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
