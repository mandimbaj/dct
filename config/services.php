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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'groq' => [
        'key' => env('GROQ_API_KEY', ''),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
    ],

    'microsoft_entra' => [
        'enabled' => (bool) env('MICROSOFT_ENTRA_ENABLED', false),
        'tenant' => env('MICROSOFT_ENTRA_TENANT', ''),
        'client_id' => env('MICROSOFT_ENTRA_CLIENT_ID', ''),
        'client_secret' => env('MICROSOFT_ENTRA_CLIENT_SECRET', ''),
        'redirect_uri' => env('MICROSOFT_ENTRA_REDIRECT_URI', ''),
        'scopes' => array_values(array_filter(preg_split('/\s+/', (string) env('MICROSOFT_ENTRA_SCOPES', 'openid profile email User.Read')) ?: [])),
        'local_login_enabled' => (bool) env('MICROSOFT_ENTRA_LOCAL_LOGIN_ENABLED', true),
    ],

];
