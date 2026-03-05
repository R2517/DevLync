<?php
declare(strict_types=1);

/**
 * SupervisorErrorHandler
 * Captures PHP errors, exceptions, and fatal shutdowns,
 * logging them to the supervisor_errors table automatically.
 */
class SupervisorErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $severity = 'info';
        switch ($errno) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $severity = 'critical';
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $severity = 'warning';
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
            case E_DEPRECATED:
                $severity = 'info';
                break;
        }

        try {
            $supervisor = new Supervisor();
            $supervisor->logError('php', $severity, $errstr, [
                'file' => $errfile,
                'line' => $errline,
                'url' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
        } catch (\Exception $e) {
            // Silently fail — don't create error loops
            error_log("Supervisor error handler failed: " . $e->getMessage());
        }

        return false; // Let PHP handle it too
    }

    public static function handleException(\Throwable $e): void
    {
        try {
            $supervisor = new Supervisor();
            $supervisor->logError('php', 'critical', $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'trace' => $e->getTraceAsString(),
            ]);
        } catch (\Exception $ex) {
            error_log("Supervisor exception handler failed: " . $ex->getMessage());
        }

        // Show generic error page (don't expose details)
        if (php_sapi_name() !== 'cli') {
            http_response_code(500);
            $viewFile = (defined('VIEWS_PATH') ? VIEWS_PATH : __DIR__ . '/../views') . '/errors/500.php';
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                echo '<h1>Something went wrong</h1><p>The error has been logged and will be investigated.</p>';
            }
        }
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}
