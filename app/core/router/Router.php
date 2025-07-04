<?php
declare(strict_types=1);

namespace App\Core\Router;

use App\Core\Services\CoreException;

/**
 * Class Router
 *
 * A simple and flexible PHP router supporting:
 * - Static and dynamic routes
 * - Optional route parameters
 * - Action script and closure endpoints
 * - HTTP method matching (GET, POST, PUT, DELETE, PATCH)
 * - Named routes for URL generation
 * - Global and per-route middleware
 * - Custom 404 handler
 *
 * @package App\Core\Router
 */
class Router {
    /**
     * List of all registered routes
     * @var Route[]
     */
    protected array $routes = [];
    /**
     * Custom 404 handler
     * @var callable|null
     */
    protected $notFound = null;
    /**
     * Route names for URL generation
     * @var array
     */
    protected array $routeNames = [];
    /**
     * Global middleware callbacks
     * @var callable[]
     */
    protected array $middleware = [];
    /**
     * Router configuration
     * @var array
     */
    protected array $config;
    protected array $middlewareRegistry;

    /**
     * Router constructor.
     *
     * @param array|null $config Optional router configuration
     */
    public function __construct(?array $config = null) {
        if($config === null) {
            $envConfig = \App\Core\App::loadConfig();
            $config = $envConfig['router'] ?? [];
        }

        $this->config = $config;

        // init costum Middleware registry
        $registryPath = $this->config['middleware_registry'] ?? __DIR__ . '/../../ext/MiddlewareRegistry.php';
        
        if (file_exists($registryPath)) {
            $this->middlewareRegistry = require $registryPath;
        } else {
            $this->middlewareRegistry = [];
        }
    }

    /**
     * Load routes from an external file (e.g., routes.php)
     * The file should use $router to register routes.
     *
     * @param string $filePath
     */
    public function loadRoutesFromFile($filePath) {
        if(file_exists($filePath)) {
            $router = $this;
            require $filePath;
        }
    }

    /**
     * Register a global middleware callback.
     * Middleware receives ($uri, $method) and can return false to stop dispatch.
     *
     * @param callable $callback
     * @return void
     */
    public function middleware(callable $callback): void {
        $this->middleware[] = $callback;
    }

    /**
     * Add a route to the router.
     *
     * @param string $method HTTP method (GET, POST, etc)
     * @param string $pattern Route pattern (e.g., '/user/{id?}')
     * @param callable|string $callback Closure or action script path
     * @param string|null $name Optional route name
     * @param array $middleware Optional array of middleware callbacks for this route
     * @return Route
     */
    public function add(string $method, string $pattern, $callback, ?string $name = null, array $middleware = []): Route {
        $route = new Route($method, $pattern, $callback, $name, $middleware);
        $this->routes[] = $route;

        if($name) {
            $this->routeNames[$name] = $pattern;
        }

        return $route;
    }

    /**
     * Register a GET route
     * @param string $pattern
     * @param callable|string $callback
     * @param string|null $name
     * @return Route
     */
    public function get(string $pattern, $callback, ?string $name = null, array $test = null): Route {
        //die(var_dump(print_r("Pattern: " . $pattern)));
        //die(var_dump(print_r("Callback: " . $callback)));
        //die(var_dump(print_r("Name: " . $name)));
        die(var_dump(print_r("Test: " . $test)));
        //die(var_dump(print_r($this->middlewareRegistry['oddMinuteBlock'])));
        return $this->add('GET', $pattern, $callback, $name);
    }

    /**
     * Register a POST route
     * @param string $pattern
     * @param callable|string $callback
     * @param string|null $name
     * @return Route
     */
    public function post(string $pattern, $callback, ?string $name = null): Route {
        return $this->add('POST', $pattern, $callback, $name);
    }

    /**
     * Register a PUT route
     * @param string $pattern
     * @param callable|string $callback
     * @param string|null $name
     * @return Route
     */
    public function put(string $pattern, $callback, ?string $name = null): Route {
        return $this->add('PUT', $pattern, $callback, $name);
    }

    /**
     * Register a DELETE route
     * @param string $pattern
     * @param callable|string $callback
     * @param string|null $name
     * @return Route
     */
    public function delete(string $pattern, $callback, ?string $name = null): Route {
        return $this->add('DELETE', $pattern, $callback, $name);
    }

    /**
     * Register a PATCH route (as 'update')
     * @param string $pattern
     * @param callable|string $callback
     * @param string|null $name
     * @return Route
     */
    public function update(string $pattern, $callback, ?string $name = null): Route {
        return $this->add('PATCH', $pattern, $callback, $name);
    }

    /**
     * Set a custom 404 Not Found handler
     *
     * @param callable $callback
     * @return void
     */
    public function setNotFound(callable $callback): void {
        $this->notFound = $callback;
    }

