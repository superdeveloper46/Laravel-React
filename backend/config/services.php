<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'fcm' => [
        'api_host' => env('FCM_API_HOST', 'https://fcm.googleapis.com/fcm/send'),
        'api_key' => env('FCM_API_KEY', 'AAAAJ7GOhrk:APA91bFx15tS42sLFJGsCiiMQ4dTdlK2DFnFp52NBJvgahZQJ6wjrvC9vlxxm6y8PEXOHa6uJ5_cbu7iL6D6UfGsZpfJa73Kohq5HMpZyswHEPtexMnr0Raf2YUDRo7OQtWzDVWmf-IA'),
    ]

];
