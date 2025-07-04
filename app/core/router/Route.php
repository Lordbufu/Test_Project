<?php
declare(strict_types=1);

namespace App\Core\Router;

/**
 * Class Route
 *
 * Represents a single HTTP route, including its method, pattern, callback, name, middleware, and group restrictions.
 * Provides a fluent interface for group-based access control.
 *
 * @package App\Core\Router
 */
class Route
{
    /**
     * HTTP method (GET, POST, etc.)
     * @var string
     */
    protected string $method;

    /**
     * Route pattern (e.g., '/user/{id}')
     * @var string
     */
    protected string $pattern;

    /**
     * Route callback (action script path or callable)
     * @var callable|string
     */
    protected $callback;

    /**
     * Optional route name
     * @var string|null
     */
    protected ?string $name;

    /**
     * Middleware callbacks for this route
     * @var array
     */
    protected array $middleware = [];

    /**
     * User groups allowed to access this route
     * @var array
     */
    protected array $groups = [];


    /**
     * Route constructor.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $pattern Route pattern (e.g., '/user/{id}')
     * @param callable|string $callback Action script path or callable
     * @param string|null $name Optional route name
     * @param array $middleware Optional array of middleware callbacks
     */
    public function __construct(string $method, string $pattern, $callback, ?string $name = null, array $middleware = [])
    {
        $this->method = strtoupper($method);
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->name = $name;
        $this->middleware = $middleware;
    }

    /**
     * Restrict this route to certain user groups (e.g., 'guests', 'users', 'admins').
     * Can be chained. Used by the router to attach group-based middleware.
     *
     * @param string|array $group One or more group names (string or array)
     * @return $this
     */
    public function only($group): self
    {
        if (!is_array($group)) {
            $group = [$group];
        }
        // Merge with existing groups for chaining
        $this->groups = array_unique(array_merge($this->groups, array_map('strtolower', $group)));
        return $this;
    }

    /**
     * Get the HTTP method for this route.
     * @return string
     */
    public function getMethod(): string { return $this->method; }

    /**
     * Get the route pattern.
     * @return string
     */
    public function getPattern(): string { return $this->pattern; }

    /**
     * Get the route callback (action script path or callable).
     * @return callable|string
     */
    public function getCallback() { return $this->callback; }

    /**
     * Get the route name, if set.
     * @return string|null
     */
    public function getName(): ?string { return $this->name; }

    /**
     * Get the middleware callbacks for this route.
     * @return array
     */
    public function getMiddleware(): array { return $this->middleware; }

    /**
     * Get the allowed user groups for this route.
     * @return array
     */
    public function getGroups(): array { return $this->groups; }
}
