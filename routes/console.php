<?php

use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\calAverageTransactions;
use App\Console\Commands\billDetector;
use App\Console\Commands\cleanSMS;
use App\Console\Commands\sendReport;
use Illuminate\Support\Facades\Schedule;
// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

// Schedule::command(calAverageTransactions::class, ['--type=withdrawal'])->daily();
Schedule::command(calAverageTransactions::class, ['--type=withdrawal'])->weekly();

// Schedule::command(calAverageTransactions::class, ['--type=deposit'])->daily();
Schedule::command(calAverageTransactions::class, ['--type=deposit'])->weekly();



Schedule::command(billDetector::class)->weekly();

Schedule::command(cleanSMS::class)->weekly();


Schedule::command(sendReport::class)->weekly();

