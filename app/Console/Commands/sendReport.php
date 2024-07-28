<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use App\Models\User;
use App\Mail\report;
use App\Helpers\fireflyIII;

class sendReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send spending report to users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $firefly = new fireflyIII();
        
        // TODO: need to set them based on the budget period
        $start = date('Y-m-01');
        $end = date('Y-m-t');

        $users = User::get();
        $budgets = [];
        foreach($users as $user){
            if($user->alertViaEmail == 0 || count($user->budgets) == 0) continue;
            foreach($user->budgets as $budget_id){
                if(!isset($budgets[$budget_id])){
                    $budget = $firefly->getBudget($budget_id, $start, $end);
                    $budgets[$budget_id] = $budget->data;
                }
                Mail::to($user)->send(new report($budgets[$budget_id]));
            }
        }
    }
}
