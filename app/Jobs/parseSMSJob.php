<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SMS;

class parseSMSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sms;
    protected $options;
    /**
     * Create a new job instance.
     */
    public function __construct(SMS $sms, $options = [])
    {
        $this->options = array_merge([
            'dryRun' => false,
        ], $options);

        $this->sms = $sms->withoutRelations();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $senders = config('parseSMS.senders');
        $parser = 'App\parseSMS\Parsers\\' . $senders[$this->sms->sender];
        $data = $parser::execute($this->sms, $this->options);

        if($this->options['dryRun']){
            dispatch(new createTransactionJob($this->sms, $data, $this->options))->onConnection('sync');
        }else{
            dispatch(new createTransactionJob($this->sms, $data, $this->options));
        }

    }
}
