<?php
declare(strict_types=1);

/**
 * SupervisorController
 * Admin-only controller for the AI Supervisor dashboard and sub-pages.
 */
class SupervisorController
{
    private Supervisor $supervisor;

    public function __construct()
    {
        Auth::requireAuth();
        $this->supervisor = new Supervisor();
    }

    /** Main dashboard page */
    public function index(): void
    {
        $data = [
            'stats' => $this->supervisor->getDashboardStats(),
            'page_statuses' => $this->supervisor->getPageStatuses(),
            'page_summary' => $this->supervisor->getPageStatusSummary(),
            'recent_errors' => $this->supervisor->getErrors('new', 'all', 10),
            'suggestions' => $this->supervisor->getSuggestions('pending', 'all', 10),
            'recent_scans' => $this->supervisor->getRecentScans(5),
            'latest_report' => $this->supervisor->getLatestReport(),
            'activity_log' => $this->supervisor->getActivityLog(15),
            'active_incidents' => $this->supervisor->getActiveIncidents(),
            'settings' => $this->supervisor->getAllSettings(),
            'website_score' => $this->supervisor->calculateWebsiteScore(),
        ];

        $meta = ['title' => 'AI Supervisor'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/supervisor/dashboard.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /** Errors management page */
    public function errors(): void
    {
        $status = $_GET['status'] ?? 'all';
        $severity = $_GET['severity'] ?? 'all';

        $data = [
            'errors' => $this->supervisor->getErrors($status, $severity, 100),
            'filter_status' => $status,
            'filter_severity' => $severity,
        ];

        $meta = ['title' => 'Supervisor — Errors'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/supervisor/errors.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /** Suggestions management page */
    public function suggestions(): void
    {
        $status = $_GET['status'] ?? 'pending';
        $category = $_GET['category'] ?? 'all';

        $data = [
            'suggestions' => $this->supervisor->getSuggestions($status, $category, 100),
            'filter_status' => $status,
            'filter_category' => $category,
        ];

        $meta = ['title' => 'Supervisor — Suggestions'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/supervisor/suggestions.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /** Reports page */
    public function reports(): void
    {
        $data = [
            'reports' => $this->supervisor->getReports(30),
        ];

        $meta = ['title' => 'Supervisor — Reports'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/supervisor/reports.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /** Activity log page */
    public function activity(): void
    {
        $data = [
            'activities' => $this->supervisor->getActivityLog(100),
        ];

        $meta = ['title' => 'Supervisor — Activity Log'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/supervisor/activity.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /** Supervisor settings page (includes 3 API key sections) */
    public function settings(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Process all sv_ prefixed fields
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'sv_') === 0) {
                    $settingKey = substr($key, 3);
                    $this->supervisor->updateSetting($settingKey, $value);
                }
            }

            // Handle checkbox toggles (unchecked checkboxes don't submit)
            $toggleKeys = ['claude_active', 'gemini_active', 'chatgpt_active', 'ai_analysis_enabled', 'auto_mode', 'telegram_alerts_enabled'];
            foreach ($toggleKeys as $tk) {
                if (!isset($_POST['sv_' . $tk])) {
                    $this->supervisor->updateSetting($tk, 'false');
                }
            }

            header('Location: ' . url('/admin/supervisor/settings?saved=1'));
            exit;
        }

        $data = [
            'settings' => $this->supervisor->getAllSettings(),
            'saved' => isset($_GET['saved']),
        ];

        $meta = ['title' => 'Supervisor — Settings'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/supervisor/settings.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }
}
