<?php
namespace App\Ext;

/**
 * ExampleService
 *
 * This is a sample user-defined service class. Copy and modify as needed.
 */
class ExampleService
{
    public function hello(string $name = 'World'): string
    {
        return "Hello, $name! This is your custom service.";
    }
}
