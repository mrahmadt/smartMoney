<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'device_token' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:ios,android'],
        ]);

        DeviceToken::updateOrCreate(
            ['device_token' => $data['device_token']],
            [
                'user_id' => $data['user_id'],
                'platform' => $data['platform'],
            ]
        );

        return response()->json(['ok' => true]);
    }
}
