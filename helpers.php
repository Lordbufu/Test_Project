<?php
/**
 * helpers.php
 *
 * Place your global helper functions here. These are optional, non-core utilities that can be used throughout your application.
 *
 * Best practices:
 * - Use for debugging, formatting, or other convenience functions.
 * - Avoid putting business logic or framework-critical code here.
 * - Functions here are globally available if this file is included early (see index.php).
 *
 * Example: dieAndDump() - dump variable(s) and halt execution.
 */

if (!function_exists('dieAndDump')) {
    /**
     * Dump variable(s) in a readable format and halt execution.
     * Usage: dieAndDump($var1, $var2, ...)
     * @param mixed ...$vars
     * @return void
     */
    function dieAndDump(...$vars): void {
        echo '<pre style="background:#222;color:#eee;padding:1em;">';
        
        foreach ($vars as $var) {
            print_r($var);
            echo "\n";
        }

        echo '</pre>';

        die(1);
    }
}