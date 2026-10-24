<?php

return [
    'mfa_required' => (bool) env('ASSET_MFA_REQUIRED', false),

    'qr_reports_per_minute' => (int) env('ASSET_QR_REPORTS_PER_MINUTE', 6),

    'uploads' => [
        'disk' => env('ASSET_DISK', 'local'),
        'max_kb' => (int) env('ASSET_MAX_UPLOAD_KB', 5120),
    ],

    'admin' => [
        'email' => env('ASSET_ADMIN_EMAIL'),
        'password' => env('ASSET_ADMIN_PASSWORD'),
    ],

    'demo' => [
        'enabled' => (bool) env('ASSET_DEMO', false),
        'password' => env('ASSET_DEMO_PASSWORD', 'Demo#2026aset'),
    ],
];
