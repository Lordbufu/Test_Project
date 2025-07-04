<?php
/**
 * Application configuration.
 *
 * Add or modify environments as needed: 'development', 'production', etc.
 * Place custom service configs under their respective keys.
 *
 * Do not commit real credentials to version control!
 */
return [
    // Set the active environment here:
    'environment' => 'development',

    'development' => [
        'database' => [
            'host' => 'localhost',
            'port' => 3306,
            'dbname' => 'frametest',
            'charset' => 'utf8mb4',
            'driver' => 'mysql',
            'collation' => 'utf8mb4_general_ci'
        ],
        'credentials' => [
            'user' => 'biblioapp',
            'pass' => '*0VR6PjrknrIY[_x'
        ],
        'version' => 'v0.0.1',
        // --- Framework service config examples ---
        'auth' => [
            'password_algo' => PASSWORD_DEFAULT,
            'password_cost' => 12,
            'user_table' => 'users', // Name of your user table
            'user_group_field' => 'user_group', // Field in user/session data for group/role
        ],
        'session' => [
            'lifetime' => 3600,
            'cookie_name' => 'myapp_session',
            'save_path' => __DIR__ . '/tmp/session',
            'secure' => false,
            'httponly' => true,
        ],
        'files' => [
            'max_size' => 5 * 1024 * 1024,
            'allowed_types' => ['jpg', 'png', 'pdf'],
            'upload_dir' => __DIR__ . '/uploads',
        ],
        'router' => [
            'middleware_registry' => __DIR__ . '/app/ext/MiddlewareRegistry.php',
        ],
        // Add other service configs as needed...
    ],

    // Example for production (copy and adjust as needed)
    // 'production' => [
    //     ...same structure as above...
    // ],
];