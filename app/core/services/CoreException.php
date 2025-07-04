<?php
namespace App\Core\Services;

// Exception type for internal framework and core errors.
// Use for framework-level issues that should be handled by the framework's error handler.
class CoreException extends \Exception {}
