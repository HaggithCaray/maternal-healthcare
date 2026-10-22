<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup folder
    |--------------------------------------------------------------------------
    |
    | Where the daily zips are written on this PC. It is left out of the
    | backups themselves.
    |
    */

    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    /*
    |--------------------------------------------------------------------------
    | Backup password
    |--------------------------------------------------------------------------
    |
    | Every file in a backup zip is encrypted with AES-256 using this password.
    | A backup holds every patient's records and the .env (app key, database
    | password), so without it anyone who copies the zip can read everything.
    | Open a protected zip with 7-Zip (Windows' built-in zip cannot do AES).
    | Keep the password somewhere safe away from this PC: lose it and the
    | backups cannot be restored.
    |
    */

    'password' => env('BACKUP_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Second copy
    |--------------------------------------------------------------------------
    |
    | A folder that every new backup is also copied to, e.g. a USB drive or a
    | synced cloud folder, so a failed disk on this PC does not take the
    | backups with it. Copies are pruned on the same 30-day schedule.
    |
    */

    'copy_to' => env('BACKUP_COPY_PATH'),

];
