<?php

return [
    'ai' => [
        'base_url' => rtrim((string) env('AI_BASE_URL', 'https://api.deepseek.com'), '/'),
        'key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL', 'deepseek-chat'),
    ],
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),
];
