<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sistema Jurídico Configuration
    |--------------------------------------------------------------------------
    */

    'version' => env('APP_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Document Management
    |--------------------------------------------------------------------------
    */
    'documents' => [
        'disk' => env('DOCUMENTS_DISK', 'local'),
        'path' => env('DOCUMENTS_PATH', 'documents'),
        'max_size' => env('MAX_DOCUMENT_SIZE', 10240), // KB
        'allowed_types' => [
            'pdf', 'doc', 'docx', 'txt', 'rtf',
            'jpg', 'jpeg', 'png', 'gif',
            'xls', 'xlsx', 'csv'
        ],
        'encrypt' => env('ENCRYPT_DOCUMENTS', true),
        'versioning' => true,
        'audit_trail' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Digital Signature
    |--------------------------------------------------------------------------
    */
    'signature' => [
        'providers' => [
            'certisign' => [
                'enabled' => !empty(env('CERTISIGN_API_KEY')),
                'api_url' => env('CERTISIGN_API_URL'),
                'api_key' => env('CERTISIGN_API_KEY'),
                'certificate_path' => env('CERTISIGN_CERTIFICATE_PATH'),
            ],
            'serasa' => [
                'enabled' => !empty(env('SERASA_API_KEY')),
                'api_url' => env('SERASA_API_URL'),
                'api_key' => env('SERASA_API_KEY'),
                'certificate_path' => env('SERASA_CERTIFICATE_PATH'),
            ],
        ],
        'default_provider' => 'certisign',
        'timeout' => 30, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Court Systems Integration
    |--------------------------------------------------------------------------
    */
    'courts' => [
        'pje' => [
            'enabled' => !empty(env('PJE_API_KEY')),
            'api_url' => env('PJE_API_URL'),
            'api_key' => env('PJE_API_KEY'),
            'certificate_path' => env('PJE_CERTIFICATE_PATH'),
            'sync_interval' => 'daily',
        ],
        'eproc' => [
            'enabled' => !empty(env('EPROC_API_KEY')),
            'api_url' => env('EPROC_API_URL'),
            'api_key' => env('EPROC_API_KEY'),
            'certificate_path' => env('EPROC_CERTIFICATE_PATH'),
            'sync_interval' => 'daily',
        ],
        'saj' => [
            'enabled' => !empty(env('SAJ_API_KEY')),
            'api_url' => env('SAJ_API_URL'),
            'api_key' => env('SAJ_API_KEY'),
            'certificate_path' => env('SAJ_CERTIFICATE_PATH'),
            'sync_interval' => 'daily',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS & Communication
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'default_provider' => 'twilio',
        'providers' => [
            'twilio' => [
                'enabled' => !empty(env('TWILIO_SID')),
                'sid' => env('TWILIO_SID'),
                'token' => env('TWILIO_TOKEN'),
                'from' => env('TWILIO_FROM'),
            ],
            'zenvia' => [
                'enabled' => !empty(env('ZENVIA_API_TOKEN')),
                'api_token' => env('ZENVIA_API_TOKEN'),
                'from' => env('ZENVIA_FROM'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OAB Integration
    |--------------------------------------------------------------------------
    */
    'oab' => [
        'api_url' => env('OAB_API_URL', 'https://api.oab.org.br'),
        'api_key' => env('OAB_API_KEY'),
        'cache_ttl' => 86400, // 24 hours
        'validation_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Deadline Calculation
    |--------------------------------------------------------------------------
    */
    'deadlines' => [
        'business_days_only' => true,
        'exclude_holidays' => true,
        'default_alert_days' => 3,
        'auto_calculate' => true,
        'types' => [
            'appeal' => 15,
            'response' => 15,
            'execution_payment' => 15,
            'execution_obligation_to_do' => 30,
            'execution_obligation_not_to_do' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Time Tracking
    |--------------------------------------------------------------------------
    */
    'time_tracking' => [
        'minimum_increment' => 15, // minutes
        'rounding_method' => 'up', // up, down, nearest
        'auto_stop_inactive' => true,
        'inactive_timeout' => 3600, // seconds (1 hour)
        'default_hourly_rate' => 200.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing & Invoicing
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'auto_generate_number' => true,
        'number_format' => 'YYYYMM9999',
        'default_due_days' => 30,
        'late_fee_percentage' => 2.0,
        'interest_rate_monthly' => 1.0,
        'tax_calculation' => 'none', // none, percentage, fixed
        'tax_rate' => 0.0,
        'currency' => 'BRL',
        'currency_symbol' => 'R$',
    ],

    /*
    |--------------------------------------------------------------------------
    | Financial Settings
    |--------------------------------------------------------------------------
    */
    'financial' => [
        'auto_reconciliation' => true,
        'reconciliation_tolerance' => 0.01, // R$ 0,01
        'bank_statement_formats' => ['ofx', 'csv', 'xml'],
        'default_categories' => [
            'income' => [
                'Honorários Advocatícios',
                'Consultoria Jurídica',
                'Taxas Administrativas',
            ],
            'expense' => [
                'Custas Processuais',
                'Despesas Operacionais',
                'Impostos e Taxas',
                'Salários e Encargos',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search & Indexing
    |--------------------------------------------------------------------------
    */
    'search' => [
        'elasticsearch' => [
            'hosts' => explode(',', env('ELASTICSEARCH_HOSTS', 'localhost:9200')),
            'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'juridico'),
            'auto_index' => true,
        ],
        'cache_ttl' => env('SEARCH_CACHE_TTL', 1800),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'password_policy' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
        ],
        'session_timeout' => 7200, // 2 hours
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'two_factor_enabled' => false,
        'audit_log_retention' => 365, // days
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'cache_ttl' => env('CACHE_TTL', 3600),
        'api_rate_limit' => env('API_RATE_LIMIT', 60),
        'pagination' => [
            'default_per_page' => 20,
            'max_per_page' => 100,
        ],
        'lazy_loading' => true,
        'image_optimization' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('BACKUP_ENABLED', true),
        'schedule' => env('BACKUP_SCHEDULE', '0 2 * * *'),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'disk' => env('BACKUP_DISK', 's3'),
        'encrypt' => true,
        'include_files' => true,
        'exclude_tables' => ['activity_log', 'notifications'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'sentry_enabled' => !empty(env('SENTRY_LARAVEL_DSN')),
        'prometheus_enabled' => env('PROMETHEUS_ENABLED', false),
        'grafana_enabled' => env('GRAFANA_ENABLED', false),
        'health_checks' => [
            'database' => true,
            'redis' => true,
            'elasticsearch' => true,
            'storage' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'channels' => ['database', 'mail', 'sms'],
        'deadline_alerts' => [
            'enabled' => true,
            'advance_days' => [1, 3, 7],
            'channels' => ['database', 'mail'],
        ],
        'invoice_reminders' => [
            'enabled' => true,
            'days_before_due' => [7, 3, 1],
            'days_after_due' => [1, 7, 15, 30],
            'channels' => ['mail'],
        ],
        'system_alerts' => [
            'enabled' => true,
            'channels' => ['database', 'mail'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Portal
    |--------------------------------------------------------------------------
    */
    'client_portal' => [
        'enabled' => true,
        'allow_registration' => false,
        'features' => [
            'view_lawsuits' => true,
            'view_documents' => true,
            'view_invoices' => true,
            'download_documents' => true,
            'message_lawyers' => true,
            'schedule_appointments' => false,
        ],
        'document_access_log' => true,
    ],
];