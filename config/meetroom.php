<?php

return [
    'name' => env('APP_NAME', 'MeetRoom'),

    'reverb' => [
        // Public WSS endpoint for browser clients. When running behind a
        // Cloudflare quick tunnel, set REVERB_PUBLIC_WS_URL to the tunnel
        // host (e.g. "xxxx.trycloudflare.com"). Falls back to the current
        // host on the standard Reverb port for local development.
        'public_host' => env('REVERB_PUBLIC_WS_URL', ''),
        'port' => env('REVERB_PORT', 8080),
        'key' => env('REVERB_APP_KEY'),
    ],
];
