<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Optional tracking
    |--------------------------------------------------------------------------
    |
    | The public site currently has no analytics or advertising tags. Keep the
    | preference interface dormant until the business and legal approval for a
    | specific optional integration is recorded.
    |
    */
    'optional_tracking_enabled' => env('PRIVACY_OPTIONAL_TRACKING_ENABLED', false),

    'preferences_cookie' => 'igna_privacy_preferences',
    'preferences_version' => 1,
    'preferences_lifetime_minutes' => 60 * 24 * 180,
];
