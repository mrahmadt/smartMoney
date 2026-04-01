<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\Setting;

class CleanupAlerts extends Command
{
    protected $signature = 'app:CleanupAlerts';

    protected $description = 'Delete read alerts older than configured days';

    public function handle()
    {
        $days = Setting::getInt('cleanup_alerts_days', 30);

        $deleted = Alert::where('is_read', true)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} read alerts older than {$days} days.");
    }
}
