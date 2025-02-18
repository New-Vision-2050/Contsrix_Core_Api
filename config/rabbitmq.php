<?php

return [
    'actions' => [
        [
            'action' => 'test',
            'class' => RabbitMQ\Handlers\RabbitMQHandler::class
        ]
    ],
    'driver' => 'rabbitmq',
    'queue' => env('RABBITMQ_QUEUE', 'default'),
    'connection' => PhpAmqpLib\Connection\AMQPStreamConnection::class,
    'hosts' => [
        [
            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => env('RABBITMQ_PORT', 5672),
            'user' => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'vhost' => env('RABBITMQ_VHOST', '/'),
        ]
    ],
    'options' => [
        'exchanges' => [
            'user_events' => [
                'name' => 'user_events_exchange',
                'type' => 'fanout', // direct, topic, fanout
                'passive' => false,
                'durable' => true,
                'auto_delete' => false,
            ]
        ],

        'queues' => [
            'user_events' => [
                'name' => 'user_events_queue',
                'exchange' => 'user_events', // Reference to exchange
                'routing_key' => 'user.registration',
                'passive' => false,
                'durable' => true,
                'exclusive' => false,
                'auto_delete' => false,
                'arguments' => [
                    'x-dead-letter-exchange' => 'dead_letter_exchange',
                    'x-message-ttl' => 86400000, // 24 hours in milliseconds
                ],
            ]
        ],

        // Dead letter configuration for failed messages
        'dead_letter' => [
            'exchange' => [
                'name' => 'dead_letter_exchange',
                'type' => 'direct',
                'durable' => true,
            ],
            'queue' => [
                'name' => 'dead_letter_queue',
                'durable' => true,
            ],
        ],

        // Retry mechanism configuration
        'retry' => [
            'max_attempts' => 3,
            'initial_delay' => 1000, // milliseconds
            'backoff_multiplier' => 2,
        ],
    ]
];
