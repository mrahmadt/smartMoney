<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Helpers\fireflyIII;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

use App\Models\Transaction;

class BudgetController extends Controller
{
    public function index()
    {
        $apiToken = session()->get('apiToken', null);
        if($apiToken == null){
            $apiToken = Auth::user()->generateToken('api-token', true, 'apiToken');
        }

        $budgets = [];
        $firefly = new fireflyIII();

        $start = date('Y-m-01');
        $end = date('Y-m-t');

        $budgets_all = $firefly->getBudget(null, $start, $end);

        $budgets = [];
        if(Auth::user()->budgets!=''){
            $user_budgets = Auth::user()->budgets;
            if(count($user_budgets) == 1) return Redirect::to('/budgets/'.$user_budgets[0] . '/' . $start . '/' . $end);
            foreach($budgets_all->data as $budget){
                if(in_array($budget->id, $user_budgets)){
                    $budgets[] = $budget;
                }
            }
        }
        return view('budget.index', ['budgets' => $budgets, 'start'=> $start, 'end'=>$end]);
    }

    public function show(Request $request, $budget_id, $start = null, $end = null): View
    {
        $apiToken = session()->get('apiToken', null);
        if($apiToken == null){
            $apiToken = Auth::user()->generateToken('api-token', true, 'apiToken');
        }

        if($start == null) $start = date('Y-m-01');
        if($end == null) $end = date('Y-m-t');

        if(date('Y-m', strtotime($end)) == date('Y-m')){
            $range = null;
        }elseif($start != null && $end != null && date('Y-m', strtotime($start)) == date('Y-m', strtotime($end))){
            $range = date('M', strtotime($start));
        }elseif($start != null && $end != null && date('Y', strtotime($start)) == date('Y', strtotime($end))){
            $range = date('Y/M', strtotime($start));
            $range .= ' ' . date('Y/M', strtotime($end));
        }
 
        $firefly = new fireflyIII();

        $budget = $firefly->getBudget($budget_id, $start, $end);
        $budget = $budget->data;

        $transactions = $firefly->getTransactions($start, $end, ['budget_id' => $budget_id]);

        $stats = Transaction::transactionsStatistics($transactions);

        // Array to count spending per day
        $spending = [];
        // Loop through transactions to count spending by date
        foreach ($transactions as $transaction) {
            if ($transaction->type === 'withdrawal') {
                // Extract the date part (up to 'T' character)
                $date = substr($transaction->date, 0, 10);
                // If the date is not in the spending array, initialize it
                if (!isset($spending[$date])) {
                    $spending[$date] = 0;
                }
                // Add the transaction amount to the corresponding date
                $spending[$date] += (float)$transaction->amount;
            }
        }

        // Create an array of labels in 'MM-DD' format
        $spendingLabels = array_map(function($date) {
            return date('m-d', strtotime($date));
        }, array_keys($spending));

        $categoriesChart = [];
        // Loop through the categories to extract 'withdrawalOnly' values
        foreach ($stats['categories'] as $category => $data) {
            // Assign the 'withdrawalOnly' amount to the corresponding category in categoriesChart
            $categoriesChart[$category] = $data['withdrawalOnly'];
        }

        return view('budget.show', ['apiToken' => $apiToken, 'budget' => $budget,'transactions'=>$transactions,  'spendingLabels'=>$spendingLabels, 'spending'=>$spending, 'stats'=>$stats, 'range'=>$range, 'start'=> $start, 'end'=>$end, 'categoriesChart'=>$categoriesChart]);
    }
}
