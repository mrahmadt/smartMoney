<?php

namespace App\Filament\Resources\SMS\Pages;

use App\Filament\Resources\SMS\SMSResource;
use App\Jobs\parseSMSJob;
use App\Models\SMS;
use App\Models\SMSSender;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSMS extends ListRecords
{
    protected static string $resource = SMSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addSMS')
                ->label(__('menu.add_sms'))
                ->icon('heroicon-o-plus')
                ->form([
                    Select::make('sender')
                        ->label(__('menu.sender'))
                        ->options(SMSSender::where('is_active', true)->pluck('sender', 'sender'))
                        ->searchable()
                        ->required(),
                    Textarea::make('message')
                        ->label(__('widget.message'))
                        ->required()
                        ->rows(6),
                ])
                ->action(function (array $data) {
                    $sender = $data['sender'];
                    $message = SMS::removeHiddenChars($data['message']);
                    $smsSender = SMSSender::where('sender', $sender)->where('is_active', true)->first();
                    $message = SMS::preClean($message, $smsSender?->id);

                    if (SMS::isDuplicate($sender, $message)) {
                        Notification::make()
                            ->title(__('menu.sms_duplicate'))
                            ->warning()
                            ->send();

                        return;
                    }

                    $sms = new SMS;
                    $sms->sender = strtolower($sender);
                    $sms->message = $message;
                    $sms->content = [
                        '_version' => 1,
                        'query' => [
                            'sender' => $sender,
                            'date' => now()->toDateTimeString(),
                            'message' => ['text' => $message],
                        ],
                        'app' => ['version' => 'manual-ui'],
                        'key' => null,
                    ];
                    $sms->is_valid = true;
                    $sms->is_processed = false;
                    $sms->message_hash = SMS::generateHash($sender, $message);
                    $sms->save();

                    dispatch(new parseSMSJob($sms));

                    Notification::make()
                        ->title(__('menu.sms_queued'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
