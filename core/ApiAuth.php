<?php
declare(strict_types=1);

/**
 * API Key Authentication
 * Verifies X-API-Key header for all n8n webhook endpoints.
 */
class ApiAuth
{
    /**
     * Verifies the X-API-Key header against the stored API key.
     * Sends a 403 JSON response and exits if the key is invalid or missing.
     *
     * @return void
     */
    public static function verify(): void
    {
        $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $storedKey = Setting::get('api_key');

        if (!$storedKey || !hash_equals($storedKey, $providedKey)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized',
                'code' => 403,
            ]);
            exit;
        }
    }

    /**
     * Returns true if the current request has a valid API key in the header.
     *
     * @return bool
     */
    public static function isValid(): bool
    {
        $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $storedKey = Setting::get('api_key');
        return (bool) ($storedKey && hash_equals($storedKey, $providedKey));
    }
}
