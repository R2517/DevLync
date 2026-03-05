<?php
declare(strict_types=1);

/**
 * Automation Center Cron Runner
 *
 * Recommended cron:
 * * * * * php /path/to/devlync.com/cron/automation-runner.php >> /path/to/logs/devlync-automation.log 2>&1
 */

// Simulate web context for BASE_PATH detection in CLI.
$_SERVER['SCRIPT_NAME'] = '/devlync.com/cron/automation-runner.php';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/HttpClient.php';
require_once __DIR__ . '/../core/Cache.php';
require_once __DIR__ . '/../core/SocialMediaClient.php';

require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/CostRecord.php';
require_once __DIR__ . '/../models/Supervisor.php';
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Tag.php';
require_once __DIR__ . '/../models/AffiliateLink.php';
require_once __DIR__ . '/../models/ImageLibrary.php';
require_once __DIR__ . '/../models/KnowledgeItem.php';
require_once __DIR__ . '/../models/RoadmapItem.php';
require_once __DIR__ . '/../models/ScrapeLog.php';
require_once __DIR__ . '/../models/Automation.php';

require_once __DIR__ . '/../core/AutomationRunner.php';

$started = microtime(true);
echo '[' . date('Y-m-d H:i:s') . "] Automation runner started\n";

try {
    $runner = new AutomationRunner(new Automation());
    $result = $runner->runDueSchedules();

    $dueCount = (int) ($result['due_count'] ?? 0);
    $staleRemoved = (int) ($result['stale_locks_removed'] ?? 0);
    echo '[' . date('Y-m-d H:i:s') . "] Due schedules: {$dueCount}, stale locks removed: {$staleRemoved}\n";

    $results = $result['results'] ?? [];
    if (is_array($results)) {
        foreach ($results as $item) {
            $module = (string) ($item['module'] ?? 'unknown');
            $status = (string) ($item['status'] ?? 'unknown');
            $runId = (int) ($item['run_id'] ?? 0);
            echo "  - {$module}: {$status}" . ($runId > 0 ? " (run_id={$runId})" : '') . "\n";
        }
    }

    $elapsed = round(microtime(true) - $started, 2);
    echo '[' . date('Y-m-d H:i:s') . "] Automation runner completed in {$elapsed}s\n";
    exit(0);
} catch (Throwable $e) {
    $elapsed = round(microtime(true) - $started, 2);
    $message = '[Automation Cron] Failed after ' . $elapsed . 's: ' . $e->getMessage();
    error_log($message);
    try {
        (new Supervisor())->logError('api', 'critical', $message, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
        (new Supervisor())->sendTelegramAlert($message, 'critical');
    } catch (Throwable $hookError) {
        error_log('[Automation Cron] Supervisor hook failed: ' . $hookError->getMessage());
    }
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    exit(1);
}
