
<?php
// Set PHP error log to custom location (same as session/logs)
ini_set('error_log', __DIR__ . '/../tmp/error.log');

// User-Agent validation: block empty or obviously invalid user-agents
if (empty($_SERVER['HTTP_USER_AGENT']) || strlen($_SERVER['HTTP_USER_AGENT']) < 10) {
    http_response_code(404);
    echo 'Error 404: Valid user-data not found, come back again once you fix that.';
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
// Include global helpers (user and framework helpers)
require_once __DIR__ . '/../helpers.php';

use App\Core\App;

if(App::getInstance()->loaded()) {
    App::get('router')->dispatch();
}