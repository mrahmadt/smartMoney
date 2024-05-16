<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Notifications\WebPush;

use App\Models\User;

class NotificationManagerController extends Controller
{

    public function send(Request $request)
    {
        $title = $request->input('title');
        $body = $request->input('body');
        $user_id = $request->input('user_id');
        if($title == null || $body == null){
            return response()->json(['status' => 'error', 'message' => 'Title and body are required']);
        }
        if($user_id){
            $user = User::find($user_id);
            $user->notify(new WebPush($title, $body));
        }else{
            $users = User::all();
            foreach($users as $user){
                $user->notify(new WebPush($title, $body));
            }
        }
        
        return response()->json(['status' => 'success']);
    }


    public function subscribe(Request $req)
    {
        $user = Auth::user();

        $subscription = $user->updatePushSubscription(
            $req->post('endpoint'),
            $req->post('public_key'),
            $req->post('auth_token'),
            $req->post('encoding'),
        );
        return response()->json(['message' => 'Subscribed!']);
    }

    public function unsubscribe(Request $req)
    {
        // $user = User::find(1);
        $user = Auth::user();

        $user->deletePushSubscription($req->post('endpoint'));

        return response()->json(['message' => 'Unsubscribed!']);
    }
}