<?php
/**
 * Simple .env loader for this project.
 *
 * Reads the project's `.env` (if present) or falls back to `.env.example` for defaults.
 * It sets variables using putenv and populates $_ENV/$_SERVER so getenv() works.
 *
 * Note: Do NOT commit your real `.env` file into VCS. This loader will not overwrite
 * existing environment variables (so server-level env vars take precedence).
 */

$envPath = __DIR__ . '/../.env';
$fallback = __DIR__ . '/../.env.example';

if (!file_exists($envPath) && file_exists($fallback)) {
    $envPath = $fallback;
}

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Strip surrounding quotes
        if ((substr($value,0,1) === '"' && substr($value,-1) === '"') || (substr($value,0,1) === "'" && substr($value,-1) === "'")) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

?>
