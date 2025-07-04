<?php

// User Service Registration
//
// Register or override services for the DI container here.
// Place your custom service classes in app/ext/ (e.g., app/ext/ExampleService.php)
// and reference them below. Example:
//
// 'example' => function($c) { return new \App\Ext\ExampleService(); },
//
// You can add as many services as you like. Each entry can be a class name, a closure (factory), or an object instance.
// The closure receives the container as its first argument.

return [
    // Example: Add a user extension service (see app/ext/ExampleService.php)
    'example' => function($c) {
        return new \App\Ext\ExampleService();
    },

    // Register a user-defined authentication function (see app/ext/CustomAuth.php)
    // This is a minimal example; you may use a closure or class as needed.
    // 'auth_custom' is expected to be a global function (see CustomAuth.php)
    // If you want to use a class-based service, update Authentication.php accordingly.
    //
    // 'auth_custom' => function($c) {
    //     return new \App\Ext\CustomAuth();
    // },
];