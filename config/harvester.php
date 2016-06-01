<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Harvester Configuration
    |--------------------------------------------------------------------------
    |
    | These settings can be used to set extended data types used within the
    | database schema for AssetType, DateType, LocationType, TermType and
    | TextType. These settings allow the havester to be more flexable.
    |
    */
    'types' => [

        'asset' => [
            ['name' => 'thumb', 'desc' => '165px max width'],
            ['name' => 'medium', 'desc' => '360px max width'],
            ['name' => 'large', 'desc' => '1140px max width'],
            ['name' => 'original', 'desc' => 'Original resolution for download.'],
            ['name' => 'protected', 'desc' => 'Rights restricted asset.'],
            ['name' => 'dzi', 'desc' => 'xml description for dzi'],
            ['name' => 'jsonp', 'desc' => 'jsonp description for dzi']
        ],

        'date' => [
            ['name' => 'decade', 'desc' => null],
            ['name' => 'year', 'desc' => null]
        ],

        'location' => [
            ['name' => 'building', 'desc' => '']
        ],

        'term' => [
            ['name' => 'medium', 'desc' => 'material used to make the work'],
            ['name' => 'support', 'desc' => 'material that the work is on'],
            ['name' => 'dynasty', 'desc' => null],
            ['name' => 'period', 'desc' => null],
            ['name' => 'authoriser', 'desc' => null]
        ],

        'text' => [
            ['name' => 'attribution', 'desc' => null]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Size Configurations
    |--------------------------------------------------------------------------
    |
    | These items set the width for derivitive images.
    |
    */
   'image' => [

        // choose between gd and imagick
        'driver' => 'imagick',

        'sizes' => [
            'thumb' => 165,
            'medium' => 360,
            'large' => 1140
        ],

        'protected' => [
            'thumb' => [
                'width' => 200,
                'height' => 250
            ],
            'medium' => [
                'width' => 768,
                'height' => 960
            ]
        ]

    ]
];
