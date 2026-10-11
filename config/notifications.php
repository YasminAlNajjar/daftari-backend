<?php

return [
    'queue' => env('NOTIFICATIONS_QUEUE', 'notifications'),

    'firebase' => [
        'enabled' => (bool) env('FIREBASE_ENABLED', false),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'credentials_base64' => env('FIREBASE_CREDENTIALS_BASE64'),
    ],

    'credit_limit_warning_percentage' => (float) env(
        'NOTIFICATIONS_CREDIT_LIMIT_WARNING_PERCENTAGE',
        80
    ),

    'daily_reminder_time' => env(
        'NOTIFICATIONS_DAILY_REMINDER_TIME',
        '21:00'
    ),

    'timezone' => env(
        'NOTIFICATIONS_TIMEZONE',
        'Asia/Hebron'
    ),
];
