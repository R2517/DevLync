<?php
declare(strict_types=1);

/**
 * Automation Model
 * Core data and execution helpers for the Automation Center.
 */
class Automation
{
    private Database $db;
    private Supervisor $supervisor;
    private string $lockDir;

    public function __construct(?Database $db = null, ?Supervisor $supervisor = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->supervisor = $supervisor ?? new Supervisor();
        $this->lockDir = ROOT_PATH . '/cache/locks';
    }

    /**
     * Ensures required runtime directories exist.
     *
     * @return void
     */
    public function ensureRuntimeDirectories(): void
    {
        $dirs = [
            $this->lockDir,
            ROOT_PATH . '/uploads/images',
            ROOT_PATH . '/uploads/images/social',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Acquires a lock file for a module.
     *
     * @param string $lockKey
     * @param int    $timeoutSeconds
     * @return bool
     */
    public function acquireLock(string $lockKey, int $timeoutSeconds = 900): bool
    {
        $this->ensureRuntimeDirectories();
        $path = $this->getLockPath($lockKey);

        if (is_file($path)) {
            $age = time() - (int) @filemtime($path);
            if ($age < $timeoutSeconds) {
                return false;
            }
            @unlink($path);
        }

        $written = @file_put_contents(
            $path,
            json_encode(['lock_key' => $lockKey, 'pid' => getmypid() ?: 0, 'created_at' => gmdate('Y-m-d H:i:s')]),
            LOCK_EX
        );

        return $written !== false;
    }

    /**
     * Releases a lock file.
     *
     * @param string $lockKey
     * @return void
     */
    public function releaseLock(string $lockKey): void
    {
        $path = $this->getLockPath($lockKey);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Deletes stale lock files.
     *
     * @param int $timeoutSeconds
     * @return int
     */
    public function cleanupStaleLocks(int $timeoutSeconds = 900): int
    {
        $this->ensureRuntimeDirectories();
        $files = glob($this->lockDir . '/*.lock') ?: [];
        $removed = 0;

        foreach ($files as $file) {
            $age = time() - (int) @filemtime($file);
            if ($age > $timeoutSeconds && @unlink($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Creates a run record in automation_runs.
     *
     * @param string      $module
     * @param string      $triggerType
     * @param string      $triggeredBy
     * @param string|null $lockKey
     * @return int
     */
    public function createRun(
        string $module,
        string $triggerType = 'manual',
        string $triggeredBy = 'system',
        ?string $lockKey = null
    ): int {
        $this->db->execute(
            'INSERT INTO automation_runs (module, trigger_type, status, triggered_by, lock_key)
             VALUES (?, ?, \'running\', ?, ?)',
            [$module, $triggerType, $triggeredBy, $lockKey]
        );

        $runId = $this->db->lastInsertId();
        $this->log($runId, $module, 'info', 'run_start', 'Automation run started', [
            'trigger_type' => $triggerType,
            'triggered_by' => $triggeredBy,
        ]);

        $this->safeSupervisorActivity('automation_start', "Automation module '{$module}' started", [
            'run_id' => $runId,
            'module' => $module,
            'trigger_type' => $triggerType,
            'triggered_by' => $triggeredBy,
        ]);

        return $runId;
    }

    /**
     * Marks a run completed.
     *
     * @param int   $runId
     * @param array $result
     * @return void
     */
    public function completeRun(int $runId, array $result = []): void
    {
        $run = $this->db->queryOne('SELECT module FROM automation_runs WHERE id = ? LIMIT 1', [$runId]);
        $module = (string) ($run['module'] ?? 'unknown');
        $processed = (int) ($result['processed'] ?? 0);
        $succeeded = (int) ($result['succeeded'] ?? $processed);
        $failed = (int) ($result['failed'] ?? 0);

        $this->db->execute(
            'UPDATE automation_runs
             SET status = \'completed\',
                 completed_at = NOW(),
                 duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()),
                 items_processed = ?,
                 items_succeeded = ?,
                 items_failed = ?,
                 result_data = ?
             WHERE id = ?',
            [$processed, $succeeded, $failed, $this->encodeJson($result), $runId]
        );

        $this->log($runId, $module, 'info', 'run_complete', 'Automation run completed', [
            'processed' => $processed,
            'failed' => $failed,
        ]);
        $this->safeSupervisorActivity('automation_success', "Automation module '{$module}' completed", [
            'run_id' => $runId,
            'module' => $module,
            'processed' => $processed,
            'failed' => $failed,
        ]);
        $this->safeSupervisorTelegram(
            "Module {$module} completed.\nRun ID: {$runId}\nProcessed: {$processed}, Failed: {$failed}",
            'success',
            'completion'
        );
    }

    /**
     * Marks a run failed.
     *
     * @param int              $runId
     * @param Throwable|string $error
     * @param array            $result
     * @return void
     */
    public function failRun(int $runId, Throwable|string $error, array $result = []): void
    {
        $run = $this->db->queryOne('SELECT module FROM automation_runs WHERE id = ? LIMIT 1', [$runId]);
        $module = (string) ($run['module'] ?? 'unknown');
        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        $this->db->execute(
            'UPDATE automation_runs
             SET status = \'failed\',
                 completed_at = NOW(),
                 duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()),
                 error_message = ?,
                 result_data = ?
             WHERE id = ?',
            [$message, $this->encodeJson($result), $runId]
        );

        $this->log($runId, $module, 'critical', 'run_failed', 'Automation run failed', ['error' => $message]);
        error_log("[Automation] {$module} failed (run_id={$runId}): {$message}");
        $this->safeSupervisorError('api', 'critical', "Automation module '{$module}' failed: {$message}");
        $this->safeSupervisorTelegram(
            "Module {$module} failed.\nRun ID: {$runId}\nError: " . mb_substr($message, 0, 250),
            'critical',
            'failure'
        );
    }

    /**
     * Marks a run skipped.
     *
     * @param int    $runId
     * @param string $reason
     * @param array  $result
     * @return void
     */
    public function skipRun(int $runId, string $reason, array $result = []): void
    {
        $run = $this->db->queryOne('SELECT module FROM automation_runs WHERE id = ? LIMIT 1', [$runId]);
        $module = (string) ($run['module'] ?? 'unknown');

        $this->db->execute(
            'UPDATE automation_runs
             SET status = \'skipped\',
                 completed_at = NOW(),
                 duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()),
                 error_message = ?,
                 result_data = ?
             WHERE id = ?',
            [$reason, $this->encodeJson($result), $runId]
        );

        $this->log($runId, $module, 'warning', 'run_skipped', 'Automation run skipped', ['reason' => $reason]);
        $this->safeSupervisorActivity('automation_skipped', "Automation module '{$module}' skipped", [
            'run_id' => $runId,
            'module' => $module,
            'reason' => $reason,
        ]);
    }

    /**
     * Records lock-based skip when a module cannot start.
     *
     * @param string $module
     * @param string $lockKey
     * @param string $triggerType
     * @param string $triggeredBy
     * @return void
     */
    public function recordLockSkip(string $module, string $lockKey, string $triggerType, string $triggeredBy): void
    {
        $this->safeSupervisorActivity('automation_locked_skip', "Automation module '{$module}' skipped due to active lock", [
            'module' => $module,
            'lock_key' => $lockKey,
            'trigger_type' => $triggerType,
            'triggered_by' => $triggeredBy,
        ]);
    }

    /**
     * Writes a log line to automation_logs.
     *
     * @param int|null $runId
     * @param string   $module
     * @param string   $level
     * @param string   $step
     * @param string   $message
     * @param array    $context
     * @return void
     */
    public function log(?int $runId, string $module, string $level, string $step, string $message, array $context = []): void
    {
        $normalizedLevel = in_array($level, ['debug', 'info', 'warning', 'error', 'critical'], true) ? $level : 'info';
        $this->db->execute(
            'INSERT INTO automation_logs (run_id, module, log_level, step, message, context_data)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$runId, $module, $normalizedLevel, $step, $message, $this->encodeJson($context)]
        );
    }

    /**
     * Returns all schedules.
     *
     * @return array
     */
    public function getSchedules(): array
    {
        return $this->db->query('SELECT * FROM automation_schedules ORDER BY id ASC');
    }

    /**
     * Returns one schedule by module.
     *
     * @param string $module
     * @return array|null
     */
    public function getSchedule(string $module): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM automation_schedules WHERE module = ? LIMIT 1',
            [$module]
        );
    }

    /**
     * Returns enabled schedules due now (UTC).
     *
     * @param DateTimeImmutable|null $now
     * @return array
     */
    public function getDueSchedules(?DateTimeImmutable $now = null): array
    {
        $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        return $this->db->query(
            'SELECT * FROM automation_schedules
             WHERE is_enabled = 1
               AND (next_run_at IS NULL OR next_run_at <= ?)
             ORDER BY id ASC',
            [$now->format('Y-m-d H:i:s')]
        );
    }

    /**
     * Updates schedule stats and next run time after a run.
     *
     * @param string $module
     * @param bool   $success
     * @param int    $durationSeconds
     * @return void
     */
    public function updateScheduleAfterRun(string $module, bool $success, int $durationSeconds): void
    {
        $schedule = $this->getSchedule($module);
        if (!$schedule) {
            return;
        }

        $totalRuns = (int) ($schedule['total_runs'] ?? 0) + 1;
        $oldAvg = (int) ($schedule['avg_duration_seconds'] ?? 0);
        $newAvg = (int) round((($oldAvg * ($totalRuns - 1)) + $durationSeconds) / max(1, $totalRuns));
        $oldSuccessRate = (float) ($schedule['success_rate'] ?? 100.0);
        $oldSuccessRuns = (int) round(($oldSuccessRate / 100) * max(0, $totalRuns - 1));
        $newSuccessRate = round((($oldSuccessRuns + ($success ? 1 : 0)) / max(1, $totalRuns)) * 100, 2);
        $nextRun = $this->computeNextRun((string) $schedule['cron_expression']);

        $this->db->execute(
            'UPDATE automation_schedules
             SET last_run_at = NOW(),
                 next_run_at = ?,
                 total_runs = ?,
                 avg_duration_seconds = ?,
                 success_rate = ?
             WHERE module = ?',
            [$nextRun?->format('Y-m-d H:i:s'), $totalRuns, $newAvg, $newSuccessRate, $module]
        );
    }

    /**
     * Sets a schedule next run from cron expression.
     *
     * @param string                 $module
     * @param DateTimeImmutable|null $from
     * @return string|null
     */
    public function updateScheduleNextRun(string $module, ?DateTimeImmutable $from = null): ?string
    {
        $schedule = $this->getSchedule($module);
        if (!$schedule) {
            return null;
        }

        $next = $this->computeNextRun((string) $schedule['cron_expression'], $from);
        $nextStr = $next?->format('Y-m-d H:i:s');
        $this->db->execute('UPDATE automation_schedules SET next_run_at = ? WHERE module = ?', [$nextStr, $module]);

        return $nextStr;
    }

    /**
     * Updates schedule configuration from admin API.
     *
     * @param string $module
     * @param array  $data
     * @return bool
     */
    public function updateSchedule(string $module, array $data): bool
    {
        $allowed = ['is_enabled', 'cron_expression', 'timeout_seconds', 'max_retries', 'retry_delay_seconds', 'lock_timeout_seconds'];
        $sets = [];
        $params = [];

        foreach ($data as $col => $value) {
            if (!in_array($col, $allowed, true)) {
                continue;
            }
            $sets[] = "{$col} = ?";
            $params[] = $value;
        }

        if (!$sets) {
            return false;
        }

        $params[] = $module;
        $this->db->execute(
            'UPDATE automation_schedules SET ' . implode(', ', $sets) . ' WHERE module = ?',
            $params
        );

        if (isset($data['cron_expression'])) {
            $this->updateScheduleNextRun($module);
        }

        return true;
    }

    /**
     * Returns recent run history.
     *
     * @param int         $limit
     * @param string|null $module
     * @param string|null $status
     * @return array
     */
    public function getRunHistory(int $limit = 50, ?string $module = null, ?string $status = null): array
    {
        $where = [];
        $params = [];

        if ($module !== null && $module !== '') {
            $where[] = 'module = ?';
            $params[] = $module;
        }
        if ($status !== null && $status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $params[] = max(1, $limit);

        return $this->db->query(
            "SELECT * FROM automation_runs {$whereSql} ORDER BY id DESC LIMIT ?",
            $params
        );
    }

    /**
     * Returns logs for one run.
     *
     * @param int $runId
     * @param int $limit
     * @return array
     */
    public function getRunLogs(int $runId, int $limit = 500): array
    {
        return $this->db->query(
            'SELECT * FROM automation_logs WHERE run_id = ? ORDER BY id ASC LIMIT ?',
            [$runId, max(1, $limit)]
        );
    }

    /**
     * Returns dashboard data for admin.
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        $summary = $this->db->queryOne(
            'SELECT
                SUM(status = \'running\') AS running,
                SUM(status = \'completed\') AS completed,
                SUM(status = \'failed\') AS failed,
                SUM(status = \'skipped\') AS skipped
             FROM automation_runs
             WHERE started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
        ) ?? [];

        return [
            'schedules' => $this->getSchedules(),
            'recent_runs' => $this->getRunHistory(20),
            'stats' => [
                'running' => (int) ($summary['running'] ?? 0),
                'completed' => (int) ($summary['completed'] ?? 0),
                'failed' => (int) ($summary['failed'] ?? 0),
                'skipped' => (int) ($summary['skipped'] ?? 0),
            ],
        ];
    }

    /**
     * Returns provider configurations.
     *
     * @param bool $onlyEnabled
     * @return array
     */
    public function getProviders(bool $onlyEnabled = false): array
    {
        $where = $onlyEnabled ? 'WHERE is_enabled = 1 AND is_active = 1' : '';
        return $this->db->query("SELECT * FROM ai_provider_configs {$where} ORDER BY priority ASC, id ASC");
    }

    /**
     * Updates provider config from admin API.
     *
     * @param int   $id
     * @param array $data
     * @return bool
     */
    public function updateProvider(int $id, array $data): bool
    {
        $allowed = ['display_name', 'api_endpoint', 'is_enabled', 'priority', 'max_output_tokens', 'supports_json_mode', 'is_active', 'notes'];
        $sets = [];
        $params = [];

        foreach ($data as $col => $value) {
            if (!in_array($col, $allowed, true)) {
                continue;
            }
            $sets[] = "{$col} = ?";
            $params[] = $value;
        }

        if (!$sets) {
            return false;
        }

        $params[] = $id;
        $this->db->execute('UPDATE ai_provider_configs SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
        return true;
    }

    /**
     * Builds provider fallback chain for a module.
     *
     * @param string $module
     * @return array
     */
    public function getProviderChain(string $module): array
    {
        $moduleKey = str_starts_with($module, 'writer_') ? 'writer' : $module;
        $rows = $this->db->query(
            'SELECT * FROM ai_provider_configs
             WHERE is_enabled = 1
               AND is_active = 1
               AND JSON_SEARCH(use_for, \'one\', ?) IS NOT NULL
             ORDER BY priority ASC, id ASC',
            [$moduleKey]
        );
        if (!$rows) {
            return [];
        }

        $primary = (string) (Setting::get('automation_primary_ai') ?? '');
        $fallback = (string) (Setting::get('automation_fallback_ai') ?? '');
        $social = (string) (Setting::get('automation_social_ai') ?? '');

        usort($rows, static function (array $a, array $b) use ($primary, $fallback, $social, $module): int {
            $weightA = (int) $a['priority'];
            $weightB = (int) $b['priority'];
            if ((string) $a['provider'] === $primary) {
                $weightA -= 1000;
            } elseif ((string) $a['provider'] === $fallback) {
                $weightA -= 500;
            } elseif ($module === 'social' && (string) $a['provider'] === $social) {
                $weightA -= 700;
            }
            if ((string) $b['provider'] === $primary) {
                $weightB -= 1000;
            } elseif ((string) $b['provider'] === $fallback) {
                $weightB -= 500;
            } elseif ($module === 'social' && (string) $b['provider'] === $social) {
                $weightB -= 700;
            }
            return $weightA <=> $weightB;
        });

        return $rows;
    }

    /**
     * Runs AI request with provider fallback.
     *
     * @param string $module
     * @param array  $messages
     * @param array  $options
     * @return array
     */
    public function callAiWithFallback(string $module, array $messages, array $options = []): array
    {
        $providers = $this->getProviderChain($module);
        $errors = [];

        foreach ($providers as $provider) {
            $response = $this->callProvider($provider, $messages, $options + ['module' => $module]);
            if ($response['success']) {
                return $response;
            }
            $errors[] = (string) $provider['provider'] . ': ' . (string) ($response['error'] ?? 'Unknown error');
        }

        return [
            'success' => false,
            'error' => $errors ? implode(' | ', $errors) : 'No enabled providers for this module',
            'provider' => null,
            'model' => null,
            'content' => null,
            'usage' => ['input_tokens' => 0, 'output_tokens' => 0],
            'cost_usd' => 0.0,
            'raw' => null,
        ];
    }

    /**
     * Tests one provider with a small prompt.
     *
     * @param int    $id
     * @param string $module
     * @return array
     */
    public function testProvider(int $id, string $module = 'writer_blog'): array
    {
        $provider = $this->db->queryOne('SELECT * FROM ai_provider_configs WHERE id = ? LIMIT 1', [$id]);
        if (!$provider) {
            return ['success' => false, 'error' => 'Provider not found'];
        }

        return $this->callProvider($provider, [
            ['role' => 'system', 'content' => 'Return short JSON only.'],
            ['role' => 'user', 'content' => 'Respond with {"ok":true}'],
        ], ['module' => $module, 'json_mode' => true, 'timeout' => 30]);
    }

    /**
     * Returns platform configurations.
     *
     * @return array
     */
    public function getPlatforms(): array
    {
        return $this->db->query('SELECT * FROM social_platform_configs ORDER BY id ASC');
    }

    /**
     * Updates social platform config.
     *
     * @param string $platform
     * @param array  $data
     * @return bool
     */
    public function updatePlatform(string $platform, array $data): bool
    {
        $allowed = ['is_enabled', 'rate_limit_per_hour', 'rate_limit_per_day', 'notes', 'credentials_json', 'post_format'];
        $sets = [];
        $params = [];

        foreach ($data as $col => $value) {
            if (!in_array($col, $allowed, true)) {
                continue;
            }
            $sets[] = "{$col} = ?";
            $params[] = $value;
        }

        if (!$sets) {
            return false;
        }

        $params[] = $platform;
        $this->db->execute('UPDATE social_platform_configs SET ' . implode(', ', $sets) . ' WHERE platform = ?', $params);
        return true;
    }

    /**
     * Basic platform configuration test.
     *
     * @param string $platform
     * @return array
     */
    public function testPlatform(string $platform): array
    {
        $row = $this->db->queryOne(
            'SELECT platform, is_enabled, credentials_json FROM social_platform_configs WHERE platform = ? LIMIT 1',
            [$platform]
        );
        if (!$row) {
            return ['success' => false, 'error' => 'Platform not found'];
        }

        return [
            'success' => true,
            'platform' => $platform,
            'enabled' => (int) $row['is_enabled'] === 1,
            'has_credentials' => !empty((string) $row['credentials_json']),
            'message' => 'Configuration record found',
        ];
    }

    /**
     * Upserts one setting.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function updateSetting(string $key, string $value): void
    {
        Setting::set($key, $value);
    }

    /**
     * Runs social queue worker placeholder.
     *
     * @param int $limit
     * @return array
     */
    public function processSocialQueue(int $limit = 20): array
    {
        $rows = $this->db->query(
            'SELECT id FROM social_post_queue WHERE status = \'queued\' ORDER BY id ASC LIMIT ?',
            [max(1, $limit)]
        );

        $processed = 0;
        foreach ($rows as $row) {
            $this->db->execute(
                'UPDATE social_post_queue
                 SET status = \'skipped\',
                     error_message = ?
                 WHERE id = ?',
                ['Social publisher will be enabled in Module 3 implementation', (int) $row['id']]
            );
            $processed++;
        }

        return ['processed' => $processed, 'succeeded' => 0, 'failed' => 0];
    }

    /**
     * Executes one provider request.
     *
     * @param array $provider
     * @param array $messages
     * @param array $options
     * @return array
     */
    private function callProvider(array $provider, array $messages, array $options = []): array
    {
        $providerName = (string) ($provider['provider'] ?? '');
        $apiKeySetting = (string) ($provider['api_key_setting'] ?? '');
        $apiKey = trim((string) (Setting::get($apiKeySetting) ?? ''));

        if ($apiKey === '') {
            return [
                'success' => false,
                'error' => "Missing API key in settings: {$apiKeySetting}",
                'provider' => $providerName,
                'model' => (string) ($provider['model_id'] ?? ''),
                'content' => null,
                'usage' => ['input_tokens' => 0, 'output_tokens' => 0],
                'cost_usd' => 0.0,
                'raw' => null,
            ];
        }

        $request = $this->buildProviderRequest($provider, $messages, $apiKey, $options);
        $response = HttpClient::postJson(
            (string) $request['url'],
            $request['payload'],
            $request['headers'],
            (int) ($options['timeout'] ?? 30),
            ['retries' => 2, 'retry_delay_ms' => 300, 'verify_ssl' => true]
        );

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => (string) ($response['error'] ?? 'Request failed'),
                'provider' => $providerName,
                'model' => (string) ($provider['model_id'] ?? ''),
                'content' => null,
                'usage' => ['input_tokens' => 0, 'output_tokens' => 0],
                'cost_usd' => 0.0,
                'raw' => $response['json'] ?? null,
            ];
        }

        $content = $this->parseProviderContent($providerName, $response['json'] ?? []);
        $usage = $this->parseTokenUsage($providerName, $response['json'] ?? []);
        $cost = $this->estimateCost($provider, $usage['input_tokens'], $usage['output_tokens']);
        $this->recordProviderMetrics($provider, $usage, $cost);

        if ($content === '') {
            return [
                'success' => false,
                'error' => 'Provider returned empty content',
                'provider' => $providerName,
                'model' => (string) ($provider['model_id'] ?? ''),
                'content' => null,
                'usage' => $usage,
                'cost_usd' => $cost,
                'raw' => $response['json'] ?? null,
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'provider' => $providerName,
            'model' => (string) ($provider['model_id'] ?? ''),
            'content' => $content,
            'usage' => $usage,
            'cost_usd' => $cost,
            'raw' => $response['json'] ?? null,
        ];
    }

    /**
     * Prepares HTTP payload and headers per provider.
     *
     * @param array  $provider
     * @param array  $messages
     * @param string $apiKey
     * @param array  $options
     * @return array{url: string, headers: array, payload: array}
     */
    private function buildProviderRequest(array $provider, array $messages, string $apiKey, array $options): array
    {
        $providerName = (string) $provider['provider'];
        $endpoint = (string) $provider['api_endpoint'];
        $model = (string) $provider['model_id'];
        $temperature = isset($options['temperature']) ? (float) $options['temperature'] : 0.5;
        $maxTokens = isset($options['max_tokens']) ? (int) $options['max_tokens'] : (int) ($provider['max_output_tokens'] ?? 2048);
        $jsonMode = !empty($options['json_mode']);

        if ($providerName === 'gemini') {
            $systemText = $this->extractSystemText($messages);
            $userText = $this->extractNonSystemText($messages);
            $payload = [
                'contents' => [['role' => 'user', 'parts' => [['text' => $userText]]]],
                'generationConfig' => ['temperature' => $temperature, 'maxOutputTokens' => $maxTokens],
            ];
            if ($systemText !== '') {
                $payload['systemInstruction'] = ['parts' => [['text' => $systemText]]];
            }
            if ($jsonMode) {
                $payload['generationConfig']['responseMimeType'] = 'application/json';
            }

            return [
                'url' => $endpoint . '?key=' . rawurlencode($apiKey),
                'headers' => ['Content-Type: application/json'],
                'payload' => $payload,
            ];
        }

        if ($providerName === 'claude') {
            $systemText = $this->extractSystemText($messages);
            $claudeMessages = $this->buildRoleMessages($messages);
            $payload = [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'messages' => $claudeMessages,
            ];
            if ($systemText !== '') {
                $payload['system'] = $systemText;
            }

            return [
                'url' => $endpoint,
                'headers' => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $apiKey,
                    'anthropic-version: 2023-06-01',
                ],
                'payload' => $payload,
            ];
        }

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ];
        if ($providerName === 'openrouter') {
            $headers[] = 'HTTP-Referer: https://devlync.com';
            $headers[] = 'X-Title: devlync.com';
        }

        $payload = [
            'model' => $model,
            'messages' => $this->buildOpenAiMessages($messages),
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];
        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        return ['url' => $endpoint, 'headers' => $headers, 'payload' => $payload];
    }

    /**
     * Extracts provider text response.
     *
     * @param string $providerName
     * @param array  $json
     * @return string
     */
    private function parseProviderContent(string $providerName, array $json): string
    {
        if ($providerName === 'gemini') {
            $parts = $json['candidates'][0]['content']['parts'] ?? [];
            $chunks = [];
            foreach ($parts as $part) {
                $text = trim((string) ($part['text'] ?? ''));
                if ($text !== '') {
                    $chunks[] = $text;
                }
            }
            return trim(implode("\n", $chunks));
        }

        if ($providerName === 'claude') {
            $content = $json['content'][0]['text'] ?? '';
            return trim((string) $content);
        }

        return trim((string) ($json['choices'][0]['message']['content'] ?? ''));
    }

    /**
     * Extracts token usage from provider response.
     *
     * @param string $providerName
     * @param array  $json
     * @return array{input_tokens: int, output_tokens: int}
     */
    private function parseTokenUsage(string $providerName, array $json): array
    {
        if ($providerName === 'gemini') {
            return [
                'input_tokens' => (int) ($json['usageMetadata']['promptTokenCount'] ?? 0),
                'output_tokens' => (int) ($json['usageMetadata']['candidatesTokenCount'] ?? 0),
            ];
        }

        $usage = $json['usage'] ?? [];
        return [
            'input_tokens' => (int) ($usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0),
            'output_tokens' => (int) ($usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0),
        ];
    }

    /**
     * Stores provider usage and cost metrics.
     *
     * @param array $provider
     * @param array $usage
     * @param float $cost
     * @return void
     */
    private function recordProviderMetrics(array $provider, array $usage, float $cost): void
    {
        $this->db->execute(
            'UPDATE ai_provider_configs
             SET total_requests = total_requests + 1,
                 total_tokens_used = total_tokens_used + ?,
                 total_cost_usd = total_cost_usd + ?,
                 last_used_at = NOW(),
                 last_error = NULL
             WHERE id = ?',
            [
                (int) ($usage['input_tokens'] ?? 0) + (int) ($usage['output_tokens'] ?? 0),
                $cost,
                (int) $provider['id'],
            ]
        );

        if ($cost > 0) {
            CostRecord::create([
                'article_id' => null,
                'step' => 'automation_ai',
                'model' => (string) ($provider['model_id'] ?? ''),
                'provider' => (string) ($provider['provider'] ?? ''),
                'input_tokens' => (int) ($usage['input_tokens'] ?? 0),
                'output_tokens' => (int) ($usage['output_tokens'] ?? 0),
                'cost' => $cost,
            ]);
        }
    }

    /**
     * Estimates AI call cost in USD.
     *
     * @param array $provider
     * @param int   $inputTokens
     * @param int   $outputTokens
     * @return float
     */
    private function estimateCost(array $provider, int $inputTokens, int $outputTokens): float
    {
        $inputPer1M = (float) ($provider['input_cost_per_1m'] ?? 0);
        $outputPer1M = (float) ($provider['output_cost_per_1m'] ?? 0);
        return round((($inputTokens / 1000000) * $inputPer1M) + (($outputTokens / 1000000) * $outputPer1M), 6);
    }

    /**
     * Calculates next run datetime from cron expression (UTC).
     *
     * @param string                 $cronExpression
     * @param DateTimeImmutable|null $from
     * @return DateTimeImmutable|null
     */
    private function computeNextRun(string $cronExpression, ?DateTimeImmutable $from = null): ?DateTimeImmutable
    {
        $from = $from ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));

        if (class_exists('\Cron\CronExpression')) {
            try {
                $cron = \Cron\CronExpression::factory($cronExpression);
                $next = $cron->getNextRunDate($from, 0, true);
                return DateTimeImmutable::createFromInterface($next);
            } catch (Throwable) {
                // Fall through to lightweight parser.
            }
        }

        $parts = preg_split('/\s+/', trim($cronExpression));
        if (!is_array($parts) || count($parts) !== 5) {
            return $from->modify('+15 minutes');
        }

        [$minExpr, $hourExpr, $domExpr, $monthExpr, $dowExpr] = $parts;
        $mins = $this->parseCronField($minExpr, 0, 59, false);
        $hours = $this->parseCronField($hourExpr, 0, 23, false);
        $dom = $this->parseCronField($domExpr, 1, 31, false);
        $months = $this->parseCronField($monthExpr, 1, 12, false);
        $dow = $this->parseCronField($dowExpr, 0, 7, true);

        if ($mins === false || $hours === false || $dom === false || $months === false || $dow === false) {
            return $from->modify('+15 minutes');
        }

        $domAny = $dom === null;
        $dowAny = $dow === null;
        $cursor = $from
            ->setTimezone(new DateTimeZone('UTC'))
            ->setTime((int) $from->format('G'), (int) $from->format('i'), 0)
            ->modify('+1 minute');

        for ($i = 0; $i < 525600; $i++) {
            $minute = (int) $cursor->format('i');
            $hour = (int) $cursor->format('G');
            $day = (int) $cursor->format('j');
            $month = (int) $cursor->format('n');
            $weekday = (int) $cursor->format('w');

            $minuteOk = $mins === null || isset($mins[$minute]);
            $hourOk = $hours === null || isset($hours[$hour]);
            $monthOk = $months === null || isset($months[$month]);
            $domOk = $domAny || isset($dom[$day]);
            $dowOk = $dowAny || isset($dow[$weekday]);
            $dayOk = (!$domAny && !$dowAny) ? ($domOk || $dowOk) : ($domOk && $dowOk);

            if ($minuteOk && $hourOk && $monthOk && $dayOk) {
                return $cursor;
            }

            $cursor = $cursor->modify('+1 minute');
        }

        return $from->modify('+15 minutes');
    }

    /**
     * Parses one cron field into a value map.
     *
     * @param string $expr
     * @param int    $min
     * @param int    $max
     * @param bool   $isDow
     * @return array<int, true>|null|false
     */
    private function parseCronField(string $expr, int $min, int $max, bool $isDow): array|null|false
    {
        $expr = trim($expr);
        if ($expr === '*') {
            return null;
        }

        $values = [];
        $tokens = explode(',', $expr);

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                return false;
            }

            $step = 1;
            if (str_contains($token, '/')) {
                [$base, $stepPart] = explode('/', $token, 2);
                $token = trim($base);
                $step = (int) trim($stepPart);
                if ($step <= 0) {
                    return false;
                }
            }

            if ($token === '*') {
                for ($i = $min; $i <= $max; $i += $step) {
                    $values[$this->normalizeCronValue($i, $isDow)] = true;
                }
                continue;
            }

            if (str_contains($token, '-')) {
                [$startPart, $endPart] = explode('-', $token, 2);
                $start = (int) trim($startPart);
                $end = (int) trim($endPart);
                if ($start > $end) {
                    return false;
                }
                for ($i = $start; $i <= $end; $i += $step) {
                    if ($i < $min || $i > $max) {
                        return false;
                    }
                    $values[$this->normalizeCronValue($i, $isDow)] = true;
                }
                continue;
            }

            $single = (int) $token;
            if ($single < $min || $single > $max) {
                return false;
            }
            $values[$this->normalizeCronValue($single, $isDow)] = true;
        }

