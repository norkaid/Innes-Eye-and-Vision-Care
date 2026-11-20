<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    /*
    | Paystack (card and mobile money payments). Test keys start with pk_test_ / sk_test_.
    | The secret key is only ever used on the server; never commit it.
    */
    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'currency' => env('PAYSTACK_CURRENCY', 'GHS'),
    ],

    /*
    | SMS to customers. SMS_DRIVER: log (PC: writes to the log), arkesel, hubtel, twilio, or empty (off).
    | SMS_SENDER_ID must be registered/approved with the provider (max 11 characters).
    */
    'sms' => [
        'driver' => env('SMS_DRIVER'),
        'sender_id' => env('SMS_SENDER_ID', 'InnesEye'),
        'country_code' => env('SMS_COUNTRY_CODE', '233'),
        'arkesel_key' => env('ARKESEL_API_KEY'),
        'hubtel_client_id' => env('HUBTEL_CLIENT_ID'),
        'hubtel_client_secret' => env('HUBTEL_CLIENT_SECRET'),
        'twilio_sid' => env('TWILIO_SID'),
        'twilio_token' => env('TWILIO_TOKEN'),
        'twilio_from' => env('TWILIO_FROM'),
    ],

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

];
