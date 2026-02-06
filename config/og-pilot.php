<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OG Pilot API Key
    |--------------------------------------------------------------------------
    |
    | Your OG Pilot API key used to sign JWT tokens for image generation.
    | You can find this in your OG Pilot dashboard.
    |
    */
    'api_key' => env('OG_PILOT_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | OG Pilot Domain
    |--------------------------------------------------------------------------
    |
    | The domain associated with your OG Pilot account. This is used as
    | the issuer (iss) claim in the JWT token.
    |
    */
    'domain' => env('OG_PILOT_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The OG Pilot API base URL. You typically don't need to change this
    | unless you're using a custom deployment.
    |
    */
    'base_url' => env('OG_PILOT_BASE_URL', 'https://ogpilot.com'),

    /*
    |--------------------------------------------------------------------------
    | Connection Timeout
    |--------------------------------------------------------------------------
    |
    | The number of seconds to wait while trying to connect to the OG Pilot
    | API server.
    |
    */
    'connect_timeout' => env('OG_PILOT_CONNECT_TIMEOUT', 5.0),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The total number of seconds to wait for a response from the OG Pilot
    | API server.
    |
    */
    'timeout' => env('OG_PILOT_TIMEOUT', 10.0),

    /*
    |--------------------------------------------------------------------------
    | Strip Extensions
    |--------------------------------------------------------------------------
    |
    | When enabled, file extensions are removed from resolved paths so that
    | /docs.md, /docs.php, and /docs all map to the same "/docs" path.
    | Dotfiles like /.hidden are left unchanged.
    |
    */
    'strip_extensions' => env('OG_PILOT_STRIP_EXTENSIONS', true),
];
