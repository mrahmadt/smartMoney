<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Category;
use Illuminate\Console\Command;

class SyncFirefly extends Command
{
    protected $signature = 'app:SyncFirefly';

    protected $description = 'Sync accounts and categories from Firefly III';

    public function handle(): void
    {
        $this->info('Syncing accounts from Firefly III...');
        $accountResult = Account::syncFromFirefly();
        $this->info("Accounts: {$accountResult['created']} created, {$accountResult['updated']} updated, {$accountResult['deleted']} deleted");

        $this->info('Syncing categories from Firefly III...');
        $categoryResult = Category::syncFromFirefly();
        $this->info("Categories: {$categoryResult['created']} created");

        $this->info('Sync complete.');
    }
}
