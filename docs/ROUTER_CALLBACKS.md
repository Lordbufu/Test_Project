# Router Callback Resolution and Action Script Parameters

## Overview

The Router in this framework supports multiple types of route callbacks, including action script files and closures. For action script routes, the Router automatically injects route parameters as an associative `$params` array, making it easy to access named parameters in your action scripts.

## Action Script Callbacks

When you register a route with a callback that is a string ending in `.php`, the Router treats it as an action script. The script is loaded from the `app/controllers/` directory. Example:

```php
$router->get('/user/{id}', 'user_show.php');
```

### Parameter Injection

For dynamic routes (e.g., `/user/{id}`), the Router extracts parameter names from the route pattern and passes them to the action script as an associative array called `$params`. The keys of this array match the parameter names in the route pattern.

**Example:**

Route definition:

```php
$router->get('/user/{id}', 'user_show.php');
```

In `app/controllers/user_show.php`:

```php
// $params['id'] will contain the value from the URL
$userId = $params['id'] ?? null;
```

If the route has multiple parameters, all are included in `$params`:

```php
$router->get('/post/{postId}/comment/{commentId}', 'comment_show.php');
```

In `app/controllers/comment_show.php`:

```php
$postId = $params['postId'] ?? null;
$commentId = $params['commentId'] ?? null;
```

### Optional Parameters

Optional parameters (e.g., `/user/{id?}`) are included in `$params` only if present in the URL. If not provided, the key will be absent or `null`.

## Legacy Controller@method Callbacks

The Router also supports legacy `Controller@method` callbacks for backward compatibility. This is optional and can be removed if not needed.

## Error Handling

If an action script file is not found, the Router throws a `CoreException` with a clear error message.

## Best Practices

- Always use named parameters in your route patterns for clarity.
- Access all route parameters via the `$params` array in your action scripts.
- Keep action scripts minimal and focused on a single responsibility.
- Use closures for advanced logic or when you need more control over parameter handling.

## Example: Full Route and Action Script

**Route registration:**

```php
$router->get('/product/{sku}', 'product_show.php');
```

**Action script (`app/controllers/product_show.php`):**

```php
$sku = $params['sku'] ?? null;
if ($sku) {
    // Fetch and display product by SKU
} else {
    // Handle missing SKU
}
```

---

For more details, see the Router class implementation in `app/core/router/Router.php`.
