<?php

return [

    /*
     * Shared secret for the public mobile (Android/iOS) API.
     * Send it as: Authorization: Bearer <key>  (or ?api_key=<key>).
     * Generate a strong random value and keep it out of version control.
     */
    'api_key' => env('MOBILE_API_KEY'),
];
