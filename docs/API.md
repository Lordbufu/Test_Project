# Mini PHP Framework API Documentation

This guide covers the public API and usage patterns for building applications with this framework. For details on internal/core classes, see code comments.

---

## Table of Contents
- [Routing](#routing)
- [Controllers](#controllers)
- [Middleware & Access Control](#middleware--access-control)
- [Session Management](#session-management)
- [File Management](#file-management)
- [Service Container & App](#service-container--app)
- [Database & Query Builder](#database--query-builder)
- [Error Handling & Logging](#error-handling--logging)

---

## Routing

Define routes in `routes.php` (project root) using action script paths:

```php
$router->get('/', 'home.php');
$router->post('/login', 'authentication/login.php');
$router->get('/dashboard', 'dashboard.php')->only('users'); // 'users' is just an example group name
$router->get('/admin', 'admin.php')->only(['admins']);      // 'admins' is just an example group name
$router->setNotFound(function() { echo "404 Not Found"; });
```

- Use `->only('guests'|<your-group-names>)` to restrict access by group. Only `'guests'` is special-cased in the core; all other group names are user-defined and matched dynamically.
- Route parameters: `$router->get('/user/{id}', 'user/show.php');`
- Optional parameters: `$router->get('/post/{id?}', 'post/view.php');`

---

## Action Scripts (Controllers)

Each route points to a single action script in `app/controllers/` (no controller classes):

```php
// app/controllers/home.php
echo "Welcome!";
```

Action scripts have full access to all services via `App::get('service')` and should contain only the logic for that specific route.

---

## Middleware & Access Control

- Use `->only('guests'|<your-group-names>)` on routes for access control. Only `'guests'` is special-cased in the core; all other group names are user-defined and matched dynamically.
- You can add custom middleware per route:

```php
$router->get('/profile', 'UserController@profile', null, [function($uri, $method) {
    // Custom check
    return true;
}]);
```
- Global middleware: `$router->middleware($callback);`

---

## Session Management

Access the session manager via the static App singleton:

```php
App::get('session')->set('cart', ['item1' => 2]);
App::get('session')->get('cart');
App::get('session')->setNested('cart.items.product1.qty', 3);
App::get('session')->getNested('cart.items.product1.qty');
App::get('session')->flash('message', 'Saved!');
App::get('session')->getFlash('message');
```

---

## File Management

Access the file manager via the static App singleton:

```php
App::get('files')->read($path);
App::get('files')->write($path, $content);
App::get('files')->append($path, $content);
App::get('files')->delete($path);
App::get('files')->makeDir($dir);
App::get('files')->deleteDir($dir);
App::get('files')->copy($source, $dest);
App::get('files')->move($source, $dest);
App::get('files')->size($path);
App::get('files')->modified($path);
App::get('files')->serve($path, $asDownload = false);
```

---

## Service Container & App

- Access services via `App::get('serviceName')` (static singleton).
- Add or override services in `services.php` (project root). Place your custom service classes in `app/ext/` and reference them in `services.php`.

```php
// app/ext/ExampleService.php
namespace App\Ext;
class ExampleService {
    public function hello($name = 'World') {
        return "Hello, $name! This is your custom service.";
    }
}

// services.php
return [
    'example' => function($c) { return new \App\Ext\ExampleService(); },
];

// Usage in your app
echo App::get('example')->hello('User');
```

- The app and router are initialized in `public/index.php`.
- The container supports `has('name')` to check for a service, and `remove('name')` to unregister a service.
- Missing services throw a `CoreException`.
- All services are singletons by default; to create a new instance each time, register a factory that returns a new object.

---

## Database & Query Builder

Access the database and query builder via the static App singleton. For common operations, use the new convenience methods:

```php
// Find a single user by username
$user = App::get('db')->findOne('users', ['user_name' => $username]);

// Find all active users, ordered and limited
$users = App::get('db')->findAll('users', ['active' => 1], 'created_at DESC', 10);

// Count users with a certain status
$total = App::get('db')->count('users', ['active' => 1]);

// Insert a new user
$id = App::get('db')->insertOne('users', [
    'name' => 'Alice',
    'email' => 'alice@example.com',
]);

// Update a user
$count = App::get('db')->updateOne('users', ['id' => $id], [
    'name' => 'Alice Smith',
]);

// Delete a user
$count = App::get('db')->deleteOne('users', ['id' => $id]);
```

- All these helpers use parameter binding for security.
- For advanced queries, you can still use the full query builder:

```php
// Advanced WHERE and JOIN
$user = App::get('db')->query()->table('users')
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->where('users.email', '=', 'bob@example.com')
    ->orWhere('users.username', '=', 'bobby')
    ->first();
```

- See code comments in `QueryBuilder.php` for more advanced usage and chaining options.

---

## Error Handling & Logging

All errors and exceptions are handled centrally by the framework's ErrorHandler service. This ensures clean separation between API errors (user-facing, JSON) and Core errors (internal, logged).

### Core Errors
- Internal framework errors (routing, database, etc.) throw `CoreException`.
- All core errors are logged to `tmp/error.log`.
- Core errors return a generic error page to the user.

### API Errors
- For API endpoints, throw `ApiException` for user-facing errors (validation, authentication, etc.).
- API errors are returned as JSON with HTTP status code and message.

### Usage Example
```php
use App\Core\Services\ApiException;
use App\Core\Services\CoreException;

// Throw an API error (for API endpoints)
if (!$user) {
    throw new ApiException('User not found', 404);
}

// Throw a core error (for internal framework issues)
if (!$config) {
    throw new CoreException('Missing configuration');
}
```

### Logging
- All errors are logged to `tmp/error.log` with timestamps and type.
- You can access the error handler via `App::get('errors')`.

---

---


## Global Helpers (`helpers.php`)

You may define global helper functions in `helpers.php` (project root). These are loaded automatically for every request (see `public/index.php`).

**Purpose:**
- Provide optional, non-core utility functions (debugging, formatting, etc.)
- Keep the core framework clean and focused
- Allow both framework authors and end-users to add convenience functions

**Best Practices:**
- Use for debugging, formatting, or other convenience helpers
- Avoid business logic or framework-critical code
- Functions here are globally available

**Example:**

```php
// helpers.php
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
```

You may add your own helpers as needed. This file is optional and can be left empty if not used.

---


## Extensibility & Best Practices

- **Keep each action script focused on a single responsibility.**
- **Use the service container to manage dependencies and configuration.**
- **Never edit core files to add features.** Instead, extend via configuration, `services.php`, or by adding files to `app/ext/`.
- **Authentication and group/role logic:**
    - The core framework provides only minimal hooks for authentication and group/role assignment.
    - All real authentication logic (user lookup, password check, group assignment) must be implemented by the user via a custom service or function.
    - See `app/ext/CustomAuth.php` for a template. Register your function or service in `services.php` as `auth_custom`.
    - The core will call `auth_custom($username, $password)` if present, and expects a user array (with group field) on success, or null/false on failure.
    - The only hardcoded group is `'guests'` (for unauthenticated users). All other group/role logic is user-configurable.
- **Minimal core:**
    - No advanced or non-essential features are present in the core. All extension is via hooks, config, or user code.
    - No group/role logic or authentication logic is hardcoded except for the `'guests'` group.
- **How to extend authentication:**
    1. Copy `app/ext/CustomAuth.php` and implement your own user lookup logic (e.g., database query).
    2. Register your function in `services.php` as `'auth_custom'` (see comments in both files).
    3. Configure your user/group fields in `config.php` under the `auth` section.
    4. Do **not** edit core files for authentication or group logic.
- **For more advanced extension:**
    - You may register class-based services in `services.php` and update your custom logic accordingly.
    - See code comments in `Authentication.php` and `services.php` for details.

---

### Minimal Authentication Flow

1. User submits login form (POST to your login route).
2. Your action script calls `App::get('auth')->attempt($username, $password)`.
3. The core checks for a user-defined `auth_custom` function (see `app/ext/CustomAuth.php`).
4. If present, your function is called. If it returns a user array, the user is logged in and their group is determined by the configured field (see `config.php`).
5. If not present or returns false/null, login fails.
6. All group/role logic is user-configurable; only `'guests'` is hardcoded for unauthenticated users.

---

**See code comments in `Authentication.php`, `config.php`, and `services.php` for more details and best practices.**

---

## Route Parameters in Action Scripts

- All dynamic route parameters (e.g., `/user/{id}`) are available as an associative `$params` array in your action scripts (controllers).
- Example:

```php
// routes.php
$router->get('/user/{id}', 'user_show.php');

// app/controllers/user_show.php
if (isset($params['id'])) {
    echo "User ID: " . htmlspecialchars($params['id']);
}
```

- This works for multiple parameters as well:

```php
$router->get('/post/{postId}/comment/{commentId}', 'comment_show.php');
// In comment_show.php: $params['postId'], $params['commentId']
```
