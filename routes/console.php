<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command('app:SyncFirefly')->daily();
Schedule::command('app:CleanupPushSubscriptions')->weekly();
Schedule::command('app:SubscriptionDetector')->cron('0 0 */10 * *');
Schedule::command('app:CleanupSMS')->daily();
Schedule::command('app:CleanupAlerts')->daily();
Schedule::command('app:DailySpendingCheck')->dailyAt('23:00');
Schedule::command('app:WeeklySpendingCheck')->weeklyOn(0, '23:30');
Schedule::command('app:MonthlySpendingCheck')->lastDayOfMonth('23:45');
