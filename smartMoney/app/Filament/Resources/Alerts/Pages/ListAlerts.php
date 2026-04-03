<?php

namespace App\Filament\Resources\Alerts\Pages;

use App\Filament\Resources\Alerts\AlertResource;
use App\Models\Alert;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAlerts extends ListRecords
{
    protected static string $resource = AlertResource::class;

    public function mount(): void
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllRead')
                ->label(__('menu.mark_all_read'))
                ->icon('heroicon-o-check')
                ->color('gray')
                ->requiresConfirmation(false)
                ->action(function () {
                    Alert::where('user_id', Auth::id())
                        ->where('is_read', false)
                        ->update(['is_read' => true]);

                    Notification::make()
                        ->title(__('menu.all_marked_read'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
