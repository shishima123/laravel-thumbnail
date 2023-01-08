<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you will configure the path to save the temporary file,
    | the purpose to use in the process of creating the thumbnail file
    |
    */
    'disks' => [
        'temp_thumbnail' => [
            'driver' => 'local',
            'root' => storage_path('app/public/temp_thumbnail'),
        ],

        'thumbnail' => [
            'driver' => 'local',
            'root' => storage_path('app/public/thumbnail'),
            'url' => '/storage/thumbnail',
            'visibility' => 'public',
            'throw' => false,
        ]
    ],

    'default_thumbnail' => true,

    'default_path' => public_path('vendor/laravel_thumbnail/Thumbnail-default.svg'),

    'thumbnail_format' => 'jpg',

    'thumbnail_height' => 400,

    'thumbnail_width' => 400,

    'thumbnail_layer' => 14,

    'ignore_extension' => [],

    'table_name' => 'thumbnails',

    'table_fillable' => [
        'name',
        'original_name',
        'path',
        'thumbnailable_id',
        'thumbnailable_type'
    ]
];
