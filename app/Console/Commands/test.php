<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\fireflyIII;
use Illuminate\Support\Facades\Mail;
use App\Mail\newTransaction;
use App\Models\User;

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
        // Mail::to('ahmadt@gmail.com')->send(new newTransaction('MY VAL', '24$ to Walmat'));
        // dd($role);
        $user = User::find(1);
        $title = 'New Transaction';
        $message = 'A new transaction has been made.';
        $error = [];
        Mail::to($user)->send(new newTransaction($title, $message, $error));
    }
}
