<?php
declare(strict_types=1);

/**
 * Supervisor Health Cron Job
 * Runs automated health scans on all registered pages.
 * 
 * Schedule via cron (every 15 minutes):
 *   php /path/to/devlync.com/cron/supervisor-health.php
 * 
 * Or via Windows Task Scheduler:
 *   C:\xampp\php\php.exe C:\xampp\htdocs\devlync.com\cron\supervisor-health.php
 */

// Simulate web context for BASE_PATH detection
$_SERVER['SCRIPT_NAME'] = '/devlync.com/cron/supervisor-health.php';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Supervisor.php';

$startTime = microtime(true);
$supervisor = new Supervisor();

// Check if auto mode is enabled
$settings = Database::getInstance()->query("SELECT setting_key, setting_value FROM supervisor_settings WHERE setting_key IN ('auto_mode', 'scan_interval_minutes')");
$config = [];
foreach ($settings as $row) {
    $config[$row['setting_key']] = $row['setting_value'];
}

if (($config['auto_mode'] ?? 'false') !== 'true') {
    echo "[" . date('Y-m-d H:i:s') . "] Auto mode is OFF. Skipping cron scan.\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting automated health scan...\n";

try {
    $results = $supervisor->runFullHealthScan();
    $duration = round(microtime(true) - $startTime, 2);

    echo "[" . date('Y-m-d H:i:s') . "] Health scan complete in {$duration}s\n";
    echo "  Total: {$results['total']}, Passed: {$results['passed']}, Failed: {$results['failed']}, Warnings: {$results['warnings']}\n";

    // Log the cron execution
    $supervisor->logActivity('cron_health_scan', "Automated health scan: {$results['passed']}/{$results['total']} pages healthy ({$duration}s)", ['triggered_by' => 'cron']);

    // If there are failures, create an incident (if not already open)
    if ($results['failed'] > 0) {
        $openIncident = Database::getInstance()->queryOne(
            "SELECT id FROM supervisor_incidents WHERE status IN ('active','investigating') AND title LIKE '%pages down%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );

        if (!$openIncident) {
            Database::getInstance()->execute(
                "INSERT INTO supervisor_incidents (title, description, severity, status) VALUES (?, ?, ?, 'active')",
                [
                    "{$results['failed']} pages down",
                    "Automated health scan detected {$results['failed']} failing pages out of {$results['total']}.",
                    $results['failed'] > 5 ? 'critical' : 'warning',
                ]
            );
            echo "  ⚠️ Incident created: {$results['failed']} pages down\n";
        }
    }

} catch (Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    error_log("Supervisor cron error: " . $e->getMessage());
    exit(1);
}

exit(0);
