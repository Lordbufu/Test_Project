<?php

namespace App\Core\Services;

// Provides basic file and directory operations for internal framework use.
// Not intended for direct use with untrusted user input. Always validate paths in production.
class FileManager {
    protected $config;

    /**
     * FileManager constructor. Loads config from environment if not provided.
     *
     * @param array|null $config Optional file manager config (otherwise loaded from environment config)
     */
    public function __construct(array $config = null) {
        if ($config === null) {
            $envConfig = \App\Core\App::loadConfig();
            $config = $envConfig['files'] ?? [];
        }
        $this->config = $config;
    }

    /**
     * Read the contents of a file.
     *
     * @param string $path File path
     * @return string|false File contents or false on failure
     */
    public function read($path) {
        return is_file($path) ? file_get_contents($path) : false;
    }

    /**
     * Check if a file or directory exists.
     *
     * @param string $path File or directory path
     * @return bool True if exists, false otherwise
     */
    public function exists($path) {
        return file_exists($path);
    }

    /**
     * Check if a path is a directory.
     *
     * @param string $path Directory path
     * @return bool True if directory, false otherwise
     */
    public function isDir($path) {
        return is_dir($path);
    }

    /**
     * List files and directories in a directory.
     *
     * @param string $dir Directory path
     * @return array|false List of files/directories or false on failure
     */
    public function list($dir) {
        return is_dir($dir) ? scandir($dir) : false;
    }

    /**
     * Write (overwrite) contents to a file.
     *
     * @param string $path File path
     * @param string $content Content to write
     * @return bool True on success, false on failure
     */
    public function write($path, $content) {
        return file_put_contents($path, $content) !== false;
    }

    /**
     * Append content to a file.
     *
     * @param string $path File path
     * @param string $content Content to append
     * @return bool True on success, false on failure
     */
    public function append($path, $content) {
        return file_put_contents($path, $content, FILE_APPEND) !== false;
    }

    /**
     * Delete a file.
     *
     * @param string $path File path
     * @return bool True on success, false on failure
     */
    public function delete($path) {
        return is_file($path) ? unlink($path) : false;
    }

    /**
     * Create a directory (recursively by default).
     *
     * @param string $path Directory path
     * @param bool $recursive Whether to create directories recursively
     * @return bool True on success, false on failure
     */
    public function makeDir($path, $recursive = true) {
        return is_dir($path) || mkdir($path, 0777, $recursive);
    }

    /**
     * Recursively delete a directory and its contents.
     *
     * @param string $dir Directory path
     * @return bool True on success, false on failure
     */
    public function deleteDir($dir) {
        if (!is_dir($dir)) { return false; }

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    /**
     * Copy a file.
     *
     * @param string $source Source file path
     * @param string $dest Destination file path
     * @return bool True on success, false on failure
     */
    public function copy($source, $dest) {
        return copy($source, $dest);
    }

    /**
     * Move or rename a file or directory.
     *
     * @param string $source Source path
     * @param string $dest Destination path
     * @return bool True on success, false on failure
     */
    public function move($source, $dest) {
        return rename($source, $dest);
    }

    /**
     * Get file size in bytes.
     *
     * @param string $path File path
     * @return int|false File size in bytes or false on failure
     */
    public function size($path) {
        return is_file($path) ? filesize($path) : false;
    }

    /**
     * Get last modified time (Unix timestamp).
     *
     * @param string $path File path
     * @return int|false Unix timestamp or false on failure
     */
    public function modified($path) {
        return is_file($path) ? filemtime($path) : false;
    }

    /**
     * Serve a file to the browser (optionally as a download).
     *
     * @param string $path File path
     * @param bool $asDownload If true, send as download; otherwise, inline
     * @return bool True if file was served, false if not found
     * @note This method sends headers and outputs file content, but does not exit; caller is responsible for further response handling.
     */
    public function serve($path, $asDownload = false) {
        if (!is_file($path)) {
            http_response_code(404);
            echo "File not found.";
            return false;
        }

        $filename = basename($path);
        $mime = mime_content_type($path);
        header('Content-Type: ' . $mime);

        if ($asDownload) {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }

        header('Content-Length: ' . filesize($path));
        readfile($path);
        return true;
    }
}