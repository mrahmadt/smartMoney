<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\parseSMSJob;

use App\Models\SMS;

class processSMS extends Command
{
    protected $signature = 'app:processSMS {--id=} {--dryRun}';
    protected $description = 'Command description';

    public function handle()
    {
        $options = $this->option();

        if($options['id']){
            if($options['id'] == 'all'){
                $this->info('Processing all SMS (is_processed = 0)');
                $smses = SMS::where('is_processed', 0)->get();
            }else{
                $smses = SMS::where('id', $options['id'])->get();

                // send alert here
                
            }
            foreach($smses as $sms){
                $this->info('Processing SMS ID: '.$sms->id);
                if($options['dryRun']){
                    dispatch(new parseSMSJob($sms, $options))->onConnection('sync');
                }else{
                    dispatch(new parseSMSJob($sms, $options));
                }
            }
        }
    }
}
