<?php
declare(strict_types=1);

/**
 * HttpClient
 * Reusable cURL wrapper for external API calls used by automation modules.
 */
class HttpClient
{
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_RETRIES = 2;
    private const DEFAULT_RETRY_DELAY_MS = 300;
    private const USER_AGENT = 'DevLync-Automation/1.0';

    /**
     * Sends a GET request.
     *
     * @param string $url
     * @param array  $headers
     * @param int    $timeout
     * @param array  $options
     * @return array{
     *   success: bool,
     *   status_code: int,
     *   body: string,
     *   json: array|null,
     *   headers: array,
     *   error: string|null,
     *   time_ms: int
     * }
     */
    public static function get(string $url, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT, array $options = []): array
    {
        return self::request('GET', $url, null, $headers, $timeout, $options);
    }

    /**
     * Sends a POST request with raw body.
     *
     * @param string      $url
     * @param string|null $body
     * @param array       $headers
     * @param int         $timeout
     * @param array       $options
     * @return array{
     *   success: bool,
     *   status_code: int,
     *   body: string,
     *   json: array|null,
     *   headers: array,
     *   error: string|null,
     *   time_ms: int
     * }
     */
    public static function post(string $url, ?string $body = null, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT, array $options = []): array
    {
        return self::request('POST', $url, $body, $headers, $timeout, $options);
    }

    /**
     * Sends a POST request with JSON body.
     *
     * @param string $url
     * @param mixed  $data
     * @param array  $headers
     * @param int    $timeout
     * @param array  $options
     * @return array{
     *   success: bool,
     *   status_code: int,
     *   body: string,
     *   json: array|null,
     *   headers: array,
     *   error: string|null,
     *   time_ms: int
     * }
     */
    public static function postJson(string $url, mixed $data, array $headers = [], int $timeout = self::DEFAULT_TIMEOUT, array $options = []): array
    {
        $jsonBody = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonBody === false) {
            return self::buildResult(false, 0, '', null, [], 'Failed to encode JSON body', 0);
        }

        $lower = array_map(static fn($h) => strtolower((string) $h), $headers);
        $hasJsonHeader = false;
        foreach ($lower as $line) {
            if (str_starts_with($line, 'content-type:') && str_contains($line, 'application/json')) {
                $hasJsonHeader = true;
                break;
            }
        }
        if (!$hasJsonHeader) {
            $headers[] = 'Content-Type: application/json';
        }

