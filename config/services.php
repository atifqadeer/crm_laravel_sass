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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Shared secret for the desktop MicroSIP CRM integration endpoints.
    // Set MICROSIP_API_TOKEN in .env, then point MicroSIP's crmApiUrl at
    // https://your-crm/api/sip/<that-token>
    'microsip' => [
        'token' => env('MICROSIP_API_TOKEN'),
    ],

    // Click-to-dial collision lock: how long (minutes) a number stays locked
    // to the dialing agent before auto-expiring (also released when the call
    // is logged). Set DIAL_LOCK_MINUTES in .env to override.
    'dialing' => [
        'lock_minutes' => env('DIAL_LOCK_MINUTES', 5),
    ],

];
