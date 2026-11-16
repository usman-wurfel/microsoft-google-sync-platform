<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'microsoft' => [
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
        'apps' => [
            'config' => [
                'client_id' => env('MICROSOFT_CONFIG_CLIENT_ID'),
                'client_secret' => env('MICROSOFT_CONFIG_CLIENT_SECRET'),
            ],
            'mail' => [
                'client_id' => env('MICROSOFT_MAIL_CLIENT_ID'),
                'client_secret' => env('MICROSOFT_MAIL_CLIENT_SECRET'),
            ],
            'phishing' => [
                'client_id' => env('MICROSOFT_PHISHING_CLIENT_ID'),
                'client_secret' => env('MICROSOFT_PHISHING_CLIENT_SECRET'),
            ],
            'user_sync' => [
                'client_id' => env('MICROSOFT_USER_SYNC_CLIENT_ID'),
                'client_secret' => env('MICROSOFT_USER_SYNC_CLIENT_SECRET'),
            ],
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

];
