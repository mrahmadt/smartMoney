<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\fireflyIII;
use Illuminate\View\View;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Models\Alert;
use App\Models\User;
use App\Notifications\WebPush;
use App\Helpers\func;

class AlertController extends Controller
{
    public function test(Request $request){
        Alert::test();
    }
    public function create(Request $request)
    {
        $title = $request->input('title', 'Alert');
        $message = $request->input('message','Unknown alert');
        $type = $request->input('type', 'info');
        $user_id = $request->input('user_id');
        $pushNotification = $request->input('pushNotification', 1);

        
        if($title == null || $message == null){
            return response()->json(['status' => 'error', 'message' => 'Title and message are required']);
        }


        if($user_id){
            $user = User::find($user_id);
            $alert = new Alert();
            $alert->title = $title;
            $alert->user_id = $user_id;
            $alert->message = $message;
            $alert->type = $type;
            $alert->save();
            if($pushNotification) $user->notify(new WebPush($title, $message));
        }else{
            $users = User::all();
            foreach($users as $user){
                $alert = new Alert();
                $alert->title = $title;
                $alert->user_id = $user->id;
                $alert->message = $message;
                $alert->type = $type;
                $alert->save();
                if($pushNotification) $user->notify(new WebPush($title, $message));
            }
        }
        
        return response()->json(['status' => 'success']);

    }


    public function show($id)
    {
        $alert = Alert::where(['user_id'=> Auth::User()->id,'id'=>$id])->first();
        if($alert == null) return response()->json(['error' => 'Alert not found'], 404);
        $alert->date = \Carbon\Carbon::parse($alert->created_at)->diffForhumans();
        return view('alerts.show', ['alert'=>$alert]);
    }

    public function index(Request $request){
        $apiToken = session()->get('apiToken', null);
        if($apiToken == null){
            $apiToken = Auth::user()->generateToken('api-token', true, 'apiToken');
        }

        $start = date('Y-m-01');
        $end = date('Y-m-t');
        $fireflyIII = new fireflyIII();
        $budget_id = null;
        $budget = null;
        if(Auth::user()->budgets!='' && is_array(Auth::user()->budgets)){
            $budget_id = Auth::user()->budgets[0];
        }
        if($budget_id){
            $budget = $fireflyIII->getBudget($budget_id, $start, $end);
            $budget = $budget->data;
        }
        $alerts = Alert::where(['user_id'=> Auth::User()->id])->orderBy('id','DESC')->limit(20)->get();
        return view('alerts.index', ['alerts'=>$alerts, 'budget' => $budget]);
        
    }
}
