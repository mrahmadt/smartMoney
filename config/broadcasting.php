<?php

return [

    'default' => env('BROADCAST_CONNECTION', 'log'),

    'connections' => [

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

        'apn' => [
            'key_id' => env('APN_KEY_ID'),
            'team_id' => env('APN_TEAM_ID'),
            'app_bundle_id' => env('APN_BUNDLE_ID'),
            'private_key_path' => env('APN_PRIVATE_KEY', storage_path('app/apns/AuthKey.p8')),
            'production' => env('APN_PRODUCTION', false),
        ],

    ],

];
