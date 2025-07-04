<?php
// Default landing route for guests only
// This route points to app/controllers/welcome.php, which renders the welcome view.
$router->get('/', 'welcome.php');

// 2.4 Test: Route with custom middleware (using add for per-route middleware)
$router->get('/middleware-test', 'middleware_test.php', null, ['oddMinuteBlock']);