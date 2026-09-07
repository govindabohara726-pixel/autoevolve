<?php
return [
    'ai' => [
        'base_url' => rtrim((string) env('AI_BASE_URL', 'https://api.deepseek.com'), '/'),
        'key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL', 'deepseek-chat'),
    ],
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'frontend_url' => rtrim((string) env('FRONTEND_URL', env('APP_URL', 'http://localhost')), '/'),
];
