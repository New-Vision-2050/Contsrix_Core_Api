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
    'mora_sms' => [
        "api_key" => env("SMS_MORA_KEY",""),
        "username" => env("SMS_MORA_USER",""),
        "sender" => env("SMS_MORA_SENDER",""),
        "base_url"=>env("SMS_MORA_BASE_URL","https://mora-sa.com/api/v1/sendsms")
    ],
    'twilio' => [
        'sid' => env('TWILIO_SID', ''),
        'auth_token' => env('TWILIO_AUTH_TOKEN', ''),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', ''), // e.g. whatsapp:+14155238886
    ],
    'twilio_voice' => [
        'sid' => env('TWILIO_SID', ''),
        'api_key_sid' => env('TWILIO_VOICE_API_KEY_SID', ''),
        'api_key_secret' => env('TWILIO_VOICE_API_KEY_SECRET', ''),
        'from' => env('TWILIO_VOICE_FROM', env('TWILIO_WHATSAPP_FROM', '')),
    ],
    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'calendarific' => [
        'api_key' => env('CALENDARIFIC_API_KEY', 'G2XGP0u6w8FihkwuRxUDzOjjT321JeJ5'),
        'base_url' => env('CALENDARIFIC_BASE_URL', 'https://calendarific.com/api/v2'),
    ],

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS', public_path('firebase_credentials.json')),
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS Rekognition (Face Verification for Attendance)
    |--------------------------------------------------------------------------
    |
    | These are DEDICATED AWS credentials for the Rekognition face-compare
    | API and are intentionally separate from AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY
    | above, because those are actually DigitalOcean Spaces credentials used
    | for file storage (see config/filesystems.php + devops/deploy.sh). Rekognition
    | only exists on real AWS, so it needs its own real AWS IAM user/keys.
    |
    */
    'rekognition' => [
        'enabled' => env('FACE_RECOGNITION_ENABLED', false),
        'key' => env('AWS_REKOGNITION_ACCESS_KEY_ID'),
        'secret' => env('AWS_REKOGNITION_SECRET_ACCESS_KEY'),
        'region' => env('AWS_REKOGNITION_REGION', 'us-east-1'),
        // Minimum % similarity (0-100) required to consider it a match.
        'similarity_threshold' => (float) env('FACE_MATCH_SIMILARITY_THRESHOLD', 80),
    ],
];
