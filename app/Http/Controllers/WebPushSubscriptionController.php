<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class WebPushSubscriptionController extends Controller
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $request->input('contentEncoding', 'aesgcm')
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        $endpoint = $request->input('endpoint');

        if ($endpoint) {
            $request->user()->deletePushSubscription($endpoint);
        } else {
            // optional: delete all for this user
            $request->user()->pushSubscriptions()->delete();
        }

        return response()->json(['ok' => true]);
    }
}