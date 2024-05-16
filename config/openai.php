<?php

return [

    'enabled' => (bool) env('OPENAI_ENABLED', false),

    'url' => env('OPENAI_URL', null),

    'token' => env('OPENAI_TOKEN', null),

    'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 1048),

    'temperature' => (int) env('OPENAI_TEMPERATURE', 1),

    'top_p' => (int) env('OPENAI_TOP_P', 1),

    
];
