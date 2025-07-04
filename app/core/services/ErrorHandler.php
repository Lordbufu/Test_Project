<?php

namespace App\Core\Services;

// Centralized error/exception handler and logger for the framework.
// Handles PHP errors and uncaught exceptions, logs errors, and formats API/Core error responses.
// Configurable via environment config (log path, etc.).
// API/Core response methods do not call exit; caller is responsible for further response handling.
class ErrorHandler {
    protected $logFile;
    protected $lastError;
    protected $config;

    public function __construct(array $config = null) {
        if ($config === null) {
            $envConfig = \App\Core\App::loadConfig();
            $config = $envConfig['errors'] ?? [];
        }
        $this->config = $config;
        $this->logFile = $config['log_path'] ?? dirname(__DIR__, 3) . '/tmp/error.log';
    }

    /**
     * Handle PHP errors (for set_error_handler).
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool Always true to prevent default PHP error output
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        $msg = "[PHP ERROR] [$errno] $errstr in $errfile on line $errline";
        $this->log($msg, 'core');
        $this->lastError = $msg;
        return true; // Prevent default PHP error output
    }

    /**
     * Handle uncaught exceptions (for set_exception_handler).
     *
     * @param \Throwable $exception
     * @return void
     */
    public function handleException($exception) {
        $type = ($exception instanceof \App\Core\Services\ApiException) ? 'api' : 'core';
        $msg = '[' . strtoupper($type) . ' EXCEPTION] ' . $exception->getMessage() . "\n" . $exception->getTraceAsString();
        $this->log($msg, $type);
        $this->lastError = $msg;
        if ($type === 'api') {
            $this->apiResponse($exception);
        } else {
            $this->coreResponse($exception);
        }
    }

    /**
     * Log an error message to file.
     *
     * @param string $message
     * @param string $type
     * @return void
     */
    public function log($message, $type = 'core') {
        $line = date('Y-m-d H:i:s') . " [$type] $message\n";
        file_put_contents($this->logFile, $line, FILE_APPEND);
    }

    /**
     * Output a JSON error for API exceptions.
     *
     * @param \Throwable $exception
     * @return void
     * @note This method sends headers and outputs JSON, but does not exit; caller is responsible for further response handling.
     */
    public function apiResponse($exception) {
        http_response_code($exception->getCode() ?: 500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ]);
        // No exit; caller must decide how to terminate response
    }

    /**
     * Output a simple error for Core exceptions.
     *
     * @param \Throwable $exception
     * @return void
     * @note This method sends headers and outputs HTML, but does not exit; caller is responsible for further response handling.
     */
    public function coreResponse($exception) {
        http_response_code(500);
        $display = $this->config['display'] ?? false;
        echo "<h1>Internal Server Error</h1>";
        if ($display) {
            echo '<pre style="color:#a00;background:#fff;padding:1em;border:1px solid #a00;">';
            echo htmlspecialchars($exception->getMessage()) . "\n";
            echo htmlspecialchars($exception->getTraceAsString());
            echo '</pre>';
        }
        // No exit; caller must decide how to terminate response
    }

    /**
     * Get the last error or exception message handled.
     *
     * @return string|null
     */
    public function getLastError() {
        return $this->lastError;
    }
}

/**
 * Class ApiException
 * For API-specific errors (use throw new ApiException(...))
 */
class ApiException extends \Exception {}
