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

    /*
    |--------------------------------------------------------------------------
    | Default Thumbnail
    |--------------------------------------------------------------------------
    |
    | This config is used in case if the file is not supported
    | or there is an error in the thumbnail creation process
    | It will return the default path.
    |
    */
    'default' => [
        'enable' => true,
        'path' => '/vendor/laravel_thumbnail/Thumbnail-default.svg'
    ],

    /*
    |--------------------------------------------------------------------------
    | Options For Thumbnail
    |--------------------------------------------------------------------------
    |
    | This config is used to change some properties of the thumbnail image during creation
    | Only supports some properties such as: format, height, width, layer.
    | If other attributes are added, the package is not supported
    |
    */
    'options' => [
        'format' => 'jpg',
        'height' => 400,
        'width' => 400,
        'layer' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore extensions
    |--------------------------------------------------------------------------
    |
    | By default, the package will generate thumbnails if the extension file is of the following formats: doc, docx, xls, xlsx, gif, jpg, jpeg, png.
    | If you want to exclude any extension, you can use this config to exclude.
    | E.g: 'ignore_extensions' => ['gif', 'jpg']
    |
    */
    'ignore_extensions' => [],

    /*
    |--------------------------------------------------------------------------
    | Model Table Name
    |--------------------------------------------------------------------------
    | This model will be used to save thumbnail.
    | and extend Illuminate\Database\Eloquent\Model.
     */
    'thumbnail_model' => \PhuocNguyen\Thumbnail\Models\Thumbnail::class,

    /*
    |--------------------------------------------------------------------------
    | Model Table Name
    |--------------------------------------------------------------------------
    | This is the name of the table that will be created by the migration and
    | used by the Thumbnail model shipped with this package.
     */
    'table_name' => 'thumbnails',
];
