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
use App\Models\Transaction;
use App\Models\AverageTransaction;

class TransactionController extends Controller
{
    public function newTransactionWebhook(Request $request){
        $data = $request->json()->all();
        if(!isset($data['trigger']) || $data['trigger'] != 'STORE_TRANSACTION') return response()->json(['error' => 'Invalid trigger'], 400);
        if(!isset($data['content']['transactions'][0])) return response()->json(['error' => 'No transaction found'], 404);
        $transaction = $data['content']['transactions'][0];

        $fireflyIII = new fireflyIII();

        Transaction::checkBillOverMaxAmount($transaction, $fireflyIII);
        Transaction::abnormalTransaction($transaction, $fireflyIII);
        Alert::newTransaction($transaction);
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
        $budget_id = null;
        $budget = null;
        if(Auth::user()->budgets!='' && is_array(Auth::user()->budgets)){
            $filter['budget_id'] = Auth::user()->budgets;
            $budget_id = Auth::user()->budgets[0];
        }
        if($budget_id){
            $budget = $fireflyIII->getBudget($budget_id, $start, $end);
            $budget = $budget->data;
        }

        $limit = 20;
        $transactions = $fireflyIII->getTransactions($start, $end, $filter, $limit);
        if(isset($transactions->data)) $transactions = $transactions->data;
        return view('transaction.index', ['apiToken'=>$apiToken, 'transactions'=>$transactions,'range'=>$range, 'budget' => $budget]);

    }
}
