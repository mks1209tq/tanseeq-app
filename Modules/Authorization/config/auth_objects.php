<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Codes (ACTVT)
    |--------------------------------------------------------------------------
    |
    | These activity codes represent the standard SAP-style activity types
    | for authorization checks. They are used throughout the authorization
    | system to specify what action is being authorized.
    |
    */

    'activities' => [
        '01' => 'Create',
        '02' => 'Change',
        '03' => 'Display',
        '06' => 'Delete',
        '07' => 'Lock',
        '08' => 'Unlock',
        '09' => 'Post',
        '10' => 'Cancel',
        '11' => 'Print',
        '12' => 'Export',
        '13' => 'Import',
    ],

    /*
    |--------------------------------------------------------------------------
    | Helper Functions
    |--------------------------------------------------------------------------
    |
    | These helper functions provide easy access to activity codes.
    |
    */

    'ACTVT_CREATE' => '01',
    'ACTVT_CHANGE' => '02',
    'ACTVT_DISPLAY' => '03',
    'ACTVT_DELETE' => '06',
    'ACTVT_LOCK' => '07',
    'ACTVT_UNLOCK' => '08',
    'ACTVT_POST' => '09',
    'ACTVT_CANCEL' => '10',
    'ACTVT_PRINT' => '11',
    'ACTVT_EXPORT' => '12',
    'ACTVT_IMPORT' => '13',
];

