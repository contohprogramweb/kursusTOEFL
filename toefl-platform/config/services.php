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

    // Google Cloud Speech-to-Text API
    'google_cloud' => [
        'api_key' => env('GOOGLE_CLOUD_API_KEY'),
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
    ],

    // OpenAI API for AI Grading
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 2500),
    ],

    // Anthropic Claude API (alternative to OpenAI)
    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'model' => env('CLAUDE_MODEL', 'claude-3-opus-20240229'),
        'max_tokens' => env('CLAUDE_MAX_TOKENS', 4096),
    ],

    // Firebase Cloud Messaging (FCM)
    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'server_key' => env('FIREBASE_SERVER_KEY'),
    ],

    // Twilio SMS API
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from_number' => env('TWILIO_FROM_NUMBER'),
    ],

    // WhatsApp API (Fonnte or similar)
    'whatsapp' => [
        'api_key' => env('WHATSAPP_API_KEY'),
        'endpoint' => env('WHATSAPP_ENDPOINT', 'https://api.fonnte.com/send'),
    ],

];
