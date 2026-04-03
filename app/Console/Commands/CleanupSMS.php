<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SMS;
use App\Models\Setting;

class CleanupSMS extends Command
{
    protected $signature = 'app:CleanupSMS';

    protected $description = 'Delete valid and processed SMS older than configured days';

    public function handle()
    {
        $days = Setting::getInt('cleanup_sms_days', 30);

        $deleted = SMS::where('is_valid', true)
            ->where('is_processed', true)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} processed SMS older than {$days} days.");
    }
}
