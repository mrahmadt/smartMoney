<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class cleanSMS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanSMS';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove SMS from the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(config('parseSMS.clean_processed_sms')) {
            $this->info('Cleaning processed SMS...');
            \App\Models\SMS::where('is_processed', true)->delete();
        }
        if(config('parsedSMS.clean_invalid_sms')) {
            $this->info('Cleaning invalid SMS...');
            \App\Models\SMS::where('is_valid', false)->delete();
        }
        if(config('parsedSMS.clean_error_sms')) {
            $this->info('Cleaning error SMS...');
            \App\Models\SMS::where('is_processed', false)->whereNotNull('error')->delete();
        }
    }
}