    /**
     * Resolve a route callback, supporting action script files.
     *
     * @param callable|string $callback
     * @return callable
     * @throws CoreException if action script not found
     */
    protected function resolveCallback($callback, $pattern = null): callable {
        // Support action script files: e.g. 'authentication/showLogin.php'
        if(is_string($callback) && substr($callback, -4) === '.php') {
            $file = __DIR__ . '/../../controllers/' . $callback;

            if(file_exists($file)) {
                // Return a closure that accepts route parameters as arguments
                // and injects them as an associative $params array
                return function(...$params) use ($file, $pattern) {

                    // Extract parameter names from the route pattern
                    $pattern = $pattern ?? '';
                    preg_match_all('#\{([a-zA-Z0-9_]+)(\?)?\}#', $pattern, $paramNames);
                    $keys = $paramNames[1];
                    $paramsAssoc = [];

                    foreach ($keys as $i => $key) {
                        if (isset($params[$i])) {
                            $paramsAssoc[$key] = $params[$i];
                        }
                    }

                    $params = $paramsAssoc;
                    
                    return require $file;
                };
            } else {
                throw new CoreException("Action script not found: $file");
            }
        }

        // Legacy: support Controller@method if needed (optional, can remove if not used)
        if(is_string($callback) && strpos($callback, '@') !== false) {
            list($controller, $method) = explode('@', $callback);
            $controllerClass = "\\App\\Controllers\\" . $controller;

            if (class_exists($controllerClass)) {
                $instance = new $controllerClass();

                if (method_exists($instance, $method)) {
                    return [$instance, $method];
                }
            }

            throw new CoreException("Controller or method not found: $controllerClass@$method");
        }

        if (!is_callable($callback)) {
            throw new CoreException("Invalid route callback provided.");
        }

        return $callback;
    }

    /**
     * Dispatch the current request to the appropriate route.
     * Runs global and route-specific middleware.
     * Supports optional parameters (e.g., /user/{id?}).
     *
     * @param string|null $requestUri
     * @param string|null $requestMethod
     * @return mixed|null
     */
    public function dispatch(?string $requestUri = null, ?string $requestMethod = null) {
        $uri = $requestUri ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = strtoupper($requestMethod ?? $_SERVER['REQUEST_METHOD']);

        error_log('[ROUTER DEBUG] Registered routes: ' . count($this->routes));
        foreach($this->routes as $route) {
            error_log('[ROUTER DEBUG] Route: ' . $route->getPattern() . ' | Method: ' . $route->getMethod() . ' | Middleware count: ' . count($route->getMiddleware()));
            if ($route->getMethod() !== $method) {
                continue;
            }

            // Convert route pattern to regex, supporting optional params: /user/{id?}
            $pattern = preg_replace_callback(
                '#\{([a-zA-Z0-9_]+)(\?)?\}#',
                function ($matches) {
                    if(isset($matches[2]) && $matches[2] === '?') {
                        return '(?:([a-zA-Z0-9_\-]+))?';
                    }
                    return '([a-zA-Z0-9_\-]+)';
                },
                $route->getPattern()
            );
            $pattern = "#^" . $pattern . "$#";

            if(preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match

                // Run global middleware
                foreach($this->middleware as $mw) {
                    $result = call_user_func($mw, $uri, $method);
                    if($result === false) {
                        return null;
                    }
                }

                // Attach group middleware if needed
                if(!empty($route->getGroups())) {
                    $groupMiddleware = \App\Core\Middleware\Middleware::group($route->getGroups());
                    $result = call_user_func($groupMiddleware, $uri, $method);

                    if($result === false) {
                        return null;
                    }
                }

                // Run route-specific middleware
                $routeMiddleware = $route->getMiddleware();

                error_log('[ROUTER DEBUG] Per-route middleware count: ' . count($routeMiddleware));

                foreach($routeMiddleware as $i => $mw) {
                    error_log('[ROUTER DEBUG] Executing middleware #' . $i);
                    $result = call_user_func($mw, $uri, $method);
                    if ($result === false) {
                        error_log('[ROUTER DEBUG] Middleware #' . $i . ' returned false.');
                        return null;
                    }
                }

                $callback = $this->resolveCallback($route->getCallback(), $route->getPattern());

                return call_user_func_array($callback, $matches);
            }
        }

        // No route matched: call custom 404 or default
        if ($this->notFound) {
            call_user_func($this->notFound);
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }

        return null;
    }

    /**
     * Generate a URL by route name and parameters.
     *
     * @param string $name Route name
     * @param array $params Key-value pairs for route parameters
     * @return string|null
     */
    public function url(string $name, array $params = []): ?string {
        if(!isset($this->routeNames[$name])) {
            return null;
        }

        $pattern = $this->routeNames[$name];

        foreach ($params as $key => $value) {
            $pattern = preg_replace('/\{' . $key . '\??}/', $value, $pattern);
        }

        // Remove optional params not provided
        $pattern = preg_replace('/\{[a-zA-Z0-9_]+\?}/', '', $pattern);

        return $pattern;
    }
}