        return self::request('POST', $url, $jsonBody, $headers, $timeout, $options);
    }

    /**
     * Internal request runner with retry support.
     *
     * @param string      $method
     * @param string      $url
     * @param string|null $body
     * @param array       $headers
     * @param int         $timeout
     * @param array       $options
     * @return array{
     *   success: bool,
     *   status_code: int,
     *   body: string,
     *   json: array|null,
     *   headers: array,
     *   error: string|null,
     *   time_ms: int
     * }
     */
    private static function request(string $method, string $url, ?string $body, array $headers, int $timeout, array $options): array
    {
        $timeout = $timeout > 0 ? $timeout : self::DEFAULT_TIMEOUT;
        $retries = max(0, (int) ($options['retries'] ?? self::DEFAULT_RETRIES));
        $retryDelayMs = max(50, (int) ($options['retry_delay_ms'] ?? self::DEFAULT_RETRY_DELAY_MS));
        $maxAttempts = $retries + 1;
        $runId = isset($options['run_id']) ? (int) $options['run_id'] : null;
        $module = isset($options['module']) ? (string) $options['module'] : '';
        $step = (string) ($options['step'] ?? 'http_request');
        $followRedirects = (bool) ($options['follow_redirects'] ?? true);
        $verifySsl = (bool) ($options['verify_ssl'] ?? true);
        $connectTimeout = max(1, (int) ($options['connect_timeout'] ?? min(10, $timeout)));
        $retryStatuses = $options['retry_on_status'] ?? [408, 425, 429, 500, 502, 503, 504];

        $normalizedHeaders = self::normalizeHeaders($headers);
        if (!self::hasHeader($normalizedHeaders, 'User-Agent')) {
            $normalizedHeaders[] = 'User-Agent: ' . self::USER_AGENT;
        }

        self::log($module, $runId, 'debug', $step, 'HTTP request started', [
            'method' => $method,
            'url' => $url,
            'timeout' => $timeout,
            'attempts' => $maxAttempts,
        ]);

        $lastResult = self::buildResult(false, 0, '', null, [], null, 0);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $result = self::executeOnce(
                $method,
                $url,
                $body,
                $normalizedHeaders,
                $timeout,
                $connectTimeout,
                $followRedirects,
                $verifySsl
            );
            $lastResult = $result;

            if ($result['success']) {
                self::log($module, $runId, 'info', $step, 'HTTP request succeeded', [
                    'method' => $method,
                    'url' => $url,
                    'status_code' => $result['status_code'],
                    'time_ms' => $result['time_ms'],
                    'attempt' => $attempt,
                ]);
                return $result;
            }

            $shouldRetry = $attempt < $maxAttempts && self::shouldRetry($result, $retryStatuses);
            if (!$shouldRetry) {
                break;
            }

            $backoffMs = $retryDelayMs * (2 ** ($attempt - 1));
            self::log($module, $runId, 'warning', $step, 'HTTP request retry scheduled', [
                'method' => $method,
                'url' => $url,
                'status_code' => $result['status_code'],
                'error' => $result['error'],
                'attempt' => $attempt,
                'next_delay_ms' => $backoffMs,
            ]);
            usleep($backoffMs * 1000);
        }

        self::log($module, $runId, 'error', $step, 'HTTP request failed', [
            'method' => $method,
            'url' => $url,
            'status_code' => $lastResult['status_code'],
            'error' => $lastResult['error'],
            'time_ms' => $lastResult['time_ms'],
        ]);

        return $lastResult;
    }

    /**
     * Executes a single cURL request attempt.
     *
     * @param string      $method
     * @param string      $url
     * @param string|null $body
     * @param array       $headers
     * @param int         $timeout
     * @param int         $connectTimeout
     * @param bool        $followRedirects
     * @param bool        $verifySsl
     * @return array{
     *   success: bool,
     *   status_code: int,
     *   body: string,
     *   json: array|null,
     *   headers: array,
     *   error: string|null,
     *   time_ms: int
     * }
     */
    private static function executeOnce(
        string $method,
        string $url,
        ?string $body,
        array $headers,
        int $timeout,
        int $connectTimeout,
        bool $followRedirects,
        bool $verifySsl
    ): array {
        $responseHeaders = [];
        $ch = curl_init($url);
        if ($ch === false) {
            return self::buildResult(false, 0, '', null, [], 'Failed to initialize cURL', 0);
        }

        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => $followRedirects,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$responseHeaders): int {
                $trimmed = trim($headerLine);
                if ($trimmed === '' || !str_contains($trimmed, ':')) {
                    return strlen($headerLine);
                }
                [$name, $value] = explode(':', $trimmed, 2);
                $headerName = strtolower(trim($name));
                $headerValue = trim($value);
                if (!isset($responseHeaders[$headerName])) {
                    $responseHeaders[$headerName] = $headerValue;
                } else {
                    if (!is_array($responseHeaders[$headerName])) {
                        $responseHeaders[$headerName] = [$responseHeaders[$headerName]];
                    }
                    $responseHeaders[$headerName][] = $headerValue;
                }
                return strlen($headerLine);
            },
        ];

        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH' || $method === 'DELETE') {
            $curlOptions[CURLOPT_POSTFIELDS] = $body ?? '';
        }

        curl_setopt_array($ch, $curlOptions);

        $startedAt = microtime(true);
        $rawBody = curl_exec($ch);
        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
        $curlErrNo = curl_errno($ch);
        $curlErr = $curlErrNo ? curl_error($ch) : null;
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrNo !== 0) {
            return self::buildResult(false, 0, '', null, $responseHeaders, 'cURL error: ' . $curlErr, $elapsedMs);
        }

        $bodyString = is_string($rawBody) ? $rawBody : '';
        $decoded = json_decode($bodyString, true);
        $jsonData = is_array($decoded) ? $decoded : null;
        $success = $statusCode >= 200 && $statusCode < 300;
        $error = $success ? null : ('HTTP ' . $statusCode);

        return self::buildResult($success, $statusCode, $bodyString, $jsonData, $responseHeaders, $error, $elapsedMs);
    }

    /**
     * Determines whether a failed request should be retried.
     *
     * @param array $result
     * @param array $retryStatuses
     * @return bool
     */
    private static function shouldRetry(array $result, array $retryStatuses): bool
    {
        if (($result['status_code'] ?? 0) > 0) {
            return in_array((int) $result['status_code'], $retryStatuses, true);
        }
        return !empty($result['error']);
    }

    /**
     * Builds a normalized response payload.
     *
     * @param bool       $success
     * @param int        $statusCode
     * @param string     $body
     * @param array|null $json
     * @param array      $headers
     * @param string|null $error
     * @param int        $timeMs
     * @return array{
     *   success: bool,
     *   status_code: int,
     *   body: string,
     *   json: array|null,
     *   headers: array,
     *   error: string|null,
     *   time_ms: int
     * }
     */
    private static function buildResult(
        bool $success,
        int $statusCode,
        string $body,
        ?array $json,
        array $headers,
        ?string $error,
        int $timeMs
    ): array {
        return [
            'success' => $success,
            'status_code' => $statusCode,
            'body' => $body,
            'json' => $json,
            'headers' => $headers,
            'error' => $error,
            'time_ms' => $timeMs,
        ];
    }

    /**
     * Normalizes request header lines.
     *
     * @param array $headers
     * @return array
     */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $header) {
            $line = trim((string) $header);
            if ($line === '') {
                continue;
            }
            $normalized[] = $line;
        }
        return $normalized;
    }

    /**
     * Checks if a header line is present.
     *
     * @param array  $headers
     * @param string $headerName
     * @return bool
     */
    private static function hasHeader(array $headers, string $headerName): bool
    {
        $target = strtolower($headerName) . ':';
        foreach ($headers as $header) {
            if (str_starts_with(strtolower((string) $header), $target)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Writes request lifecycle logs to automation_logs and php error log.
     *
     * @param string   $module
     * @param int|null $runId
     * @param string   $level
     * @param string   $step
     * @param string   $message
     * @param array    $context
     * @return void
     */
    private static function log(string $module, ?int $runId, string $level, string $step, string $message, array $context = []): void
    {
        $line = sprintf(
            '[HttpClient] %s | %s | %s',
            strtoupper($level),
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        error_log($line);

        if (!$runId || $module === '' || !class_exists('Database')) {
            return;
        }

        try {
            $db = Database::getInstance();
            $db->execute(
                'INSERT INTO automation_logs (run_id, module, log_level, step, message, context_data)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $runId,
                    $module,
                    $level,
                    $step,
                    $message,
                    json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (Throwable) {
            // Avoid cascading failures from logging.
        }
    }
}
