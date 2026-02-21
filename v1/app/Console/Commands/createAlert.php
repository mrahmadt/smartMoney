<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;

class createAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:createAlert{--title=} {--message=} {--type=} {--user_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $title = $this->option('title');
        if ($title === null) {
            $title = $this->ask('Please enter alert title.');
        }

        $message = $this->option('message');
        if ($message === null) {
            $message = $this->ask('Please enter alert message.');
        }

        $type = $this->option('type');
        if ($type === null) {
            $type = $this->ask('Please enter alert type.');
        }
        $user_id = $this->option('user_id');

        Alert::createAlert($title, $message, $type , $user_id);

    }
}
