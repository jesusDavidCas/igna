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

    'google_drive' => [
        'enabled' => env('GOOGLE_DRIVE_ENABLED', false),
        'service_account_json' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON'),
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_DRIVE_REDIRECT_URI'),
        'root_folder_id' => env('GOOGLE_DRIVE_ROOT_FOLDER_ID'),
        'allowed_download_hosts' => [
            'drive.google.com',
            'docs.google.com',
            'googleusercontent.com',
        ],
    ],

    'poppler' => [
        'enabled' => env('POPPLER_ENABLED', true),
        'pdftoppm' => env('POPPLER_PDFTOPPM_PATH'),
    ],

    'ghostscript' => [
        'gs' => env('GHOSTSCRIPT_PATH'),
    ],

    'turnstile' => [
        // Disabled by default. Enabling without every required setting fails closed.
        'enabled' => env('REQUEST_ANTISPAM_ENABLED', false),
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'expected_hostname' => env('TURNSTILE_EXPECTED_HOSTNAME'),
        'expected_action' => env('TURNSTILE_EXPECTED_ACTION', 'service_request'),
        'connect_timeout' => env('TURNSTILE_CONNECT_TIMEOUT', 2),
        'timeout' => env('TURNSTILE_TIMEOUT', 5),
        'requests_per_minute' => env('REQUEST_ANTISPAM_PER_MINUTE', 5),
        'requests_per_hour' => env('REQUEST_ANTISPAM_PER_HOUR', 20),
    ],

];
