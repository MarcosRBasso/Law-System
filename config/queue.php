<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    */

    'default' => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

        // Custom queue configurations for different types of jobs
        'court-sync' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'court-sync',
            'retry_after' => 300, // 5 minutes
            'block_for' => null,
            'after_commit' => false,
        ],

        'notifications' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'notifications',
            'retry_after' => 60,
            'block_for' => null,
            'after_commit' => false,
        ],

        'documents' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'documents',
            'retry_after' => 180, // 3 minutes
            'block_for' => null,
            'after_commit' => false,
        ],

        'payments' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'payments',
            'retry_after' => 120, // 2 minutes
            'block_for' => null,
            'after_commit' => false,
        ],

        'reports' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'reports',
            'retry_after' => 600, // 10 minutes
            'block_for' => null,
            'after_commit' => false,
        ],

        'high-priority' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'high-priority',
            'retry_after' => 60,
            'block_for' => null,
            'after_commit' => false,
        ],

        'low-priority' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'low-priority',
            'retry_after' => 300,
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];