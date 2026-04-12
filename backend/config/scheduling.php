<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Require a confirmed centre time block before session confirm
    |--------------------------------------------------------------------------
    |
    | When true, only applies to students in the "centre support" flow:
    | in-person programme AND users.support = true. If no active blocks
    | exist for the course centre, confirmation is still allowed.
    |
    */
    'require_centre_block_for_confirm' => env('SCHEDULING_REQUIRE_CENTRE_BLOCK', false),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL hint (seconds) exposed in session-options API meta
    |--------------------------------------------------------------------------
    */
    'session_options_cache_ttl_seconds' => (int) env('SCHEDULING_SESSION_OPTIONS_TTL', 15),

];
