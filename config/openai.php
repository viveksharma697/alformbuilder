<?php

return [
    'api_key' => env('OPENROUTER_API_KEY', env('OPENAI_API_KEY', '')),
    'base_uri' => env('AI_BASE_URI', 'openrouter.ai/api/v1'),
    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 60),
    'model' => env('AI_MODEL', 'meta-llama/llama-3.1-8b-instruct:free'),
    'organization' => env('OPENAI_ORGANIZATION', null),
    'site_url' => env('APP_URL', 'http://localhost:8000'),
    'site_name' => env('APP_NAME', 'AI Form Builder'),
];
