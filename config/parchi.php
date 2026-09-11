<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maximum Concurrent Database Searches
    |--------------------------------------------------------------------------
    |
    | The safe limit of simultaneous heavy voter database search queries.
    | Any incoming request exceeding this threshold is gracefully placed
    | into the Virtual Wait & Hold Queue rather than dropping connections.
    |
    */
    'max_concurrent_searches' => (int) env('PARCHI_MAX_CONCURRENT_SEARCHES', 30),

    /*
    |--------------------------------------------------------------------------
    | Enable Virtual Wait & Hold Queue
    |--------------------------------------------------------------------------
    |
    | When true, excess requests under heavy peak loads are put in the
    | virtual waiting room with live position and automated progress.
    |
    */
    'queue_enabled' => (bool) env('PARCHI_QUEUE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Voter Slip Result Cache TTL (Seconds)
    |--------------------------------------------------------------------------
    |
    | Time-to-live for cached voter slip query results. Enables sub-2ms
    | repeat searches for the same CNIC without hitting MySQL.
    |
    */
    'cache_ttl' => (int) env('PARCHI_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Virtual Queue Ticket TTL (Seconds)
    |--------------------------------------------------------------------------
    |
    | Maximum lifespan of an active waiting ticket before auto-cleanup.
    |
    */
    'ticket_ttl' => (int) env('PARCHI_TICKET_TTL', 180),

    /*
    |--------------------------------------------------------------------------
    | Client Poll Interval (Milliseconds)
    |--------------------------------------------------------------------------
    |
    | The frequency with which the waiting room client checks ticket status.
    |
    */
    'poll_interval_ms' => 1500,
];
