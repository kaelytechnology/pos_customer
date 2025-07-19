<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de la API REST del paquete.
    |
    */
    'api_prefix' => env('POS_CUSTOMER_API_PREFIX', 'api/v1/pos/customers'),

    /*
    |--------------------------------------------------------------------------
    | Customer Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración específica de clientes.
    |
    */
    'default_active_status' => env('POS_CUSTOMER_DEFAULT_ACTIVE_STATUS', true),
    'require_rfc' => env('POS_CUSTOMER_REQUIRE_RFC', false),
    'require_tax_id' => env('POS_CUSTOMER_REQUIRE_TAX_ID', false),
    'default_customer_group' => env('POS_CUSTOMER_DEFAULT_GROUP', 'general'),
    'default_credit_limit' => env('POS_CUSTOMER_DEFAULT_CREDIT_LIMIT', 0.00),
    'default_points_balance' => env('POS_CUSTOMER_DEFAULT_POINTS', 0),

    /*
    |--------------------------------------------------------------------------
    | Credit Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para gestión de crédito.
    |
    */
    'credit' => [
        'enabled' => env('POS_CUSTOMER_CREDIT_ENABLED', true),
        'max_credit_limit' => env('POS_CUSTOMER_MAX_CREDIT_LIMIT', 100000.00),
        'min_credit_limit' => env('POS_CUSTOMER_MIN_CREDIT_LIMIT', 0.00),
        'credit_precision' => env('POS_CUSTOMER_CREDIT_PRECISION', 2),
        'auto_approve_limit' => env('POS_CUSTOMER_AUTO_APPROVE_LIMIT', 1000.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Loyalty Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para sistema de fidelización. 
    |
    */
    'loyalty' => [
        'enabled' => env('POS_CUSTOMER_LOYALTY_ENABLED', true),
        'points_per_currency' => env('POS_CUSTOMER_POINTS_PER_CURRENCY', 1),
        'points_currency' => env('POS_CUSTOMER_POINTS_CURRENCY', 'MXN'),
        'min_purchase_for_points' => env('POS_CUSTOMER_MIN_PURCHASE_FOR_POINTS', 1.00),
        'points_expiration_days' => env('POS_CUSTOMER_POINTS_EXPIRATION_DAYS', 365),
        'max_points_per_transaction' => env('POS_CUSTOMER_MAX_POINTS_PER_TRANSACTION', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Address Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para direcciones de facturación y envío.
    |
    */
    'addresses' => [
        'max_addresses_per_customer' => env('POS_CUSTOMER_MAX_ADDRESSES', 5),
        'require_phone' => env('POS_CUSTOMER_ADDRESS_REQUIRE_PHONE', false),
        'require_postal_code' => env('POS_CUSTOMER_ADDRESS_REQUIRE_POSTAL_CODE', true),
        'default_country' => env('POS_CUSTOMER_DEFAULT_COUNTRY', 'MX'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de validación para los formularios.
    |
    */
    'validation' => [
        'customer' => [
            'rfc' => 'nullable|string|max:13|regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/',
            'tax_id' => 'nullable|string|max:50',
            'customer_group' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0|max:999999.99',
            'points_balance' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ],
        'customer_address' => [
            'customer_id' => 'required|exists:customers,id',
            'type' => 'required|in:billing,shipping',
            'street' => 'required|string|max:255',
            'street_number' => 'nullable|string|max:20',
            'interior' => 'nullable|string|max:20',
            'neighborhood' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'country' => 'required|string|max:3',
            'phone' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de paginación para las listas.
    |
    */
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
        'per_page_options' => [10, 15, 25, 50, 100],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de notificaciones automáticas.
    |
    */
    'notifications' => [
        'enabled' => env('POS_CUSTOMER_NOTIFICATIONS_ENABLED', true),
        'customer_created' => env('POS_CUSTOMER_NOTIFY_CUSTOMER_CREATED', true),
        'customer_updated' => env('POS_CUSTOMER_NOTIFY_CUSTOMER_UPDATED', true),
        'points_earned' => env('POS_CUSTOMER_NOTIFY_POINTS_EARNED', true),
        'credit_limit_exceeded' => env('POS_CUSTOMER_NOTIFY_CREDIT_LIMIT_EXCEEDED', true),
        'channels' => ['mail', 'database'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de auditoría y logging.
    |
    */
    'audit' => [
        'enabled' => env('POS_CUSTOMER_AUDIT_ENABLED', true),
        'log_activities' => env('POS_CUSTOMER_LOG_ACTIVITIES', true),
        'log_changes' => env('POS_CUSTOMER_LOG_CHANGES', true),
        'retention_days' => env('POS_CUSTOMER_AUDIT_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de caché para mejorar el rendimiento.
    |
    */
    'cache' => [
        'enabled' => env('POS_CUSTOMER_CACHE_ENABLED', true),
        'ttl' => env('POS_CUSTOMER_CACHE_TTL', 3600), // 1 hora
        'prefix' => env('POS_CUSTOMER_CACHE_PREFIX', 'pos_customer'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de búsqueda y filtros.
    |
    */
    'search' => [
        'min_length' => 2,
        'max_results' => 50,
        'fuzzy_search' => env('POS_CUSTOMER_FUZZY_SEARCH', true),
        'search_fields' => ['name', 'email', 'rfc', 'tax_id'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de integración con otros paquetes.
    |
    */
    'integrations' => [
        'auth_package' => [
            'enabled' => env('POS_CUSTOMER_INTEGRATE_AUTH_PACKAGE', true),
            'extend_person' => env('POS_CUSTOMER_EXTEND_PERSON', true),
        ],
        'pos_sale' => [
            'enabled' => env('POS_CUSTOMER_INTEGRATE_POS_SALE', true),
            'auto_update_points' => env('POS_CUSTOMER_AUTO_UPDATE_POINTS', true),
            'auto_update_purchase_history' => env('POS_CUSTOMER_AUTO_UPDATE_HISTORY', true),
        ],
    ],
]; 