        return $values;
    }

    /**
     * Normalizes cron values.
     *
     * @param int  $value
     * @param bool $isDow
     * @return int
     */
    private function normalizeCronValue(int $value, bool $isDow): int
    {
        if ($isDow && $value === 7) {
            return 0;
        }
        return $value;
    }

    /**
     * Extracts system-role message content as a single string.
     *
     * @param array $messages
     * @return string
     */
    private function extractSystemText(array $messages): string
    {
        $chunks = [];
        foreach ($messages as $message) {
            if ((string) ($message['role'] ?? '') === 'system') {
                $text = trim((string) ($message['content'] ?? ''));
                if ($text !== '') {
                    $chunks[] = $text;
                }
            }
        }
        return implode("\n", $chunks);
    }

    /**
     * Extracts non-system message content as a single string.
     *
     * @param array $messages
     * @return string
     */
    private function extractNonSystemText(array $messages): string
    {
        $chunks = [];
        foreach ($messages as $message) {
            if ((string) ($message['role'] ?? '') !== 'system') {
                $text = trim((string) ($message['content'] ?? ''));
                if ($text !== '') {
                    $chunks[] = $text;
                }
            }
        }
        return $chunks ? implode("\n", $chunks) : 'Hello';
    }

    /**
     * Builds role-preserving messages array for Claude (system excluded, handled via top-level field).
     *
     * @param array $messages
     * @return array
     */
    private function buildRoleMessages(array $messages): array
    {
        $result = [];
        foreach ($messages as $message) {
            $role = (string) ($message['role'] ?? 'user');
            if ($role === 'system') {
                continue;
            }
            $text = trim((string) ($message['content'] ?? ''));
            if ($text !== '') {
                $result[] = ['role' => $role, 'content' => $text];
            }
        }
        return $result ?: [['role' => 'user', 'content' => 'Hello']];
    }

    /**
     * Builds OpenAI-compatible messages array with role preservation.
     *
     * @param array $messages
     * @return array
     */
    private function buildOpenAiMessages(array $messages): array
    {
        $result = [];
        foreach ($messages as $message) {
            $role = (string) ($message['role'] ?? 'user');
            $text = trim((string) ($message['content'] ?? ''));
            if ($text !== '') {
                $result[] = ['role' => $role, 'content' => $text];
            }
        }
        return $result ?: [['role' => 'user', 'content' => 'Hello']];
    }

    /**
     * Returns lock file path for a lock key.
     *
     * @param string $lockKey
     * @return string
     */
    private function getLockPath(string $lockKey): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $lockKey) ?: 'lock';
        return $this->lockDir . '/' . $safe . '.lock';
    }

    /**
     * Encodes value to JSON with safe fallback.
     *
     * @param mixed $value
     * @return string|null
     */
    private function encodeJson(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $encoded === false ? null : $encoded;
    }

    /**
     * Safe wrapper around Supervisor::logActivity().
     *
     * @param string $type
     * @param string $description
     * @param array  $data
     * @return void
     */
    private function safeSupervisorActivity(string $type, string $description, array $data = []): void
    {
        try {
            $this->supervisor->logActivity($type, $description, $data);
        } catch (Throwable $e) {
            error_log('[Automation] supervisor activity hook failed: ' . $e->getMessage());
        }
    }

    /**
     * Safe wrapper around Supervisor::logError().
     *
     * @param string $type
     * @param string $severity
     * @param string $message
     * @param array  $extra
     * @return void
     */
    private function safeSupervisorError(string $type, string $severity, string $message, array $extra = []): void
    {
        try {
            $this->supervisor->logError($type, $severity, $message, $extra);
        } catch (Throwable $e) {
            error_log('[Automation] supervisor error hook failed: ' . $e->getMessage());
        }
    }

    /**
     * Safe wrapper around Supervisor::sendTelegramAlert().
     *
     * @param string $message
     * @param string $level
     * @param string $context
     * @return void
     */
    private function safeSupervisorTelegram(string $message, string $level, string $context): void
    {
        try {
            $this->supervisor->sendTelegramAlert($message, $level);
        } catch (Throwable $e) {
            error_log('[Automation] telegram hook failed (' . $context . '): ' . $e->getMessage());
        }
    }
}
