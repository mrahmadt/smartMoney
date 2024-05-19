<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\fireflyIII;
use Illuminate\View\View;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Helpers\func;
use App\Models\Alert;
use App\Models\Account;
use App\Models\User;

class TransactionController extends Controller
{
    public function checkTransactionAmount(Request $request){
        $data = $request->json()->all();
        if(!isset($data['content']['transactions'][0])) return response()->json(['error' => 'No transaction found'], 404);
        $transaction = $data['content']['transactions'][0];

        $fireflyIII = new fireflyIII();

        if(config('billDetector.enabled') && $transaction['type'] == 'withdrawal') {
            $bill = $fireflyIII->findBill($transaction['destination_name']);
            if(!$bill) return response()->json(['error' => 'Bill not found'], 404);
            $billPercentage = Account::billOverAmountPercentage($bill);
            if(!$billPercentage){
                $billPercentage = config('alert.bill_over_amount_percentage');
            }
            $maxAmount = $bill->attributes->amount_max;
            if($transaction['amount'] >= ($maxAmount + ($maxAmount * ($billPercentage / 100)))){
                $user = 1;
                $users = User::where('alertBillOverAmountPercentage', 1)->get();
                foreach($users as $user){
                    Alert::billOverMaxAmount($bill, $transaction, $billPercentage, $user);
                }
            }
        }
        
        //account alertAbnormalTransaction
        
        //account alertBillOverMaxAmount

        //account abnormalTransactionPercentage
        
        //acount BillOverMaxAmountPercentage

        // if(config('calAverageTransactions.withdrawal_enabled')) {
        // if(config('calAverageTransactions.deposit_enabled')) {
        // Abnormal transaction
        // }
    }

    public function index($id)
    {
        $fireflyIII = new fireflyIII();
        $transaction = $fireflyIII->getTransaction($id);
        if($transaction == null) return response()->json(['error' => 'Transaction not found'], 404);
        if($transaction->notes){
            $transaction->notes = nl2br($transaction->notes);
            if(func::isRtl($transaction->notes)) $transaction->notes = '<div dir="rtl" class="text-right">'.$transaction->notes.'</div>';
        }
        $transaction->date = \Carbon\Carbon::parse($transaction->date)->diffForhumans();
        return response()->json($transaction);
    }

    public function listTransactions(Request $request)
    {
        $apiToken = session()->get('apiToken', null);
        if($apiToken == null){
            $apiToken = Auth::user()->generateToken('api-token', true, 'apiToken');
        }

        $start = null;
        $end = null;
        $range = null;

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

        if($start == null) $start = date('Y-m-01');
        if($end == null) $end = date('Y-m-t');

        $fireflyIII = new fireflyIII();
        
        $filter = [];
        if(Auth::user()->budgets != '' && Auth::user()->accessAllBudgets == 0){
            $filter['budget_id'] = explode(',', Auth::user()->budgets);
        }

        $transactions = $fireflyIII->getTransactions($start, $end, $filter);
        if(isset($transactions->data)) $transactions = $transactions->data;
        return view('transaction.index', ['apiToken'=>$apiToken, 'transactions'=>$transactions,'range'=>$range]);

    }
}
