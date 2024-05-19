<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\fireflyIII;
use Illuminate\Support\Facades\Mail;
use App\Mail\newTransaction;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

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
        // $firefly = new fireflyIII();
        // $role = $firefly->findRole('Health Insurance yearly bills');
        Mail::to('ahmadt@gmail.com')->send(new newTransaction('MY VAL', '24$ to Walmat'));
        // dd($role);
    }
}
