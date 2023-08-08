<?php

return [
    /**
     * Default Search Engine
     * Supported:  "collection", "null" "elastic"
     */
    'default'     => env('SCOUT_DRIVER', 'collection'),
    //Soft Deletes
    'soft_delete' => false,
    //索引前缀
    'prefix'      => '',
    //分块处理数据
    'chunk'       => 20,
    //engine Configuration
    'engine'      => [
        'collection' => [
            'driver' => \tp5er\think\scout\Engines\CollectionEngine::class,
        ],
        'null'       => [
            'driver' => \tp5er\think\scout\Engines\NullEngine::class,
        ],
        'elastic'    => [
            'driver' => \tp5er\think\scout\Engines\ElasticEngine::class,
            //https://www.elastic.co/guide/cn/elasticsearch/php/current/_configuration.html
            'hosts'  => [
                [
                    'host'   => 'localhost',
                    'port'   => "9200",
                    'scheme' => null,
                    'user'   => null,
                    'pass'   => null,
                ],
            ],
        ]
    ],
];
