<?php

namespace App\Core\Services;

// Minimal dependency injection container for managing shared services and factories.
// Supports registration and lazy instantiation of services via callables, class names, or objects.
// Throws a CoreException for missing services. All services are singletons by default.
class Container {
    /**
     * Registered service resolvers (factories, class names, or objects)
     * @var array
     */
    protected $services = [];

    /**
     * Instantiated service singletons
     * @var array
     */
    protected $instances = [];

    /**
     * Register services using a map: ['name' => factory/callable/className/object]
     *
     * @param array $map
     * @return $this
     */
    public function register(array $map): self {
        foreach ($map as $name => $resolver) {
            $this->services[$name] = $resolver;
        }
        return $this;
    }

    /**
     * Get a service by name, instantiating it if needed.
     *
     * @param string $name
     * @return mixed The resolved service instance
     * @throws CoreException If the service is not found
     */
    public function get(string $name) {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (!isset($this->services[$name])) {
            throw new CoreException("Service '{$name}' not found in container.");
        }

        $resolver = $this->services[$name];

        if (is_callable($resolver)) {
            $object = $resolver($this);
        } elseif (is_string($resolver) && class_exists($resolver)) {
            $object = new $resolver();
        } else {
            $object = $resolver;
        }

        $this->instances[$name] = $object;
        return $object;
    }

    /**
     * Check if a service is registered.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool {
        return isset($this->services[$name]);
    }

    /**
     * Remove a registered service and its instance (if any).
     *
     * @param string $name
     * @return void
     */
    public function remove(string $name): void {
        unset($this->services[$name], $this->instances[$name]);
    }
}
