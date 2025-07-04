# Framework Test Plan & Release Checklist

This checklist covers all core features, extensibility points, and recommended extension scenarios. Use it to verify the framework before release.

---

## 1. Core Routing & Action Scripts
- [x] 1.1: Access the root route `/` as a guest (welcome view renders)
    - _Tested: The default route points to the welcome controller and view. Confirmed working. Any changes for testing were reverted._
- [x] 1.2: Add a new route in `routes.php` and verify it works
    - _Tested: Temporary route (e.g., `/test`) and controller added and removed after test. Confirmed working._
- [x] 1.3: Add a route with a dynamic parameter and verify parsing
    - _Tested: Route like `/user/{id}` and controller that outputs the ID. Confirmed parameter parsing works. Temporary files removed._

## 2. Middleware & Access Control
- [x] 2.1: Access a route restricted to `'guests'` as an unauthenticated user
    - _Tested: Guest access to '/' works as expected._
- [x] 2.2: Access the same route after logging in (should be denied)
    - _Tested: After login, access to '/' is denied for authenticated users._
- [x] 2.3: Add a route restricted to a custom group (e.g., `'admins'`)
    - _Tested: Route restricted to 'admins' group works with DB user and config. Confirmed working. Test code cleaned up._
- [ ] 2.4: Add custom middleware to a route and verify execution

## 3. Authentication Flow
- [ ] 3.1: Attempt login with invalid credentials (should fail)
- [ ] 3.2: Implement and test a custom `auth_custom` function
- [ ] 3.3: Log out and verify session is cleared

## 4. Session Management
- [ ] 4.1: Set, get, and remove session variables
- [ ] 4.2: Use flash messages and verify one-request persistence

## 5. File Management
- [ ] 5.1: Use file manager to read, write, and delete a file
- [ ] 5.2: Attempt to access a file outside allowed directory (should fail)

## 6. Service Container & Extensibility
- [ ] 6.1: Register and use a new service in `services.php`
- [ ] 6.2: Override a core service and verify override
- [ ] 6.3: Add a new helper in `helpers.php` and use it

## 7. Error Handling
- [ ] 7.1: Trigger a core error and verify error page/log
- [ ] 7.2: Throw an `ApiException` and verify JSON error response

## 8. Configuration
- [ ] 8.1: Change config values and verify effect
- [ ] 8.2: Add a new environment and verify correct config is used

## 9. Security & Entry Point
- [ ] 9.1: Access with invalid/missing user-agent (should 404)
- [ ] 9.2: Attempt to access restricted routes without authentication (should deny)

## 10. Documentation & Developer Experience
- [ ] 10.1: Follow docs to add a route, service, and auth extension (should work)

---

### Extension Scenarios
- [ ] Ext 1: Implement and test a class-based authentication service
- [ ] Ext 2: Add a new user group and restrict a route to it
- [ ] Ext 3: Add a new file type to allowed uploads and verify

---

Check off each item as you verify it. Remove or add items as needed for your project.
