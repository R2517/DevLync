<?php
declare(strict_types=1);

/**
 * Automation API Endpoints
 * Action-based JSON API for the Automation Center.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ApiAuth.php';
require_once __DIR__ . '/../core/HttpClient.php';

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

if (!session_id()) {
    session_start();
}

header('Content-Type: application/json');

/**
 * Sends JSON response and exits.
 *
 * @param int   $status
 * @param array $payload
 * @return never
 */
function automationApiRespond(int $status, array $payload): never
{
    http_response_code($status);
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    if ($json === false) {
        $json = '{"success":false,"error":"Response encoding failed"}';
    }
    echo $json;
    exit;
}

/**
 * Returns request payload from JSON body + POST vars.
 *
 * @return array
 */
function automationApiPayload(): array
{
    $raw = file_get_contents('php://input');
    $json = [];
    if (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $json = $decoded;
        }
    }
    return array_merge($json, $_POST);
}

/**
 * Ensures a CSRF token exists in session and returns it.
 *
 * @return string
 */
function automationApiSessionCsrfToken(): string
{
    if (empty($_SESSION['automation_csrf_token'])) {
        $_SESSION['automation_csrf_token'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['automation_csrf_token'];
}

/**
 * Verifies CSRF token for mutating admin actions.
 *
 * @param array $payload
 * @return bool
 */
function automationApiVerifyCsrf(array $payload): bool
{
    $sessionToken = automationApiSessionCsrfToken();
    $headerToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $bodyToken = (string) ($payload['csrf_token'] ?? '');
    $provided = $headerToken !== '' ? $headerToken : $bodyToken;
    return $provided !== '' && hash_equals($sessionToken, $provided);
}

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');
$payload = automationApiPayload();
if ($action === '' && isset($payload['action'])) {
    $action = (string) $payload['action'];
}

$adminSession = Auth::isLoggedIn();
$apiKeyValid = ApiAuth::isValid();

if (!$adminSession && !$apiKeyValid) {
    automationApiRespond(403, ['success' => false, 'error' => 'Unauthorized']);
}

$mutableActions = ['run_module', 'process_social_queue', 'update_schedule', 'update_provider', 'update_platform', 'update_setting'];
$adminOnlyActions = ['update_schedule', 'update_provider', 'update_platform', 'update_setting'];

if (in_array($action, $adminOnlyActions, true) && !$adminSession) {
    automationApiRespond(403, ['success' => false, 'error' => 'Admin session required for this action']);
}

if (in_array($action, $mutableActions, true) && $adminSession && !automationApiVerifyCsrf($payload)) {
    automationApiRespond(419, ['success' => false, 'error' => 'CSRF token invalid or missing']);
}

$automation = new Automation();
$runner = new AutomationRunner($automation);

try {
    switch ($action) {
        case 'csrf_token':
            automationApiRespond(200, [
                'success' => true,
                'csrf_token' => automationApiSessionCsrfToken(),
            ]);
            break;

        case 'dashboard_data':
            automationApiRespond(200, ['success' => true, 'data' => $automation->getDashboardData()]);
            break;

        case 'run_history':
            $limit = max(1, (int) ($_GET['limit'] ?? $payload['limit'] ?? 50));
            $module = (string) ($_GET['module'] ?? $payload['module'] ?? '');
            $status = (string) ($_GET['status'] ?? $payload['status'] ?? '');
            automationApiRespond(200, [
                'success' => true,
                'data' => $automation->getRunHistory($limit, $module !== '' ? $module : null, $status !== '' ? $status : null),
            ]);
            break;

        case 'run_logs':
            $runId = (int) ($_GET['run_id'] ?? $payload['run_id'] ?? 0);
            if ($runId <= 0) {
                automationApiRespond(422, ['success' => false, 'error' => 'run_id is required']);
            }
            $limit = max(1, (int) ($_GET['limit'] ?? $payload['limit'] ?? 500));
            automationApiRespond(200, [
                'success' => true,
                'data' => $automation->getRunLogs($runId, $limit),
            ]);
            break;

        case 'get_schedules':
            automationApiRespond(200, ['success' => true, 'data' => $automation->getSchedules()]);
            break;

        case 'get_providers':
            automationApiRespond(200, ['success' => true, 'data' => $automation->getProviders(false)]);
            break;

        case 'get_platforms':
            automationApiRespond(200, ['success' => true, 'data' => $automation->getPlatforms()]);
            break;

        case 'run_module':
            $module = trim((string) ($payload['module'] ?? ''));
            if ($module === '') {
                automationApiRespond(422, ['success' => false, 'error' => 'module is required']);
            }
            automationApiRespond(200, [
                'success' => true,
                'data' => $runner->runModule($module, 'manual', $adminSession ? 'admin' : 'api_key'),
            ]);
            break;

        case 'process_social_queue':
            automationApiRespond(200, [
                'success' => true,
                'data' => $runner->runModule('social', 'manual', $adminSession ? 'admin' : 'api_key'),
            ]);
            break;

        case 'update_schedule':
            $module = trim((string) ($payload['module'] ?? ''));
            if ($module === '') {
                automationApiRespond(422, ['success' => false, 'error' => 'module is required']);
            }
            $ok = $automation->updateSchedule($module, $payload);
            automationApiRespond(200, ['success' => $ok]);
            break;

        case 'update_provider':
            $id = (int) ($payload['id'] ?? 0);
            if ($id <= 0) {
                automationApiRespond(422, ['success' => false, 'error' => 'id is required']);
            }
            $ok = $automation->updateProvider($id, $payload);
            automationApiRespond(200, ['success' => $ok]);
            break;

        case 'test_provider':
            $id = (int) ($payload['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) {
                automationApiRespond(422, ['success' => false, 'error' => 'id is required']);
            }
            automationApiRespond(200, ['success' => true, 'data' => $automation->testProvider($id)]);
            break;

        case 'update_platform':
            $platform = trim((string) ($payload['platform'] ?? ''));
            if ($platform === '') {
                automationApiRespond(422, ['success' => false, 'error' => 'platform is required']);
            }
            $ok = $automation->updatePlatform($platform, $payload);
            automationApiRespond(200, ['success' => $ok]);
            break;

        case 'test_platform':
            $platform = trim((string) ($payload['platform'] ?? $_GET['platform'] ?? ''));
            if ($platform === '') {
                automationApiRespond(422, ['success' => false, 'error' => 'platform is required']);
            }
            automationApiRespond(200, ['success' => true, 'data' => $automation->testPlatform($platform)]);
            break;

        case 'get_defaults':
            $type = trim((string) ($_GET['type'] ?? ''));
            $defaults = [
                'keywords' => AutomationRunner::getDefaultKeywords(),
                'ytQueries' => AutomationRunner::getDefaultYoutubeQueries(),
                'subreddits' => AutomationRunner::getDefaultSubreddits(),
            ];
            if (!isset($defaults[$type])) {
                automationApiRespond(422, ['success' => false, 'error' => 'Invalid type']);
            }
            automationApiRespond(200, ['success' => true, 'data' => $defaults[$type]]);
            break;

        case 'update_setting':
            $key = trim((string) ($payload['key'] ?? ''));
            $value = (string) ($payload['value'] ?? '');
            if ($key === '') {
                automationApiRespond(422, ['success' => false, 'error' => 'key is required']);
            }
            $allowedKeys = [
                'automation_primary_ai', 'automation_fallback_ai', 'automation_social_ai',
                'gemini_api_key', 'claude_api_key', 'openai_api_key', 'deepseek_api_key',
                'grok_api_key', 'openrouter_api_key',
                'image_provider', 'image_style', 'image_quality',
                'image_generation_enabled', 'image_convert_webp',
                'youtube_api_key', 'reddit_client_id', 'reddit_client_secret',
                'reddit_username', 'reddit_password',
                'twitter_bearer_token', 'twitter_api_key', 'twitter_api_secret',
                'twitter_access_token', 'twitter_access_secret',
                'linkedin_access_token', 'linkedin_person_id',
                'facebook_page_id', 'facebook_page_token',
                'instagram_business_id', 'instagram_access_token',
                'pinterest_access_token', 'pinterest_board_id',
                'youtube_channel_id', 'youtube_oauth_token',
                'threads_access_token', 'threads_user_id',
                'bluesky_handle', 'bluesky_app_password',
                'telegram_bot_token', 'telegram_chat_id',
                'google_indexing_service_key', 'indexnow_key',
                // Scraper config keys
                'scraper_keywords', 'scraper_youtube_queries', 'scraper_rss_feeds',
                'scraper_reddit_subreddits', 'scraper_reddit_min_score',
                'scraper_time_window_hours', 'scraper_youtube_max_results',
                'scraper_reddit_max_results', 'scraper_source_youtube_enabled',
                'scraper_source_reddit_enabled', 'scraper_source_rss_enabled',
                'scraper_auto_approve_threshold',
            ];
            if (!in_array($key, $allowedKeys, true)) {
                automationApiRespond(403, ['success' => false, 'error' => 'Setting key not allowed via automation API']);
            }
            $automation->updateSetting($key, $value);
            automationApiRespond(200, ['success' => true]);
            break;

        case 'roadmap_approve':
            $ids = $payload['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                automationApiRespond(422, ['success' => false, 'error' => 'ids array is required']);
            }
            RoadmapItem::bulkApprove($ids);
            automationApiRespond(200, ['success' => true, 'approved' => count($ids)]);
            break;

        case 'roadmap_reject':
            $ids = $payload['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                automationApiRespond(422, ['success' => false, 'error' => 'ids array is required']);
            }
            RoadmapItem::bulkReject($ids);
            automationApiRespond(200, ['success' => true, 'rejected' => count($ids)]);
            break;

        case 'knowledge_delete':
            $ids = $payload['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                automationApiRespond(422, ['success' => false, 'error' => 'ids array is required']);
            }
            $deleted = KnowledgeItem::bulkDelete($ids);
            automationApiRespond(200, ['success' => true, 'deleted' => $deleted]);
            break;

        case 'knowledge_review':
            $ids = $payload['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                automationApiRespond(422, ['success' => false, 'error' => 'ids array is required']);
            }
            KnowledgeItem::bulkMarkReviewed($ids);
            automationApiRespond(200, ['success' => true, 'reviewed' => count($ids)]);
            break;

        case 'competitor_import_csv':
            $csvPath = ROOT_PATH . '/marktechpost.com-competitors-5035rows.csv';
            if (!file_exists($csvPath)) {
                automationApiRespond(404, ['success' => false, 'error' => 'CSV file not found']);
            }
            $importResult = CompetitorSite::importFromCsv($csvPath);
            automationApiRespond(200, ['success' => true, 'data' => $importResult]);
            break;

        case 'competitor_discover_feeds':
            $batchSize = (int) ($payload['batch_size'] ?? 50);
            $batchSize = max(10, min(200, $batchSize));
            $runner = new AutomationRunner();
            $runId = (new Automation())->createRun('scraper', 'manual');
            $discoveryResult = $runner->discoverCompetitorFeeds($runId, $batchSize);
            automationApiRespond(200, ['success' => true, 'data' => $discoveryResult]);
            break;

        case 'competitor_feed_toggle':
            $feedId = (int) ($payload['feed_id'] ?? 0);
            $enabled = (bool) ($payload['enabled'] ?? false);
            if ($feedId < 1) {
                automationApiRespond(422, ['success' => false, 'error' => 'feed_id is required']);
            }
            CompetitorFeed::setEnabled($feedId, $enabled);
            automationApiRespond(200, ['success' => true, 'feed_id' => $feedId, 'enabled' => $enabled]);
            break;

        case 'competitor_feed_bulk_enable':
            $ids = $payload['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                automationApiRespond(422, ['success' => false, 'error' => 'ids array is required']);
            }
            CompetitorFeed::bulkEnable($ids);
            automationApiRespond(200, ['success' => true, 'enabled' => count($ids)]);
            break;

        case 'competitor_feed_bulk_disable':
            $ids = $payload['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                automationApiRespond(422, ['success' => false, 'error' => 'ids array is required']);
            }
            CompetitorFeed::bulkDisable($ids);
            automationApiRespond(200, ['success' => true, 'disabled' => count($ids)]);
            break;

        case 'competitor_reset_pending':
            $resetCount = CompetitorSite::resetAllToPending();
            automationApiRespond(200, ['success' => true, 'reset' => $resetCount]);
            break;

        default:
            automationApiRespond(404, ['success' => false, 'error' => 'Unknown action']);
    }
} catch (Throwable $e) {
    $message = '[Automation API] action=' . ($action !== '' ? $action : 'unknown') . ' failed: ' . $e->getMessage();
    error_log($message);
    try {
        (new Supervisor())->logError('api', 'critical', $message, [
            'action' => $action,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    } catch (Throwable $hookError) {
        error_log('[Automation API] Supervisor hook failed: ' . $hookError->getMessage());
    }
    automationApiRespond(500, ['success' => false, 'error' => 'Internal server error']);
}
