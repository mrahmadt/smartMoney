<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Models\Account;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    public function mount(): void
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        Account::syncFromFirefly();
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
