<?php

namespace App\Core\Services;

/**
 * Class Authentication
 *
 * Handles user authentication logic for the framework.
 *
 * Features:
 * - Configurable via environment config (supports custom group resolver)
 * - Session-based authentication (user data stored in $_SESSION['user'])
 * - Methods for login, logout, user/group retrieval, and authentication check
 * - Example login logic (replace with real user lookup in production)
 */
class Authentication {
    protected $config;

    /**
     * Authentication constructor. Loads config from environment if not provided.
     *
     * @param array|null $config Optional authentication config (otherwise loaded from environment config)
     */
    public function __construct(array $config = null) {
        if ($config === null) {
            $envConfig = \App\Core\App::loadConfig();
            $config = $envConfig['auth'] ?? [];
        }
        $this->config = $config;
    }

    /**
     * Get the group/role for a user.
     *
     * @param array|null $user User data array or null
     * @return string User group/role (string)
     */
    public function getUserGroup($user = null) {
        $config = \App\Core\App::loadConfig();
        // If no user is provided (not authenticated), return 'guests'
        if ($user === null) {
            return 'guests';
        }
        // Use configured group/role field, fallback to 'user_group', then 'users'
        $field = $config['user_group_field'] ?? 'user_group';
        return $user[$field] ?? 'users';
    }

    /**
     * Check if the user is authenticated (by session).
     *
     * @return bool True if user is authenticated, false otherwise
     */
    public function check(): bool {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    /**
     * Get the current authenticated user (if any).
     *
     * @return array|null User data array or null if not authenticated
     */
    public function user() {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Attempt to log in a user. Delegates to user-defined service if present.
     *
     * @param string $username Username
     * @param string $password Password
     * @return bool True if login successful, false otherwise
     */
    public function attempt($username, $password): bool {
        // Minimal core: delegate to user-defined service if present
        // The user should register a custom authentication service (e.g., 'auth_custom') in services.php
        if (function_exists('auth_custom')) {
            $user = auth_custom($username, $password);
            if (is_array($user) && !empty($user)) {
                $_SESSION['user'] = $user;
                return true;
            }
            return false;
        }
        // No authentication logic in core; always fail by default
        return false;
    }

    /**
     * Log out the current user (removes user data from session).
     *
     * @return void
     */
    public function logout() {
        unset($_SESSION['user']);
    }
}
