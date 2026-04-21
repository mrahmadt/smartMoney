<?php

namespace App\Filament\Pages;

use App\Models\PendingTransaction;
use App\Models\SMS;
use App\Models\Transaction;
use App\Services\fireflyIII;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewTransactions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.review-transactions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return Auth::id() === 1;
    }

    public static function getNavigationLabel(): string
    {
        app()->setLocale(auth()->user()->language ?? 'en');

        return __('menu.review_transactions');
    }

    public function getTitle(): string
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return __('menu.review_transactions');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = PendingTransaction::count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function table(Table $table): Table
    {
        app()->setLocale(Auth::user()->language ?? 'en');

        return $table
            ->query(
                PendingTransaction::query()->with('sms')->orderByDesc('date')
            )
            ->columns([
                TextColumn::make('date')
                    ->label(__('widget.date'))
                    ->dateTime('M d, g:ia')
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('menu.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'withdrawal' => 'danger',
                        'deposit' => 'success',
                        'transfer' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('sms.message')
                    ->label(__('menu.sms'))
                    ->searchable()
                    ->wrap()
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-';
                        }
                        $escaped = e($state);
                        $html = nl2br($escaped);
                        $isRtl = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $state);

                        return $isRtl
                            ? '<div dir="rtl" style="direction:rtl;text-align:right">'.$html.'</div>'
                            : $html;
                    }),
                TextColumn::make('amount')
                    ->label(__('widget.amount'))
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 2, '.', ',').' '.($record->currency ?? ''))
                    ->color(fn ($record) => $record->type === 'deposit' ? 'success' : 'danger'),
                TextColumn::make('reason')
                    ->label(__('menu.reason'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual_review' => 'info',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => __("menu.{$state}")),
                TextColumn::make('error_message')
                    ->label(__('menu.error'))
                    ->limit(40)
                    ->wrap(true)
                    ->placeholder('-')
                    ->tooltip(fn ($record) => $record->error_message),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label(__('menu.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (PendingTransaction $record): string => EditPendingTransaction::getUrl(['record' => $record->id])),
                Action::make('retry')
                    ->label(__('menu.retry'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (PendingTransaction $record): void {
                        $this->submitToFirefly($record);
                    }),
                Action::make('dismiss')
                    ->label(__('menu.dismiss'))
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (PendingTransaction $record): void {
                        if ($record->sms_id) {
                            SMS::where('id', $record->sms_id)->update(['is_processed' => true]);
                        }
                        $record->delete();
                        Notification::make()
                            ->title(__('menu.dismissed'))
                            ->info()
                            ->send();
                    }),
            ]);
    }

    protected function submitToFirefly(PendingTransaction $record): void
    {
        $fireflyTransaction = [
            'type' => $record->type,
            'amount' => (float) $record->amount,
            'currency_code' => $record->currency,
            'date' => $record->date instanceof \DateTimeInterface ? $record->date->toIso8601String() : $record->date,
        ];

        if (! empty($record->description)) {
            $fireflyTransaction['description'] = $record->description;
        }
        if (! empty($record->notes)) {
            $fireflyTransaction['notes'] = $record->notes;
        }
        if (! empty($record->category_name)) {
            $fireflyTransaction['category_name'] = $record->category_name;
        }
        if (! empty($record->tags)) {
            $fireflyTransaction['tags'] = $record->tags;
        }
        if (! empty($record->budget_id)) {
            $fireflyTransaction['budget_id'] = (int) $record->budget_id;
        }

        // ID takes priority over name
        if ($record->source_account_id) {
            $fireflyTransaction['source_id'] = (int) $record->source_account_id;
        } elseif (! empty($record->source_account_name)) {
            $fireflyTransaction['source_name'] = $record->source_account_name;
        }

        if ($record->destination_account_id) {
            $fireflyTransaction['destination_id'] = (int) $record->destination_account_id;
        } elseif (! empty($record->destination_account_name)) {
            $fireflyTransaction['destination_name'] = $record->destination_account_name;
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

                // Update the record with the new error
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

                // Update SMS record
                if ($record->sms_id) {
                    SMS::where('id', $record->sms_id)->update([
                        'is_processed' => true,
                        'transaction_id' => $transactionId,
                    ]);
                }

                // Run post-creation actions
                Transaction::postCreationActions(
                    attributes: $attributes,
                    transaction: $record->toArray(),
                    smsId: $record->sms_id,
                );

                $record->delete();

                Notification::make()
                    ->title(__('menu.transaction_submitted'))
                    ->success()
                    ->send();
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
