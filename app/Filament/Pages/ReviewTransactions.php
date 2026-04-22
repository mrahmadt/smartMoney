<?php

namespace App\Filament\Pages;

use App\Models\PendingTransaction;
use App\Models\SMS;
use App\Models\Transaction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
        $data = [
            'type' => $record->type,
            'amount' => $record->amount,
            'currency' => $record->currency,
            'date' => $record->date,
            'description' => $record->description,
            'notes' => $record->notes,
            'category_name' => $record->category_name,
            'source_account' => $record->source_account_id ?? $record->source_account_name,
            'destination_account' => $record->destination_account_id ?? $record->destination_account_name,
            'tags' => $record->tags ?? [],
            'budget_id' => $record->budget_id,
        ];

        $result = Transaction::submitToFirefly($data);

        if (! $result['success']) {
            $record->update(['error_message' => $result['error'], 'reason' => 'error']);

            Notification::make()
                ->title(__('menu.transaction_submit_failed'))
                ->body($result['error'])
                ->danger()
                ->send();

            return;
        }

        if ($record->sms_id) {
            SMS::where('id', $record->sms_id)->update([
                'is_processed' => true,
                'is_valid' => true,
                'transaction_id' => $result['transaction_id'],
            ]);
        }

        Transaction::postCreationActions(
            attributes: $result['attributes'],
            transaction: $record->toArray(),
            smsId: $record->sms_id,
        );

        $record->delete();

        Notification::make()
            ->title(__('menu.transaction_submitted'))
            ->success()
            ->send();
    }
}
