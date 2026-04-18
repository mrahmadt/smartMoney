<?php

namespace App\Filament\Resources\SMS\Pages;

use App\Filament\Pages\EditTransactions;
use App\Filament\Resources\SMS\SMSResource;
use App\Jobs\parseSMSJob;
use App\Services\fireflyIII;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditSMS extends EditRecord
{
    protected static string $resource = SMSResource::class;

    public ?array $transactionData = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->loadTransaction();
    }

    protected function loadTransaction(): void
    {
        if (! $this->record->transaction_id) {
            return;
        }

        try {
            $firefly = new fireflyIII;
            $tx = $firefly->getTransaction($this->record->transaction_id);
            if ($tx) {
                $this->transactionData = [
                    'type' => $tx->type ?? '',
                    'amount' => ($tx->currency_symbol ?? '').' '.number_format((float) ($tx->amount ?? 0), 2),
                    'description' => $tx->description ?? '',
                    'destination' => $tx->destination_name ?? '',
                    'source' => $tx->source_name ?? '',
                    'category' => $tx->category_name ?? '',
                    'date' => $tx->date ?? '',
                    'journal_id' => $tx->transaction_journal_id ?? $this->record->transaction_id,
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('EditSMS: Failed to load transaction', ['error' => $e->getMessage()]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewTransaction')
                ->label(__('alert.view_transaction'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => EditTransactions::getUrl([
                    'transactionId' => $this->transactionData['journal_id'] ?? $this->record->transaction_id,
                ]))
                ->visible(fn () => $this->record->transaction_id && $this->transactionData),
            Action::make('reprocess')
                ->label('Process')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => ! $this->record->is_valid && $this->record->is_processed)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_valid' => true, 'is_processed' => false, 'errors' => null]);
                    parseSMSJob::dispatch($this->record);
                    $this->redirect(SMSResource::getUrl('index'));
                }),
            DeleteAction::make(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $components = [
            $this->getFormContentComponent(),
            $this->getRelationManagersContentComponent(),
        ];

        if ($this->transactionData) {
            $components[] = Section::make(__('alert.view_transaction'))
                ->schema([
                    TextEntry::make('type')->label(__('widget.type'))->state($this->transactionData['type']),
                    TextEntry::make('amount')->label(__('widget.amount'))->state($this->transactionData['amount']),
                    TextEntry::make('destination')->label(__('widget.destination'))->state($this->transactionData['destination']),
                    TextEntry::make('source')->label(__('widget.source'))->state($this->transactionData['source']),
                    TextEntry::make('category')->label(__('widget.category'))->state($this->transactionData['category']),
                    TextEntry::make('description')->label(__('widget.description'))->state($this->transactionData['description']),
                    TextEntry::make('date')->label(__('widget.date'))->state($this->transactionData['date']),
                ])
                ->columns(2);
        }

        return $schema->components($components);
    }
}
