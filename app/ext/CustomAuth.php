<?php
/**
 * Example: User-defined authentication service for the mini-framework.
 *
 * Place this file in app/ext/CustomAuth.php and register the function in services.php.
 *
 * This is a minimal template. Replace the logic with your own user lookup (e.g., database).
 */

if (!function_exists('auth_custom')) {
    /**
     * Authenticate a user by username and password.
     * Return user array on success, or null/false on failure.
     *
     * @param string $username
     * @param string $password
     * @return array|null
     */
    function auth_custom($username, $password) {
        // Example: Replace with real user lookup (e.g., query your DB)
        if ($username === 'admin' && $password === 'password') {
            return [
                'id' => 1,
                'username' => 'admin',
                'user_group' => 'admins', // Use the group field configured in config.php
            ];
        }
        return null;
    }
}
