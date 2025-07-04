<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Services\Authentication;
use App\Core\App;
use App\Core\Services\CoreException;

/**
 * Class Middleware
 *
 * Provides reusable middleware callbacks for route protection and other checks.
 *
 * For group-based access control, use Middleware::group(['guests', 'users', 'admins'])
 * in conjunction with the router's only() syntax.
 *
 * Legacy methods (auth, admin) have been removed in favor of group-based access control.
 *
 * @package App\Core\Middleware
 */
class Middleware {
    /**
     * Middleware for group-based access control (guests, users, admins).
     *
     * Usage: $router->get(...)->only('users');
     * The router will automatically attach this middleware for group restrictions.
     *
     * @param array|string $groups One or more group names (string or array)
     * @return callable Middleware callback: function(string $uri, string $method): bool
     */
    public static function group(array|string $groups): callable {
        // Normalize to array of lowercase strings
        $groups = is_array($groups) ? $groups : [$groups];
        $groups = array_map('strtolower', $groups);

        return function (string $uri, string $method) use ($groups): bool {
            // Only 'guests' is special-cased; all other groups are dynamic
            foreach ($groups as $group) {
                if ($group === 'guests' && !App::get('auth')->check()) {
                    return true;
                }

                $user = App::get('auth')->user();
                $userGroup = App::get('auth')->getUserGroup($user);

                if ($user && $userGroup && $group === strtolower($userGroup)) {
                    return true;
                }
            }

            http_response_code(403);
            echo 'Forbidden: Access denied for this group.';
            
            return false;
        };
    }
}
