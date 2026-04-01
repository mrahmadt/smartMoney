<?php

namespace App\Filament\Resources\SMS\Pages;

use App\Filament\Resources\SMS\SMSResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use App\Jobs\parseSMSJob;

class EditSMS extends EditRecord
{
    protected static string $resource = SMSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reprocess')
                ->label('Process')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => !$this->record->is_valid && $this->record->is_processed)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_valid' => true, 'is_processed' => false, 'errors' => null]);
                    parseSMSJob::dispatch($this->record);
                    $this->redirect(SMSResource::getUrl('index'));
                }),
            DeleteAction::make(),
        ];
    }
}
