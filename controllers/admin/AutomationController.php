<?php
declare(strict_types=1);

/**
 * AutomationController
 * Admin pages for Automation Center.
 */
class AutomationController
{
    private Automation $automation;

    public function __construct()
    {
        Auth::requireAuth();
        $this->automation = new Automation();
    }

    /**
     * Dashboard page.
     *
     * @return void
     */
    public function dashboard(): void
    {
        $data = $this->automation->getDashboardData();
        $data['source_health'] = ScrapeLog::getStats();
        $data['knowledge_stats'] = KnowledgeItem::getStats();
        $data['roadmap_counts'] = RoadmapItem::countByStatus();
        $meta = ['title' => 'Automation Center'];

        ob_start();
        include VIEWS_PATH . '/admin/automation/dashboard.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /**
     * Run history + logs page.
     *
     * @return void
     */
    public function logs(): void
    {
        $module = trim((string) ($_GET['module'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $runId = (int) ($_GET['run_id'] ?? 0);

        $data = [
            'runs' => $this->automation->getRunHistory(100, $module !== '' ? $module : null, $status !== '' ? $status : null),
            'selected_run_id' => $runId,
            'selected_logs' => $runId > 0 ? $this->automation->getRunLogs($runId, 500) : [],
            'filters' => [
                'module' => $module,
                'status' => $status,
            ],
        ];

        $meta = ['title' => 'Automation Logs'];

        ob_start();
        include VIEWS_PATH . '/admin/automation/logs.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /**
     * AI providers page.
     *
     * @return void
     */
    public function providers(): void
    {
        $data = [
            'providers' => $this->automation->getProviders(false),
        ];
        $meta = ['title' => 'Automation AI Providers'];

        ob_start();
        include VIEWS_PATH . '/admin/automation/providers.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /**
     * Social platform page.
     *
     * @return void
     */
    public function social(): void
    {
        $data = [
            'platforms' => $this->automation->getPlatforms(),
        ];
        $meta = ['title' => 'Automation Social Platforms'];

        ob_start();
        include VIEWS_PATH . '/admin/automation/social.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /**
     * Knowledge base page.
     *
     * @return void
     */
    public function knowledge(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $sourceType = trim((string) ($_GET['source_type'] ?? ''));
        $sentiment = trim((string) ($_GET['sentiment'] ?? ''));
        $reviewed = ($_GET['reviewed'] ?? '') !== '' ? trim((string) $_GET['reviewed']) : null;
        $search = trim((string) ($_GET['q'] ?? ''));

        $result = KnowledgeItem::getFiltered(
            $page,
            25,
            $sourceType !== '' ? $sourceType : null,
            $sentiment !== '' ? $sentiment : null,
            $reviewed,
            $search !== '' ? $search : null
        );

        $data = [
            'items' => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $page,
            'stats' => KnowledgeItem::getStats(),
            'trends' => KnowledgeItem::detectTrends(48, 10),
            'filters' => [
                'source_type' => $sourceType,
                'sentiment' => $sentiment,
                'reviewed' => $reviewed,
                'q' => $search,
            ],
        ];

        $meta = ['title' => 'Knowledge Base'];

        ob_start();
        include VIEWS_PATH . '/admin/automation/knowledge.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /**
     * Scrape logs page.
     *
     * @return void
     */
    public function scrapeLogs(): void
    {
        $sourceType = trim((string) ($_GET['source_type'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $sessionId = trim((string) ($_GET['session_id'] ?? ''));

        $data = [
            'logs' => ScrapeLog::getRecent(200),
            'sessions' => ScrapeLog::getSessionSummaries(50),
            'selected_session_id' => $sessionId,
            'selected_session_logs' => $sessionId !== '' ? ScrapeLog::getBySession($sessionId) : [],
            'stats' => ScrapeLog::getStats(),
            'filters' => [
                'source_type' => $sourceType,
                'status' => $status,
            ],
        ];

        $meta = ['title' => 'Scrape Logs'];

        ob_start();
        include VIEWS_PATH . '/admin/automation/scrape-logs.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /**
     * Scraper configuration page.
     *
     * @return void
     */
    public function scraper(): void
    {
        $scraperKeys = [
            'scraper_keywords',
            'scraper_youtube_queries',
            'scraper_rss_feeds',
            'scraper_reddit_subreddits',
            'scraper_reddit_min_score',
            'scraper_time_window_hours',
            'scraper_youtube_max_results',
            'scraper_reddit_max_results',
            'scraper_source_youtube_enabled',
            'scraper_source_reddit_enabled',
            'scraper_source_rss_enabled',
            'scraper_auto_approve_threshold',
        ];

        $data = [
            'settings' => Setting::getMultiple($scraperKeys),
        ];

        $meta = ['title' => 'Scraper Configuration'];

        ob_start();
        include VIEWS_PATH . '/admin/automation/scraper.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /**
     * Competitors monitoring page.
     *
     * @return void
     */
    public function competitors(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $rssStatus = trim((string) ($_GET['rss_status'] ?? ''));
        $search = trim((string) ($_GET['q'] ?? ''));
        $tab = trim((string) ($_GET['tab'] ?? 'sites'));

        if ($tab === 'feeds') {
            $enabledOnly = ($_GET['enabled'] ?? '') !== '' ? (bool) (int) $_GET['enabled'] : null;
            $feedResult = CompetitorFeed::getFiltered($page, 50, $enabledOnly, $search !== '' ? $search : null);
            $data = [
                'tab' => 'feeds',
                'feeds' => $feedResult['items'],
                'total' => $feedResult['total'],
                'pages' => $feedResult['pages'],
                'page' => $page,
                'stats' => CompetitorSite::getStats(),
                'filters' => ['q' => $search, 'enabled' => $_GET['enabled'] ?? ''],
            ];
        } else {
            $result = CompetitorSite::getFiltered(
                $page, 50,
                $rssStatus !== '' ? $rssStatus : null,
                $search !== '' ? $search : null
            );
            $data = [
                'tab' => 'sites',
                'items' => $result['items'],
                'total' => $result['total'],
                'pages' => $result['pages'],
                'page' => $page,
                'stats' => CompetitorSite::getStats(),
                'filters' => ['rss_status' => $rssStatus, 'q' => $search],
            ];
        }

        $meta = ['title' => 'Competitor Monitoring'];

        ob_start();
        include VIEWS_PATH . '/admin/automation/competitors.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    /**
     * Automation settings page.
     *
     * @return void
     */
    public function settings(): void
    {
        $keys = [
            // AI selection
            'automation_primary_ai',
            'automation_fallback_ai',
            'automation_social_ai',
            // AI provider API keys
            'gemini_api_key',
            'claude_api_key',
            'openai_api_key',
            'deepseek_api_key',
            'grok_api_key',
            'openrouter_api_key',
            'fal_api_key',
            // Social: Twitter/X
            'twitter_bearer_token',
            'twitter_api_key',
            'twitter_api_secret',
            'twitter_access_token',
            'twitter_access_secret',
            // Social: LinkedIn
            'linkedin_access_token',
            'linkedin_person_id',
            // Social: Facebook
            'facebook_page_id',
            'facebook_page_token',
            // Social: Instagram
            'instagram_business_id',
            'instagram_access_token',
            // Social: Pinterest
            'pinterest_access_token',
            'pinterest_board_id',
            // Social: YouTube
            'youtube_api_key',
            'youtube_channel_id',
            'youtube_oauth_token',
            // Social: Threads
            'threads_access_token',
            'threads_user_id',
            // Social: Bluesky
            'bluesky_handle',
            'bluesky_app_password',
            // Social: Reddit
            'reddit_client_id',
            'reddit_client_secret',
            'reddit_username',
            'reddit_password',
            // Telegram
            'telegram_bot_token',
            'telegram_chat_id',
            // Indexing
            'google_indexing_service_key',
            'indexnow_key',
        ];

        $data = [
            'settings' => Setting::getMultiple($keys),
        ];
        $meta = ['title' => 'Automation Settings'];

        ob_start();
        include VIEWS_PATH . '/admin/automation/settings.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }
}

