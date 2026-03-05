<?php
declare(strict_types=1);

/**
 * Supervisor API Endpoints
 * AJAX API for supervisor dashboard interactions.
 * Requires admin session auth.
 */

// Bootstrap — reuse the main app's bootstrap
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/Supervisor.php';
require_once __DIR__ . '/../services/AiAnalyzer.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Require admin auth
if (!session_id()) {
    session_start();
}
if (!Auth::isLoggedIn()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$supervisor = new Supervisor();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // Run full health scan
    case 'run_health_scan':
        $results = $supervisor->runFullHealthScan();
        echo json_encode(['success' => true, 'data' => $results]);
        break;

    // Run single URL check
    case 'check_url':
        $url = $_POST['url'] ?? '';
        $name = $_POST['name'] ?? '';
        if (empty($url)) {
            echo json_encode(['error' => 'URL required']);
            break;
        }
        $result = $supervisor->runHealthCheck($url, $name);
        echo json_encode(['success' => true, 'data' => $result]);
        break;

    // Get dashboard data (AJAX refresh)
    case 'dashboard_data':
        $data = [
            'stats' => $supervisor->getDashboardStats(),
            'page_statuses' => $supervisor->getPageStatuses(),
            'recent_errors' => $supervisor->getErrors('new', 'all', 10),
            'suggestions' => $supervisor->getSuggestions('pending', 'all', 10),
            'activity_log' => $supervisor->getActivityLog(15),
            'active_incidents' => $supervisor->getActiveIncidents(),
            'website_score' => $supervisor->calculateWebsiteScore(),
        ];
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // Update error status
    case 'update_error':
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id && $status) {
            $supervisor->updateErrorStatus($id, $status);
            $supervisor->logActivity('error_update', "Error #{$id} marked as {$status}");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'ID and status required']);
        }
        break;

    // Update suggestion status
    case 'update_suggestion':
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id && $status) {
            $supervisor->updateSuggestionStatus($id, $status);
            $supervisor->logActivity('suggestion_update', "Suggestion #{$id} marked as {$status}");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'ID and status required']);
        }
        break;

    // Parse PHP error log
    case 'parse_error_log':
        $errors = $supervisor->parsePhpErrorLog();
        echo json_encode(['success' => true, 'data' => $errors]);
        break;

    // Get activity log
    case 'activity_log':
        $limit = (int) ($_GET['limit'] ?? 50);
        $log = $supervisor->getActivityLog($limit);
        echo json_encode(['success' => true, 'data' => $log]);
        break;

    // Update supervisor setting
    case 'update_setting':
        $key = $_POST['key'] ?? '';
        $value = $_POST['value'] ?? '';
        if ($key) {
            $supervisor->updateSetting($key, $value);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Key required']);
        }
        break;

    // ── Phase 2: SEO Scan ──
    case 'run_seo_scan':
        set_time_limit(120);
        $results = $supervisor->runSeoScan();
        echo json_encode(['success' => true, 'data' => $results]);
        break;

    // ── Phase 2: Performance Scan ──
    case 'run_performance_scan':
        set_time_limit(120);
        $results = $supervisor->runPerformanceScan();
        echo json_encode(['success' => true, 'data' => $results]);
        break;

    // ── Phase 2: Link Check ──
    case 'run_link_check':
        set_time_limit(300);
        $results = $supervisor->runLinkCheck();
        echo json_encode(['success' => true, 'data' => $results]);
        break;

    // ── Phase 2: Image Audit ──
    case 'run_image_audit':
        set_time_limit(120);
        $results = $supervisor->runImageAudit();
        echo json_encode(['success' => true, 'data' => $results]);
        break;
    // ── Phase 3: AI Analyze Error ──
    case 'ai_analyze_error':
        set_time_limit(60);
        $errorId = (int) ($_POST['error_id'] ?? 0);
        if (!$errorId) {
            echo json_encode(['error' => 'Error ID required']);
            break;
        }
        $error = $supervisor->getErrorById($errorId);
        if (!$error) {
            echo json_encode(['error' => 'Error not found']);
            break;
        }
        $ai = new AiAnalyzer();
        $result = $ai->analyzeError($error);
        echo json_encode($result);
        break;

    // ── Phase 3: AI Analyze Scan Results ──
    case 'ai_analyze_scan':
        set_time_limit(60);
        $rawData = file_get_contents('php://input');
        $postData = json_decode($rawData, true);
        if (!$postData) {
            $postData = $_POST;
        }
        $scanType = $postData['scan_type'] ?? '';
        $scanData = $postData['scan_data'] ?? [];
        if (empty($scanType) || empty($scanData)) {
            echo json_encode(['error' => 'Scan type and data required']);
            break;
        }
        $ai = new AiAnalyzer();
        $result = $ai->analyzeScanResults($scanType, $scanData);
        echo json_encode($result);
        break;

    // ── Phase 3: AI Test Connection ──
    case 'ai_test_connection':
        set_time_limit(30);
        $ai = new AiAnalyzer();
        $results = $ai->testConnections();
        echo json_encode(['success' => true, 'data' => $results, 'active_provider' => $ai->getActiveProvider()]);
        break;

    // ── Phase 3: Weekly Report ──
    case 'generate_weekly_report':
        set_time_limit(60);
        $results = $supervisor->generateWeeklyReport();
        echo json_encode(['success' => true, 'data' => $results]);
        break;

    // ── Phase 4: Full Audit ──
    case 'run_full_audit':
        set_time_limit(600);
        $results = $supervisor->runFullAudit();
        echo json_encode(['success' => true, 'data' => $results]);
        break;

    // ── Phase 4: Telegram Test ──
    case 'test_telegram':
        $sent = $supervisor->sendTelegramAlert('Test alert from DevLync Supervisor 🧪', 'info');
        echo json_encode(['success' => $sent, 'message' => $sent ? 'Telegram message sent!' : 'Failed — check bot token and chat ID in Settings']);
        break;

    // ── Phase 5: Security Scan ──
    case 'run_security_scan':
        set_time_limit(120);
        $results = $supervisor->runSecurityScan();
        echo json_encode(['success' => true, 'data' => $results]);
        break;

    // ── Phase 5: Content Quality Check ──
    case 'run_content_quality':
        set_time_limit(120);
        $results = $supervisor->runContentQualityCheck();
        echo json_encode(['success' => true, 'data' => $results]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
        break;
}
