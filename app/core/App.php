<?php

namespace App\Core;

use App\Core\Services\Container;

/**
 * Class App
 *
 * The main application class. Initializes and manages the service container.
 *
 * - Loads core services from a default map.
 * - Merges in user-defined services from a project root 'services.php' file (if present).
 * - Loads routes from a project root 'routes.php' file (if present).
 * - Provides access to services via get($name).
 *
 * Usage:
 *   $app = new App();
 *   $router = $app->get('router');
 *
 * To extend services, create a 'services.php' file in your project root:
 *   <?php
 *      return [
 *          'mailer' => function($c) { return new \App\Core\Services\Mailer(); },
 *          // ...
 *      ];
 */
class App {
    /**
     * @var App|null Singleton instance
     */
    protected static $instance = null;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var bool Indicates if the app loaded successfully
     */
    protected $loaded = false;
    /**
     * @var string|null Last error message if app failed to load
     */
    protected $error = null;
    /**
     * Base directory for views (relative to project root)
     * @var string
     */
    public static $viewBase = __DIR__ . '/../views/';

    /**
     * App constructor. Sets up error handling, loads core/user services, and initializes routes.
     *
     * @param array $coreMap Optional core service map (for testing/extension)
     */
    public function __construct(array $coreMap = []) {
        if (self::$instance === null) {
            self::$instance = $this;
        }

        $this->setupErrorHandling();
        $this->initializeApp($coreMap);
    }

    /**
     * Main app initialization loop with retry logic. Handles service map, container, session, and routes.
     *
     * @param array $coreMap
     * @return void
     */
    private function initializeApp($coreMap) {
        $maxAttempts = 3;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                $map = $this->buildServiceMap($coreMap);
                $this->setupContainer($map);
                $this->get('session');
                $this->loadRoutesIfPresent();
                $this->loaded = true;
                $this->error = null;
                break;
            } catch (\Throwable $e) {
                $this->loaded = false;
                $this->error = $e->getMessage() . "\n" . $e->getTraceAsString();
                try {
                    $handler = new \App\Core\Services\ErrorHandler();
                    $handler->handleException(new \App\Core\Services\CoreException($e->getMessage(), $e->getCode(), $e));
                } catch (\Throwable $logErr) {}
                $attempt++;
            }
        }
    }

    /**
     * Build the merged service map from core and user services.
     *
     * @param array $coreMap
     * @return array
     */
    private function buildServiceMap($coreMap) {
        $defaultMap = [
            'auth' => \App\Core\Services\Authentication::class,
            'session' => \App\Core\Services\SessionManager::class,
            'router' => function($c) {
                return new \App\Core\Router\Router();
            },
            'files' => \App\Core\Services\FileManager::class,
            'db' => function($c) {
                $config = self::loadConfig();
                return new \App\Core\Database\Database($config['database'], $config['credentials']);
            },
            'errors' => function($c) {
                return new \App\Core\Services\ErrorHandler();
            }
        ];

        $coreMap = array_merge($defaultMap, $coreMap);
        $fileManager = new \App\Core\Services\FileManager();
        $userMap = [];
        $userFile = dirname(__DIR__, 2) . '/services.php';

        if ($fileManager->exists($userFile)) {
            $userMap = require $userFile;
        }

        return array_merge($coreMap, $userMap);
    }

    /**
     * Register all services with the container.
     *
     * @param array $map
     * @return void
     */
    private function setupContainer($map) {
        $this->container = new Container();
        $this->container->register($map);
    }

    /**
     * Load routes from file if present. No-op if file does not exist.
     *
     * @return void
     */
    private function loadRoutesIfPresent() {
        $fileManager = new \App\Core\Services\FileManager();
        $routesFile = dirname(__DIR__, 2) . '/routes.php';

        if ($fileManager->exists($routesFile)) {
            $router = $this->get('router');
            $router->loadRoutesFromFile($routesFile);
        }
    }

    /**
     * Set up global error and exception handling for the application.
     * Throws a RuntimeException if error handler setup fails.
     *
     * @throws \RuntimeException
     * @return void
     */
    private function setupErrorHandling() {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ALL);

        try {
            $errors = new \App\Core\Services\ErrorHandler();
            set_error_handler([$errors, 'handleError']);
            set_exception_handler([$errors, 'handleException']);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Critical error: unable to initialize error handler.", 500, $e);
        }
    }

    /**
     * Get the singleton instance of the App.
     *
     * @return self
     */
    public static function getInstance(): self{
        if (self::$instance === null) {
            new self();
        }
        return self::$instance;
    }

    /**
     * Get a service from the container by name (static singleton access).
     *
     * @param string $service Service name as registered in the container
     * @return mixed The resolved service instance
     */
    public static function get($service) {
        if (self::$instance === null) {
            new self(); // auto-initialize singleton if not already
        }

        return self::$instance->container->get($service);
    }

    /**
     * Check if the app loaded successfully.
     *
     * @return bool True if loaded, false otherwise
     */
    public function loaded() {
        return $this->loaded;
    }

    /**
     * Get the last error message if app failed to load.
     *
     * @return string|null Error message or null if none
     */
    public function error() {
        return $this->error;
    }

    /**
     * Render a view file with optional data extraction.
     *
     * Usage: return App::view('login.php', ['foo' => 'bar']);
     *
     * @param string $path Relative path to the view file
     * @param array $data Data to extract into the view
     * @return void
     */
    public static function view($path, $data = []) {
        extract($data, EXTR_SKIP);

        include static::$viewBase . $path;
    }

    /**
     * Redirect to a URL and exit.
     *
     * Usage: return App::redirect('/home');
     *
     * @param string $url URL to redirect to
     * @return void
     */
    public static function redirect($url) {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Load the config for the current environment (from config.php 'environment' key).
     *
     * Caches the result for future calls. Throws if config or environment is missing/invalid.
     *
     * Usage: $config = App::loadConfig();
     *
     * @param string|null $configFile Optional path to config file
     * @return array The config array for the current environment
     * @throws \RuntimeException if config or environment is missing/invalid
     */
    public static function loadConfig($configFile = null) {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $baseDir = dirname(__DIR__, 2);
        $configFile = $configFile ?? $baseDir . '/config.php';

        if (!file_exists($configFile)) {
            throw new \RuntimeException('Config file not found: ' . $configFile);
        }

        $allConfig = require $configFile;

        if (!is_array($allConfig)) {
            throw new \RuntimeException("Config file did not return an array: $configFile");
        }

        $env = $allConfig['environment'] ?? 'development';

        if (!isset($allConfig[$env])) {
            throw new \RuntimeException("Config for environment '$env' not found in $configFile");
        }

        $cached = (array)$allConfig[$env];
        $cached['environment'] = $env;

        return $cached;
    }
}