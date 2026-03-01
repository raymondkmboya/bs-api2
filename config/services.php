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

    /*
    |--------------------------------------------------------------------------
    | NMB Invoice Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for NMB (National Microfinance Bank) invoice generation
    | API integration for payment processing
    |
    */

    'nmb' => [
        'api_url' => env('NMB_API_URL', 'https://api.nmb.co.tz/v1'),
        'api_key' => env('NMB_API_KEY'),
        'merchant_code' => env('NMB_MERCHANT_CODE'),
        'timeout' => env('NMB_TIMEOUT', 30),
        'retry_attempts' => env('NMB_RETRY_ATTEMPTS', 3),
        'webhook_secret' => env('NMB_WEBHOOK_SECRET'),

        // Invoice settings
        'default_currency' => 'TZS',
        'default_due_days' => 7,
        'max_amount' => 10000000, // 10 million TZS

        // Callback URLs
        'success_url' => env('APP_URL') . '/payments/nmb/success',
        'failure_url' => env('APP_URL') . '/payments/nmb/failure',
        'webhook_url' => env('APP_URL') . '/api/webhooks/nmb',
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS gateway integration
    | Supports multiple SMS providers
    |
    */

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'default'),

        // Default SMS provider
        'api_url' => env('SMS_API_URL', 'https://api.smsprovider.co.tz'),
        'api_key' => env('SMS_API_KEY'),
        'sender_id' => env('SMS_SENDER_ID', 'BRITISH'),
        'timeout' => env('SMS_TIMEOUT', 30),
        'retry_attempts' => env('SMS_RETRY_ATTEMPTS', 3),

        // Bulk SMS settings
        'batch_size' => env('SMS_BATCH_SIZE', 100),
        'batch_delay' => env('SMS_BATCH_DELAY', 1000000), // microseconds

        // Message settings
        'max_length' => 160,
        'max_chunks' => 5,

        // Rate limiting
        'rate_limit' => env('SMS_RATE_LIMIT', 10), // messages per minute

        // Callback URLs
        'webhook_url' => env('APP_URL') . '/api/webhooks/sms',

        // Alternative providers
        'providers' => [
            'twilio' => [
                'api_url' => env('TWILIO_API_URL', 'https://api.twilio.com/2010-04-01'),
                'account_sid' => env('TWILIO_ACCOUNT_SID'),
                'auth_token' => env('TWILIO_AUTH_TOKEN'),
                'from_number' => env('TWILIO_FROM_NUMBER'),
            ],

            'africastalking' => [
                'api_url' => env('AT_API_URL', 'https://api.africastalking.com/version1/messaging'),
                'api_key' => env('AT_API_KEY'),
                'username' => env('AT_USERNAME'),
                'from_number' => env('AT_FROM_NUMBER'),
            ],
        ],
    ],

];
