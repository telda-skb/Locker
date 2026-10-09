<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        /*
        |--------------------------------------------------------------------------
        | LOCAL STORAGE
        |--------------------------------------------------------------------------
        |
        | Penyimpanan lokal untuk lampiran surat secara privat.
        | File tersimpan di storage/app/private.
        | Akses file dilakukan melalui controller Laravel.
        |
        */

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => true,
            'report' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | PUBLIC STORAGE
        |--------------------------------------------------------------------------
        |
        | Untuk file publik yang memang diperlukan aplikasi.
        | Tidak digunakan untuk lampiran surat privat.
        |
        */

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(
                env('APP_URL', 'http://localhost'),
                '/'
            ) . '/storage',
            'visibility' => 'public',
            'throw' => true,
            'report' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];