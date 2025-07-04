<?php

namespace App\Core\Services;

// Handles secure session initialization, storage, and management for PHP applications.
// Provides methods for setting, getting, removing, and flashing session variables.
// Supports secure session cookie parameters and session ID regeneration.
class SessionManager {
    /**
     * @var string Path to session storage directory
     */
    protected $savePath;
    /**
     * @var array Session configuration
     */
    protected $config;

    /**
     * SessionManager constructor. Initializes session with secure settings and custom storage path.
     *
     * @param array|null $config Optional session config (otherwise loaded from environment config)
     *   Supported keys: 'save_path', 'lifetime', 'cookie_name', 'secure', 'httponly', etc.
     */
    public function __construct(array $config = null) {
        if ($config === null) {
            $envConfig = \App\Core\App::loadConfig();
            $config = $envConfig['session'] ?? [];
        }
        $this->config = $config;
        $this->setupSavePath();
        $this->setupSessionCookieParams();
        $this->setupSessionIni();
        $this->sendNoCacheHeaders();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Set up the session save path, allowing override via config.
     *
     * @return void
     * @internal
     */
    protected function setupSavePath() {
        $defaultPath = dirname(__DIR__, 3) . '/tmp/session/';
        $this->savePath = $this->config['save_path'] ?? $defaultPath;
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777, true);
        }
        session_save_path($this->savePath);
    }

    /**
     * Set session cookie parameters from config if provided.
     *
     * Supported config keys: 'cookie_lifetime', 'cookie_path', 'cookie_domain', 'cookie_secure', 'cookie_httponly', 'cookie_samesite'
     *
     * @return void
     * @internal
     */
    protected function setupSessionCookieParams() {
        $params = session_get_cookie_params();
        $lifetime = $this->config['cookie_lifetime'] ?? $params['lifetime'];
        $path = $this->config['cookie_path'] ?? $params['path'];
        $domain = $this->config['cookie_domain'] ?? $params['domain'];
        $secure = $this->config['cookie_secure'] ?? $params['secure'];
        $httponly = $this->config['cookie_httponly'] ?? $params['httponly'];
        $samesite = $this->config['cookie_samesite'] ?? null;

        // PHP 7.3+ supports samesite in options array
        if (PHP_VERSION_ID >= 70300 && $samesite) {
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite
            ]);
        } else {
            session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
        }
    }

    /**
     * Set secure session-related ini settings.
     *
     * @return void
     * @internal
     */
    protected function setupSessionIni() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 1 : 0);
    }

    /**
     * Send headers to prevent browser caching of session pages (for flashing).
     *
     * @return void
     * @internal
     */
    protected function sendNoCacheHeaders() {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    /**
     * Set a session variable (array or scalar).
     *
     * @param string $key Session key
     * @param mixed $value Value to store (can be array or scalar)
     * @return void
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session variable, or return default if not set.
     *
     * @param string $key Session key
     * @param mixed $default Default value if key not set
     * @return mixed
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Remove a session variable.
     *
     * @param string $key Session key
     * @return void
     */
    public function remove($key) {
        unset($_SESSION[$key]);
    }

    /**
     * Flash a session variable (available for one request only).
     *
     * @param string $key Flash key
     * @param mixed $value Value to flash
     * @return void
     */
    public function flash($key, $value) {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get and remove a flashed session variable.
     *
     * @param string $key Flash key
     * @param mixed $default Default value if not set
     * @return mixed
     */
    public function getFlash($key, $default = null) {
        if (isset($_SESSION['_flash'][$key])) {
            $value = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $value;
        }
        return $default;
    }

    /**
     * Clear all flashed session variables (should be called at end of request).
     *
     * @return void
     */
    public function clearFlash() {
        unset($_SESSION['_flash']);
    }

    /**
     * Destroy the entire session and remove the session cookie.
     *
     * @return void
     */
    public function destroy() {
        // Unset all session variables
        $_SESSION = [];

        // Delete the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        // Destroy the session
        session_destroy();
    }

    /**
     * Regenerate the session ID to prevent session fixation attacks.
     *
     * @param bool $deleteOldSession Whether to delete the old session data or not
     * @return void
     */
    public function regenerateId($deleteOldSession = true) {
        session_regenerate_id($deleteOldSession);
    }
}