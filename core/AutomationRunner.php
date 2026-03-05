<?php
declare(strict_types=1);

if (!class_exists('SocialMediaClient') && defined('ROOT_PATH')) {
    $socialClientFile = ROOT_PATH . '/core/SocialMediaClient.php';
    if (is_file($socialClientFile)) {
        require_once $socialClientFile;
    }
}

/**
 * AutomationRunner
 * Executes scheduled and manual automation modules.
 */
class AutomationRunner
{
    private Automation $automation;
    private const DEFAULT_SCRAPER_KEYWORDS = [
        // Core developer tools
        'tool', 'ide', 'editor', 'framework', 'library', 'sdk', 'api', 'cli', 'plugin', 'extension',
        // AI & coding assistants
        'ai', 'copilot', 'cursor', 'windsurf', 'codeium', 'tabnine', 'chatgpt', 'claude', 'gemini',
        'llm', 'machine learning', 'deep learning', 'generative ai', 'ai agent', 'prompt engineering',
        // Editors & IDEs
        'vscode', 'neovim', 'jetbrains', 'intellij', 'webstorm', 'pycharm', 'sublime', 'zed editor',
        // Cloud & DevOps
        'cloud', 'deploy', 'hosting', 'aws', 'azure', 'gcp', 'vercel', 'netlify', 'railway', 'render',
        'docker', 'kubernetes', 'terraform', 'ansible', 'ci/cd', 'github actions', 'jenkins',
        'serverless', 'microservice', 'container', 'devops',
        // Databases
        'database', 'postgresql', 'mysql', 'mongodb', 'redis', 'supabase', 'firebase', 'planetscale',
        'sqlite', 'prisma', 'drizzle', 'orm',
        // Frontend
        'react', 'nextjs', 'vue', 'nuxt', 'svelte', 'angular', 'tailwind', 'typescript', 'javascript',
        'astro', 'remix', 'vite', 'webpack', 'css framework', 'component library', 'ui kit',
        // Backend
        'node', 'express', 'fastapi', 'django', 'laravel', 'spring boot', 'golang', 'rust',
        'python', 'php', 'ruby', 'elixir',
        // Testing & Quality
        'testing', 'playwright', 'cypress', 'jest', 'vitest', 'selenium', 'unit test', 'e2e test',
        'code review', 'linter', 'formatter', 'eslint', 'prettier',
        // Security
        'security', 'authentication', 'oauth', 'jwt', 'encryption', 'vulnerability', 'penetration test',
        // Productivity & Workflow
        'productivity', 'automation', 'workflow', 'no-code', 'low-code', 'saas', 'open source',
        'self-hosted', 'boilerplate', 'starter kit', 'template',
        // Content intent
        'review', 'comparison', 'alternative', 'best', 'recommend', 'tutorial', 'guide',
        'benchmark', 'performance', 'pricing', 'free', 'open-source', 'new release', 'launch',
        // Monitoring & Observability
        'monitoring', 'logging', 'observability', 'apm', 'sentry', 'datadog', 'grafana',
        // Version control
        'git', 'github', 'gitlab', 'bitbucket', 'version control', 'monorepo',
    ];
    private const DEFAULT_YOUTUBE_QUERIES = [
        // AI coding tools
        'AI coding assistant review 2026', 'cursor ide vs copilot', 'best AI code editor',
        'windsurf editor review', 'codeium review', 'tabnine vs copilot',
        'chatgpt coding tutorial', 'claude ai for developers', 'ai pair programming',
        // IDEs & Editors
        'best IDE 2026', 'vscode tips and tricks', 'neovim setup 2026', 'zed editor review',
        'jetbrains fleet review', 'best code editor for beginners',
        // DevOps & Cloud
        'devops tools 2026', 'docker tutorial', 'kubernetes explained',
        'github actions ci cd', 'vercel vs netlify vs railway', 'best cloud hosting developer',
        // Web Development
        'nextjs 15 tutorial', 'react vs vue vs svelte', 'tailwind css tips',
        'fullstack project tutorial', 'best javascript framework 2026',
        // Backend & Database
        'best backend framework 2026', 'supabase vs firebase', 'postgresql tips',
        'node.js best practices', 'fastapi python tutorial', 'golang web development',
        // General Dev
        'developer productivity tips', 'best developer tools', 'open source tools developer',
        'self hosted alternatives', 'best saas tools developers', 'programming tools review',
        'software engineering best practices', 'tech stack 2026',
    ];
    private const DEFAULT_RSS_FEEDS = [
        ['name' => 'Dev.to', 'url' => 'https://dev.to/feed', 'source_type' => 'devto', 'is_enabled' => true],
        ['name' => 'Hacker News', 'url' => 'https://hnrss.org/newest?points=50', 'source_type' => 'hackernews', 'is_enabled' => true],
        ['name' => 'TechCrunch', 'url' => 'https://techcrunch.com/feed/', 'source_type' => 'rss', 'is_enabled' => true],
        ['name' => 'Product Hunt', 'url' => 'https://www.producthunt.com/feed', 'source_type' => 'producthunt', 'is_enabled' => true],
        ['name' => 'GitHub Blog', 'url' => 'https://github.blog/feed/', 'source_type' => 'rss', 'is_enabled' => true],
    ];
    private const DEFAULT_REDDIT_SUBREDDITS = [
        'webdev', 'javascript', 'programming', 'devops', 'node', 'reactjs', 'selfhosted', 'SaaS',
    ];

    public function __construct(?Automation $automation = null)
    {
        $this->automation = $automation ?? new Automation();
    }

    public static function getDefaultKeywords(): array
    {
        return self::DEFAULT_SCRAPER_KEYWORDS;
    }

    public static function getDefaultYoutubeQueries(): array
    {
        return self::DEFAULT_YOUTUBE_QUERIES;
    }

    public static function getDefaultSubreddits(): array
    {
        return self::DEFAULT_REDDIT_SUBREDDITS;
    }

    /**
     * Returns scraper keywords from DB or defaults.
     *
     * @return array
     */
    private function getScraperKeywords(): array
    {
        $val = Setting::getJson('scraper_keywords');
        return is_array($val) && $val ? $val : self::DEFAULT_SCRAPER_KEYWORDS;
    }

    /**
     * Returns YouTube queries from DB or defaults.
     *
     * @return array
     */
    private function getYoutubeQueries(): array
    {
        $val = Setting::getJson('scraper_youtube_queries');
        return is_array($val) && $val ? $val : self::DEFAULT_YOUTUBE_QUERIES;
    }

    /**
     * Returns RSS feeds from DB or defaults.
     *
     * @return array
     */
    private function getRssFeeds(): array
    {
        $val = Setting::getJson('scraper_rss_feeds');
        return is_array($val) && $val ? $val : self::DEFAULT_RSS_FEEDS;
    }

    /**
     * Returns Reddit subreddits from DB or defaults.
     *
     * @return array
     */
    private function getRedditSubreddits(): array
    {
        $val = Setting::getJson('scraper_reddit_subreddits');
        return is_array($val) && $val ? $val : self::DEFAULT_REDDIT_SUBREDDITS;
    }

    /**
     * Checks if a scraper source is enabled.
     *
     * @param string $source youtube|reddit|rss
     * @return bool
     */
    private function isSourceEnabled(string $source): bool
    {
        return (Setting::get('scraper_source_' . $source . '_enabled') ?? '1') === '1';
    }

    /**
     * Returns a numeric scraper setting or its default.
     *
     * @param string $key
     * @param int    $default
     * @return int
     */
    private function getScraperSetting(string $key, int $default): int
    {
        $val = Setting::get($key);
        return $val !== null && $val !== '' ? (int) $val : $default;
    }

    /**
     * Runs all due schedules.
     *
     * @return array
     */
    public function runDueSchedules(): array
    {
        $this->automation->ensureRuntimeDirectories();
        $removed = $this->automation->cleanupStaleLocks();
        $due = $this->automation->getDueSchedules();
        $results = [];

        foreach ($due as $schedule) {
            $module = (string) $schedule['module'];
            $results[] = $this->runModule($module, 'cron', 'system');
        }

        return [
            'success' => true,
            'stale_locks_removed' => $removed,
            'due_count' => count($due),
            'results' => $results,
        ];
    }

    /**
     * Runs one module with lock, lifecycle, and error handling.
     *
     * @param string $module
     * @param string $triggerType
     * @param string $triggeredBy
     * @return array
     */
    public function runModule(string $module, string $triggerType = 'manual', string $triggeredBy = 'system'): array
    {
        $this->automation->ensureRuntimeDirectories();

        $schedule = $this->automation->getSchedule($module);
        $lockTimeout = (int) ($schedule['lock_timeout_seconds'] ?? 900);
        $timeout = (int) ($schedule['timeout_seconds'] ?? 600);
        $lockKey = 'automation_' . $module;
        $startedAt = microtime(true);

        if (!$this->automation->acquireLock($lockKey, $lockTimeout)) {
            $this->automation->log(null, $module, 'warning', 'lock_skip', 'Module skipped due to active lock', [
                'lock_key' => $lockKey,
            ]);
            $this->automation->recordLockSkip($module, $lockKey, $triggerType, $triggeredBy);

            return [
                'success' => true,
                'module' => $module,
                'status' => 'skipped',
                'reason' => 'locked',
            ];
        }

        $runId = $this->automation->createRun($module, $triggerType, $triggeredBy, $lockKey);
        $success = false;

        try {
            $this->automation->log($runId, $module, 'info', 'dispatch', 'Dispatching module', [
                'timeout_seconds' => $timeout,
            ]);

            $result = $this->dispatchModule($module, $runId, $timeout);
            $status = (string) ($result['status'] ?? 'completed');

            if ($status === 'failed') {
                throw new RuntimeException((string) ($result['error'] ?? 'Module failed'));
            }
            if ($status === 'skipped') {
                $this->automation->skipRun($runId, (string) ($result['reason'] ?? 'Skipped'), $result);
            } else {
                $this->automation->completeRun($runId, $result);
                $success = true;
            }

            return [
                'success' => true,
                'run_id' => $runId,
                'module' => $module,
                'status' => $status,
                'result' => $result,
            ];
        } catch (Throwable $e) {
            $this->automation->failRun($runId, $e);
            return [
                'success' => false,
                'run_id' => $runId,
                'module' => $module,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        } finally {
            $duration = (int) round(microtime(true) - $startedAt);
            $this->automation->updateScheduleAfterRun($module, $success, $duration);
            $this->automation->releaseLock($lockKey);
        }
    }

    /**
     * Routes module name to module method.
     *
     * @param string $module
     * @param int    $runId
     * @param int    $timeout
     * @return array
     */
    private function dispatchModule(string $module, int $runId, int $timeout): array
    {
        if ($timeout > 0) {
            @set_time_limit($timeout);
        }

        return match ($module) {
            'scraper' => $this->runScraper($runId),
            'writer_blog' => $this->runWriter($runId, 'blog'),
            'writer_review' => $this->runWriter($runId, 'review'),
            'writer_comparison' => $this->runWriter($runId, 'comparison'),
            'writer_news' => $this->runWriter($runId, 'news'),
            'social' => $this->runSocial($runId),
            'affiliate' => $this->runAffiliate($runId),
            'indexer' => $this->runIndexer($runId),
            'report' => $this->runReport($runId),
            default => [
                'status' => 'failed',
                'error' => 'Unknown module: ' . $module,
            ],
        };
    }

    /**
     * Module 1: Master scraper (YouTube + Reddit + RSS).
     *
     * @param int $runId
     * @return array
     */
    private function runScraper(int $runId): array
    {
        $sessionId = 'scraper_' . gmdate('YmdHis') . '_' . substr(md5((string) microtime(true)), 0, 8);
        $allItems = [];
        $itemsFound = 0;
        $itemsSaved = 0;
        $itemsSkipped = 0;
        $roadmapCreated = 0;
        $roadmapEnriched = 0;

        $this->automation->log($runId, 'scraper', 'info', 'scraper_start', 'Starting scraper module', [
            'session_id' => $sessionId,
        ]);

        $sources = [
            'youtube' => fn(): array => $this->fetchYoutubeItems($runId, $sessionId),
            'reddit' => fn(): array => $this->fetchRedditItems($runId, $sessionId),
            'rss' => fn(): array => $this->fetchRssItems($runId, $sessionId),
            'competitors' => fn(): array => $this->fetchCompetitorFeedItems($runId, $sessionId),
        ];

        foreach ($sources as $name => $loader) {
            if (!$this->isSourceEnabled($name)) {
                $this->automation->log($runId, 'scraper', 'info', 'source_disabled', 'Source disabled in config', [
                    'source' => $name,
                ]);
                continue;
            }
            try {
                $items = $loader();
                $allItems = array_merge($allItems, $items);
                $this->automation->log($runId, 'scraper', 'info', 'source_done', 'Source scraped', [
                    'source' => $name,
                    'items' => count($items),
                ]);
            } catch (Throwable $e) {
                $this->automation->log($runId, 'scraper', 'error', 'source_fail', 'Source scraping failed', [
                    'source' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($allItems as $item) {
            $itemsFound++;
            if (!empty($item['source_id']) && KnowledgeItem::existsBySourceId((string) $item['source_id'])) {
                $itemsSkipped++;
                continue;
            }

            $processed = $this->processScrapedItem($item, $runId);
            if (!$processed['success']) {
                $itemsSkipped++;
                continue;
            }

            $itemsSaved++;
            $roadmapCreated += (int) ($processed['roadmap_created'] ?? 0);
            $roadmapEnriched += (int) ($processed['roadmap_enriched'] ?? 0);
        }

        // Save keyword performance stats from this run
        $this->saveKeywordStats();

        // Auto-learn new keywords from freshly scraped content
        $newKeywords = $this->learnKeywordsFromContent($runId);

        return [
            'status' => 'completed',
            'processed' => $itemsFound,
            'succeeded' => $itemsSaved,
            'failed' => max(0, $itemsFound - $itemsSaved - $itemsSkipped),
            'items_found' => $itemsFound,
            'items_saved' => $itemsSaved,
            'items_skipped' => $itemsSkipped,
            'roadmap_created' => $roadmapCreated,
            'roadmap_enriched' => $roadmapEnriched,
            'keywords_learned' => $newKeywords,
            'session_id' => $sessionId,
        ];
    }

    /**
     * Module 2: Writer.
     *
     * @param int    $runId
     * @param string $type
     * @return array
     */
    private function runWriter(int $runId, string $type): array
    {
        $module = 'writer_' . $type;
        $this->automation->log($runId, $module, 'info', 'writer_start', 'Writer started', ['type' => $type]);

        $roadmap = RoadmapItem::getNextPending($type);
        if (!$roadmap) {
            return ['status' => 'skipped', 'reason' => "No pending {$type} roadmap items", 'processed' => 0, 'failed' => 0];
        }

        $roadmapId = (int) $roadmap['id'];
        RoadmapItem::markGenerating($roadmapId);

        try {
            $context = $this->buildWriterContext($roadmap);
            $generateResult = $this->generateArticlePayload($roadmap, $context, $runId);
            $articlePayload = $generateResult['article_payload'];

            $seoReview = $this->runSeoReview($articlePayload, (string) ($roadmap['primary_keyword'] ?? ''), $runId);
            $articlePayload = $this->mergeSeoReview($articlePayload, $seoReview);

            $slug = $this->ensureUniqueSlug($this->sanitizeSlug((string) ($articlePayload['slug'] ?? '')));
            $articlePayload['slug'] = $slug;
            $articlePayload['content'] = $this->renderMarkdownToHtml((string) ($articlePayload['content'] ?? ''));

            $image = $this->generateFeaturedImage($slug, $articlePayload, $runId);
            if ($image !== null) {
                $articlePayload['featured_image_url'] = $image['url'];
                $articlePayload['featured_image_alt'] = (string) ($articlePayload['featured_image_alt'] ?? $image['alt']);
            }

            $articleId = $this->saveArticleFromPayload($articlePayload, $roadmap, $generateResult, $seoReview);
            $quality = $this->qualityGate($articleId, $roadmap, $articlePayload);
            if (!($quality['passed'] ?? false)) {
                $issues = is_array($quality['issues'] ?? null) ? $quality['issues'] : ['Quality gate failed'];
                Article::update((int) $articleId, [
                    'status' => 'draft',
                    'published_at' => null,
                ]);
                RoadmapItem::update($roadmapId, [
                    'status' => 'pending',
                    'article_id' => null,
                    'retry_count' => ((int) ($roadmap['retry_count'] ?? 0)) + 1,
                    'last_error' => mb_substr('Quality gate failed: ' . implode('; ', $issues), 0, 1000),
                ]);

                $this->automation->log($runId, $module, 'warning', 'writer_quality_gate_failed', 'Article failed quality gate and was re-queued', [
                    'roadmap_id' => $roadmapId,
                    'article_id' => $articleId,
                    'issues' => $issues,
                    'metrics' => $quality['metrics'] ?? [],
                ]);

                return [
                    'status' => 'completed',
                    'processed' => 1,
                    'succeeded' => 0,
                    'failed' => 1,
                    'roadmap_id' => $roadmapId,
                    'article_id' => $articleId,
                    'quality_gate' => 'failed',
                    'issues' => $issues,
                ];
            }

            $autoPublishEnabled = strtolower((string) (Setting::get('automation_auto_publish') ?? 'true')) !== 'false';
            $finalStatus = $autoPublishEnabled ? 'published' : 'draft';
            Article::update($articleId, [
                'status' => $finalStatus,
                'published_at' => $finalStatus === 'published' ? date('Y-m-d H:i:s') : null,
            ]);

            $this->postProcessArticle($articleId, $articlePayload, $roadmap, $image);

            RoadmapItem::updateStatus($roadmapId, 'published', $articleId);

            $this->automation->log($runId, $module, 'info', 'writer_success', 'Article generated', [
                'roadmap_id' => $roadmapId,
                'article_id' => $articleId,
                'slug' => $slug,
                'article_status' => $finalStatus,
            ]);

            // Chain follow-up modules only when article is published.
            if ($finalStatus === 'published') {
                $this->runModule('social', 'chained', $module);
                $this->runModule('indexer', 'chained', $module);
            }

            return [
                'status' => 'completed',
                'processed' => 1,
                'succeeded' => 1,
                'failed' => 0,
                'roadmap_id' => $roadmapId,
                'article_id' => $articleId,
                'article_status' => $finalStatus,
            ];
        } catch (Throwable $e) {
            RoadmapItem::markFailed($roadmapId, $e->getMessage(), 3);
            $this->automation->log($runId, $module, 'error', 'writer_failed', 'Writer failed', [
                'roadmap_id' => $roadmapId,
                'error' => $e->getMessage(),
            ]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'processed' => 1,
                'succeeded' => 0,
                'failed' => 1,
                'roadmap_id' => $roadmapId,
            ];
        }
    }

    /**
     * Scrapes YouTube with rotating query list.
     *
     * @param int    $runId
     * @param string $sessionId
     * @return array
     */
    private function fetchYoutubeItems(int $runId, string $sessionId): array
    {
        $apiKey = trim((string) (Setting::get('youtube_api_key') ?? ''));
        if ($apiKey === '') {
            $this->automation->log($runId, 'scraper', 'warning', 'youtube_skip', 'YouTube API key not configured');
            return [];
        }

        $queries = $this->getYoutubeQueries();
        $index = (int) (Setting::get('automation_scraper_youtube_query_index') ?? '0');
        $query = $queries[$index % count($queries)];
        Setting::set('automation_scraper_youtube_query_index', (string) ($index + 1));

        $timeWindow = $this->getScraperSetting('scraper_time_window_hours', 12);
        $maxResults = $this->getScraperSetting('scraper_youtube_max_results', 10);
        $publishedAfter = gmdate('c', time() - ($timeWindow * 3600));
        $params = http_build_query([
            'key' => $apiKey,
            'part' => 'snippet',
            'type' => 'video',
            'maxResults' => $maxResults,
            'order' => 'date',
            'publishedAfter' => $publishedAfter,
            'q' => $query,
        ]);

        $url = 'https://www.googleapis.com/youtube/v3/search?' . $params;
        $response = HttpClient::get($url, ['Accept: application/json'], 30, ['retries' => 2, 'verify_ssl' => true]);

        if (!$response['success']) {
            ScrapeLog::create([
                'session_id' => $sessionId,
                'source_type' => 'youtube',
                'query' => $query,
                'status' => 'failed',
                'error_message' => (string) ($response['error'] ?? 'YouTube request failed'),
                'duration_seconds' => max(1, (int) (($response['time_ms'] ?? 0) / 1000)),
            ]);
            return [];
        }

        $items = $response['json']['items'] ?? [];
        $videoIds = [];
        $rawItems = [];
        foreach ($items as $item) {
            $videoId = (string) ($item['id']['videoId'] ?? '');
            if ($videoId === '') {
                continue;
            }
            $snippet = $item['snippet'] ?? [];
            $title = trim((string) ($snippet['title'] ?? ''));
            $desc = trim((string) ($snippet['description'] ?? ''));
            if ($title === '' && $desc === '') {
                continue;
            }
            $videoIds[] = $videoId;
            $rawItems[$videoId] = [
                'source_id' => 'yt_' . $videoId,
                'source_type' => 'youtube',
                'source_name' => (string) ($snippet['channelTitle'] ?? 'YouTube'),
                'source_url' => 'https://youtube.com/watch?v=' . rawurlencode($videoId),
                'title' => $title,
                'content' => mb_substr($desc !== '' ? $desc : $title, 0, 1000),
                'published_at' => (string) ($snippet['publishedAt'] ?? ''),
                'view_count' => 0,
                'like_count' => 0,
            ];
        }

        if ($videoIds) {
            $statsParams = http_build_query([
                'key' => $apiKey,
                'part' => 'statistics',
                'id' => implode(',', $videoIds),
            ]);
            $statsResponse = HttpClient::get(
                'https://www.googleapis.com/youtube/v3/videos?' . $statsParams,
                ['Accept: application/json'],
                15,
                ['retries' => 1, 'verify_ssl' => true]
            );
            if ($statsResponse['success']) {
                foreach (($statsResponse['json']['items'] ?? []) as $statItem) {
                    $vid = (string) ($statItem['id'] ?? '');
                    $stats = $statItem['statistics'] ?? [];
                    if (isset($rawItems[$vid])) {
                        $rawItems[$vid]['view_count'] = (int) ($stats['viewCount'] ?? 0);
                        $rawItems[$vid]['like_count'] = (int) ($stats['likeCount'] ?? 0);
                    }
                }
            }
        }

        foreach ($rawItems as $vid => $rawItem) {
            $transcript = $this->fetchYoutubeTranscript($vid);
            if ($transcript !== '') {
                $rawItems[$vid]['transcript'] = $transcript;
                $rawItems[$vid]['content'] = mb_substr(
                    $rawItem['content'] . "\n\n[Transcript]\n" . $transcript,
                    0,
                    5000
                );
            }
        }

        $normalized = array_values($rawItems);

        ScrapeLog::create([
            'session_id' => $sessionId,
            'source_type' => 'youtube',
            'query' => $query,
            'items_found' => count($normalized),
            'items_saved' => 0,
            'items_skipped' => 0,
            'status' => 'success',
            'duration_seconds' => max(1, (int) (($response['time_ms'] ?? 0) / 1000)),
        ]);

        return $normalized;
    }

    /**
     * Fetches auto-generated captions/transcript for a YouTube video.
     * Uses the unofficial timedtext page-scrape method.
     *
     * @param string $videoId
     * @return string Plain-text transcript, or empty string on failure
     */
    private function fetchYoutubeTranscript(string $videoId): string
    {
        if ($videoId === '') {
            return '';
        }

        try {
            $watchUrl = 'https://www.youtube.com/watch?v=' . rawurlencode($videoId);
            $pageResponse = HttpClient::get(
                $watchUrl,
                [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept-Language: en-US,en;q=0.9',
                ],
                15,
                ['retries' => 1, 'verify_ssl' => true]
            );

            if (!$pageResponse['success'] || empty($pageResponse['body'])) {
                return '';
            }

            $html = (string) $pageResponse['body'];

            if (!preg_match('/"captionTracks"\s*:\s*(\[.*?\])/s', $html, $matches)) {
                return '';
            }

            $captionTracks = json_decode($matches[1], true);
            if (!is_array($captionTracks) || empty($captionTracks)) {
                return '';
            }

            $captionUrl = '';
            foreach ($captionTracks as $track) {
                $lang = strtolower((string) ($track['languageCode'] ?? ''));
                if ($lang === 'en' || str_starts_with($lang, 'en')) {
                    $captionUrl = (string) ($track['baseUrl'] ?? '');
                    break;
                }
            }
            if ($captionUrl === '') {
                $captionUrl = (string) ($captionTracks[0]['baseUrl'] ?? '');
            }
            if ($captionUrl === '') {
                return '';
            }

            $captionResponse = HttpClient::get(
                $captionUrl,
                ['User-Agent: Mozilla/5.0', 'Accept: application/xml, text/xml'],
                10,
                ['retries' => 1, 'verify_ssl' => true]
            );

            if (!$captionResponse['success'] || empty($captionResponse['body'])) {
                return '';
            }

            $xml = (string) $captionResponse['body'];
            $text = preg_replace('/<[^>]+>/', ' ', $xml);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);

            return mb_substr($text, 0, 4000);
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Scrapes Reddit posts with OAuth token.
     *
     * @param int    $runId
     * @param string $sessionId
     * @return array
     */
    private function fetchRedditItems(int $runId, string $sessionId): array
    {
        $clientId = trim((string) (Setting::get('reddit_client_id') ?? ''));
        $clientSecret = trim((string) (Setting::get('reddit_client_secret') ?? ''));
        $username = trim((string) (Setting::get('reddit_username') ?? ''));
        $password = trim((string) (Setting::get('reddit_password') ?? ''));

        if ($clientId === '' || $clientSecret === '' || $username === '' || $password === '') {
            $this->automation->log($runId, 'scraper', 'warning', 'reddit_skip', 'Reddit credentials not configured');
            return [];
        }

        $tokenResponse = HttpClient::post(
            'https://www.reddit.com/api/v1/access_token',
            http_build_query([
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password,
            ]),
            [
                'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: DevLync-Scraper/1.0 by ' . $username,
            ],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );

        $accessToken = (string) ($tokenResponse['json']['access_token'] ?? '');
        if ($accessToken === '') {
            ScrapeLog::create([
                'session_id' => $sessionId,
                'source_type' => 'reddit',
                'query' => 'oauth_token',
                'status' => 'failed',
                'error_message' => 'Reddit auth failed: ' . (string) ($tokenResponse['error'] ?? 'missing token'),
            ]);
            return [];
        }

        $subredditList = $this->getRedditSubreddits();
        $subreddits = implode('+', $subredditList);
        $redditMaxResults = $this->getScraperSetting('scraper_reddit_max_results', 50);
        $url = 'https://oauth.reddit.com/r/' . $subreddits . '/hot.json?limit=' . $redditMaxResults;
        $response = HttpClient::get(
            $url,
            [
                'Authorization: Bearer ' . $accessToken,
                'User-Agent: DevLync-Scraper/1.0 by ' . $username,
                'Accept: application/json',
            ],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );

        if (!$response['success']) {
            ScrapeLog::create([
                'session_id' => $sessionId,
                'source_type' => 'reddit',
                'query' => $subreddits,
                'status' => 'failed',
                'error_message' => (string) ($response['error'] ?? 'Reddit fetch failed'),
                'duration_seconds' => max(1, (int) (($response['time_ms'] ?? 0) / 1000)),
            ]);
            return [];
        }

        $children = $response['json']['data']['children'] ?? [];
        $normalized = [];
        foreach ($children as $child) {
            $post = $child['data'] ?? [];
            $title = trim((string) ($post['title'] ?? ''));
            $content = trim((string) ($post['selftext'] ?? ''));
            $text = mb_strtolower($title . ' ' . $content);

            $score = (int) ($post['score'] ?? 0);
            $isStickied = (bool) ($post['stickied'] ?? false);
            $isVideo = (bool) ($post['is_video'] ?? false);
            $hasKeyword = $this->containsAnyKeyword($text, $this->getScraperKeywords(), true);
            $minScore = $this->getScraperSetting('scraper_reddit_min_score', 20);

            if ($score < $minScore || $isStickied || $isVideo || !$hasKeyword) {
                continue;
            }

            $id = (string) ($post['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $normalized[] = [
                'source_id' => 'reddit_' . $id,
                'source_type' => 'reddit',
                'source_name' => 'r/' . (string) ($post['subreddit'] ?? 'reddit'),
                'source_url' => 'https://reddit.com' . (string) ($post['permalink'] ?? ''),
                'title' => $title,
                'content' => mb_substr($content !== '' ? $content : $title, 0, 1000),
                'published_at' => isset($post['created_utc']) ? gmdate('Y-m-d H:i:s', (int) $post['created_utc']) : '',
                'score' => $score,
                'comment_count' => (int) ($post['num_comments'] ?? 0),
                'upvote_ratio' => (float) ($post['upvote_ratio'] ?? 0),
                'subreddit' => (string) ($post['subreddit'] ?? ''),
            ];
        }

        ScrapeLog::create([
            'session_id' => $sessionId,
            'source_type' => 'reddit',
            'query' => $subreddits,
            'items_found' => count($normalized),
            'items_saved' => 0,
            'items_skipped' => 0,
            'status' => 'success',
            'duration_seconds' => max(1, (int) (($response['time_ms'] ?? 0) / 1000)),
        ]);

        return $normalized;
    }

    /**
     * Scrapes RSS feeds.
     *
     * @param int    $runId
     * @param string $sessionId
     * @return array
     */
    private function fetchRssItems(int $runId, string $sessionId): array
    {
        $normalized = [];
        $timeWindow = $this->getScraperSetting('scraper_time_window_hours', 12);
        $cutoff = time() - ($timeWindow * 3600);
        $scraperKeywords = $this->getScraperKeywords();

        foreach ($this->getRssFeeds() as $feed) {
            if (isset($feed['is_enabled']) && !$feed['is_enabled']) {
                continue;
            }
            $name = (string) ($feed['name'] ?? '');
            $url = (string) ($feed['url'] ?? '');
            $sourceType = (string) ($feed['source_type'] ?? 'rss');
            if ($url === '') {
                continue;
            }

            $response = HttpClient::get(
                $url,
                ['Accept: application/xml, text/xml, application/rss+xml, application/atom+xml'],
                10,
                ['retries' => 1, 'verify_ssl' => true]
            );

            if (!$response['success']) {
                ScrapeLog::create([
                    'session_id' => $sessionId,
                    'source_type' => 'rss',
                    'query' => $name,
                    'status' => 'failed',
                    'error_message' => (string) ($response['error'] ?? 'RSS fetch failed'),
                    'duration_seconds' => max(1, (int) (($response['time_ms'] ?? 0) / 1000)),
                ]);
                continue;
            }

            $items = $this->parseRssFeed((string) ($response['body'] ?? ''), $name);
            $kept = 0;
            foreach ($items as $item) {
                $publishedTs = (int) ($item['published_ts'] ?? 0);
                if ($publishedTs > 0 && $publishedTs < $cutoff) {
                    continue;
                }

                $title = trim((string) ($item['title'] ?? ''));
                $description = trim((string) ($item['description'] ?? ''));
                if (!$this->containsAnyKeyword(mb_strtolower($title . ' ' . $description), $scraperKeywords, true)) {
                    continue;
                }

                $link = trim((string) ($item['link'] ?? ''));
                if ($link === '') {
                    continue;
                }

                $kept++;
                $normalized[] = [
                    'source_id' => 'rss_' . md5($link),
                    'source_type' => $sourceType,
                    'source_name' => $name,
                    'source_url' => $link,
                    'title' => $title,
                    'content' => mb_substr($description !== '' ? $description : $title, 0, 1200),
                    'published_at' => $publishedTs > 0 ? gmdate('Y-m-d H:i:s', $publishedTs) : '',
                ];
            }

            ScrapeLog::create([
                'session_id' => $sessionId,
                'source_type' => 'rss',
                'query' => $name,
                'items_found' => $kept,
                'items_saved' => 0,
                'items_skipped' => 0,
                'status' => 'success',
                'duration_seconds' => max(1, (int) (($response['time_ms'] ?? 0) / 1000)),
            ]);
        }

        $this->automation->log($runId, 'scraper', 'info', 'rss_done', 'RSS feeds processed', ['items' => count($normalized)]);
        return $normalized;
    }

    /**
     * Fetches items from enabled competitor RSS feeds.
     *
     * @param int    $runId
     * @param string $sessionId
     * @return array
     */
    private function fetchCompetitorFeedItems(int $runId, string $sessionId): array
    {
        $feeds = CompetitorFeed::getEnabledFeeds();
        if (!$feeds) {
            return [];
        }

        $normalized = [];
        $timeWindow = $this->getScraperSetting('scraper_time_window_hours', 12);
        $cutoff = time() - ($timeWindow * 3600);
        $scraperKeywords = $this->getScraperKeywords();

        foreach ($feeds as $feed) {
            $feedId = (int) $feed['id'];
            $feedUrl = (string) ($feed['feed_url'] ?? '');
            $domain = (string) ($feed['domain'] ?? 'competitor');
            if ($feedUrl === '') {
                continue;
            }

            $response = HttpClient::get(
                $feedUrl,
                ['Accept: application/xml, text/xml, application/rss+xml, application/atom+xml'],
                10,
                ['retries' => 1, 'verify_ssl' => true]
            );

            if (!$response['success']) {
                CompetitorFeed::updateAfterScrape($feedId, 0, (string) ($response['error'] ?? 'Fetch failed'));
                ScrapeLog::create([
                    'session_id' => $sessionId,
                    'source_type' => 'competitor',
                    'query' => $domain,
                    'status' => 'failed',
                    'error_message' => (string) ($response['error'] ?? 'Competitor feed fetch failed'),
                    'duration_seconds' => max(1, (int) (($response['time_ms'] ?? 0) / 1000)),
                ]);
                continue;
            }

            $items = $this->parseRssFeed((string) ($response['body'] ?? ''), $domain);
            $kept = 0;
            foreach ($items as $item) {
                $publishedTs = (int) ($item['published_ts'] ?? 0);
                if ($publishedTs > 0 && $publishedTs < $cutoff) {
                    continue;
                }

                $title = trim((string) ($item['title'] ?? ''));
                $description = trim((string) ($item['description'] ?? ''));
                if (!$this->containsAnyKeyword(mb_strtolower($title . ' ' . $description), $scraperKeywords, true)) {
                    continue;
                }

                $link = trim((string) ($item['link'] ?? ''));
                if ($link === '') {
                    continue;
                }

                $kept++;
                $normalized[] = [
                    'source_id' => 'comp_' . md5($link),
                    'source_type' => 'competitor',
                    'source_name' => $domain,
                    'source_url' => $link,
                    'title' => $title,
                    'content' => mb_substr($description !== '' ? $description : $title, 0, 1200),
                    'published_at' => $publishedTs > 0 ? gmdate('Y-m-d H:i:s', $publishedTs) : '',
                ];
            }

            CompetitorFeed::updateAfterScrape($feedId, $kept);
            ScrapeLog::create([
                'session_id' => $sessionId,
                'source_type' => 'competitor',
                'query' => $domain,
                'items_found' => $kept,
                'items_saved' => 0,
                'items_skipped' => 0,
                'status' => 'success',
                'duration_seconds' => max(1, (int) (($response['time_ms'] ?? 0) / 1000)),
            ]);
        }

        $this->automation->log($runId, 'scraper', 'info', 'competitor_done', 'Competitor feeds processed', [
            'feeds' => count($feeds),
            'items' => count($normalized),
        ]);
        return $normalized;
    }

    /**
     * Discovers RSS feeds for pending competitor sites.
     * Tries common RSS feed paths and checks for valid XML.
     *
     * @param int $runId
     * @param int $batchSize
     * @return array{checked: int, discovered: int, no_feed: int, errors: int}
     */
    public function discoverCompetitorFeeds(int $runId, int $batchSize = 50): array
    {
        $result = ['checked' => 0, 'discovered' => 0, 'no_feed' => 0, 'errors' => 0];
        $pending = CompetitorSite::getPendingForDiscovery($batchSize);

        $commonPaths = [
            '/feed',
            '/rss',
            '/feed.xml',
            '/rss.xml',
            '/atom.xml',
            '/blog/feed',
            '/blog/rss',
            '/blog/feed.xml',
            '/index.xml',
            '/feeds/posts/default',
        ];

        foreach ($pending as $site) {
            $siteId = (int) $site['id'];
            $domain = (string) $site['domain'];
            $result['checked']++;
            $foundFeed = false;

            foreach ($commonPaths as $path) {
                $feedUrl = 'https://' . $domain . $path;
                try {
                    $response = HttpClient::get(
                        $feedUrl,
                        [
                            'Accept: application/xml, text/xml, application/rss+xml, application/atom+xml',
                            'User-Agent: Mozilla/5.0 (compatible; DevLyncBot/1.0)',
                        ],
                        8,
                        ['retries' => 0, 'verify_ssl' => true]
                    );

                    if (!$response['success']) {
                        continue;
                    }

                    $body = trim((string) ($response['body'] ?? ''));
                    if ($body === '' || mb_strlen($body) < 100) {
                        continue;
                    }

                    if (
                        stripos($body, '<rss') !== false ||
                        stripos($body, '<feed') !== false ||
                        stripos($body, '<channel') !== false ||
                        stripos($body, 'xmlns:atom') !== false
                    ) {
                        $feedType = (stripos($body, '<feed') !== false && stripos($body, 'xmlns="http://www.w3.org/2005/Atom"') !== false)
                            ? 'atom' : 'rss';

                        $feedTitle = null;
                        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $m)) {
                            $feedTitle = mb_substr(trim(strip_tags(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'))), 0, 255);
                        }

                        CompetitorFeed::create([
                            'competitor_id' => $siteId,
                            'feed_url' => $feedUrl,
                            'feed_type' => $feedType,
                            'feed_title' => $feedTitle ?: $domain,
                            'is_enabled' => 0,
                        ]);

                        $foundFeed = true;
                    }
                } catch (Throwable $e) {
                    continue;
                }
            }

            if ($foundFeed) {
                CompetitorSite::updateRssStatus($siteId, 'discovered');
                $result['discovered']++;
            } else {
                CompetitorSite::updateRssStatus($siteId, 'no_feed');
                $result['no_feed']++;
            }
        }

        $this->automation->log($runId, 'scraper', 'info', 'rss_discovery', 'Competitor RSS discovery batch', $result);
        return $result;
    }

    /**
     * Processes one scraped item through AI and saves it.
     *
     * @param array $item
     * @param int   $runId
     * @return array
     */
    private function processScrapedItem(array $item, int $runId): array
    {
        $analysis = $this->analyzeScrapedItemWithAi($item, $runId);
        $knowledge = $analysis['knowledge'];

        if (trim((string) ($knowledge['title'] ?? '')) === '') {
            $knowledge['title'] = (string) ($item['title'] ?? 'Untitled');
        }
        if (trim((string) ($knowledge['summary'] ?? '')) === '') {
            $knowledge['summary'] = mb_substr((string) ($item['content'] ?? ''), 0, 300);
        }

        $qualityScore = $this->calculateQualityScore($item, $knowledge);

        $knowledgeId = KnowledgeItem::create([
            'title' => $knowledge['title'],
            'content' => (string) ($item['content'] ?? ''),
            'summary' => $knowledge['summary'],
            'source_url' => (string) ($item['source_url'] ?? ''),
            'source_type' => (string) ($item['source_type'] ?? 'rss'),
            'source_name' => (string) ($item['source_name'] ?? ''),
            'source_id' => (string) ($item['source_id'] ?? ''),
            'topics' => is_array($knowledge['topics'] ?? null) ? $knowledge['topics'] : [],
            'keywords' => is_array($knowledge['keywords'] ?? null) ? $knowledge['keywords'] : [],
            'entities' => is_array($knowledge['entities'] ?? null) ? $knowledge['entities'] : [],
            'sentiment' => (string) ($knowledge['sentiment'] ?? 'neutral'),
            'quality_score' => $qualityScore,
            'is_processed' => 1,
            'processed_at' => date('Y-m-d H:i:s'),
            'processing_cost' => (float) ($analysis['cost_usd'] ?? 0),
        ]);

        $created = 0;
        $enriched = 0;
        $ideas = is_array($analysis['article_ideas'] ?? null) ? $analysis['article_ideas'] : [];
        foreach ($ideas as $idea) {
            $upsert = $this->upsertRoadmapIdea($idea, (string) ($item['source_type'] ?? 'rss'), $knowledgeId, $runId, $qualityScore);
            if ($upsert === 'created') {
                $created++;
            } elseif ($upsert === 'enriched') {
                $enriched++;
            }
        }

        return [
            'success' => true,
            'knowledge_id' => $knowledgeId,
            'quality_score' => $qualityScore,
            'roadmap_created' => $created,
            'roadmap_enriched' => $enriched,
        ];
    }

    /**
     * Calculates a 0-100 quality score for a scraped item based on source signals and AI analysis richness.
     *
     * @param array $item      Raw scraped item
     * @param array $knowledge AI-analyzed knowledge data
     * @return int
     */
    private function calculateQualityScore(array $item, array $knowledge): int
    {
        $score = 30;
        $sourceType = (string) ($item['source_type'] ?? '');

        if ($sourceType === 'reddit') {
            $redditScore = (int) ($item['score'] ?? 0);
            if ($redditScore >= 500) {
                $score += 40;
            } elseif ($redditScore >= 100) {
                $score += 25;
            } elseif ($redditScore >= 50) {
                $score += 15;
            }
            $commentCount = (int) ($item['comment_count'] ?? 0);
            if ($commentCount >= 50) {
                $score += 10;
            } elseif ($commentCount >= 20) {
                $score += 5;
            }
            $upvoteRatio = (float) ($item['upvote_ratio'] ?? 0);
            if ($upvoteRatio >= 0.9) {
                $score += 5;
            }
        } elseif ($sourceType === 'youtube') {
            $descLen = mb_strlen((string) ($item['content'] ?? ''));
            $score += $descLen > 500 ? 25 : ($descLen > 200 ? 15 : 5);
            $viewCount = (int) ($item['view_count'] ?? 0);
            if ($viewCount >= 100000) {
                $score += 15;
            } elseif ($viewCount >= 10000) {
                $score += 10;
            } elseif ($viewCount >= 1000) {
                $score += 5;
            }
            $likeCount = (int) ($item['like_count'] ?? 0);
            if ($likeCount >= 1000) {
                $score += 5;
            }
        } elseif (in_array($sourceType, ['hackernews', 'rss'], true)) {
            $score += 20;
        } elseif ($sourceType === 'devto') {
            $score += 15;
        } elseif ($sourceType === 'producthunt') {
            $score += 10;
        }

        $topics = is_array($knowledge['topics'] ?? null) ? $knowledge['topics'] : [];
        $keywords = is_array($knowledge['keywords'] ?? null) ? $knowledge['keywords'] : [];
        $entities = is_array($knowledge['entities'] ?? null) ? $knowledge['entities'] : [];
        $ideas = is_array($knowledge['articleIdeas'] ?? null) ? $knowledge['articleIdeas'] : [];

        if (count($topics) >= 2) {
            $score += 5;
        }
        if (count($keywords) >= 3) {
            $score += 5;
        }
        if (count($entities) >= 1) {
            $score += 5;
        }
        if (count($ideas) >= 1) {
            $score += 5;
        }

        $contentLen = mb_strlen((string) ($item['content'] ?? ''));
        if ($contentLen > 500) {
            $score += 5;
        }

        return min(100, max(0, $score));
    }

    /**
     * Calls AI to structure scraped content.
     *
     * @param array $item
     * @param int   $runId
     * @return array
     */
    private function analyzeScrapedItemWithAi(array $item, int $runId): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You analyze developer-tools content and output strict JSON only.',
            ],
            [
                'role' => 'user',
                'content' => "Source: {$item['source_type']} - {$item['source_name']}\n"
                    . "Title: {$item['title']}\n"
                    . "Content: {$item['content']}\n\n"
                    . "Return JSON with keys: title, summary, topics, keywords, entities, sentiment, articleIdeas[].\n"
                    . "Each articleIdeas item: title, keyword, contentType(blog|review|comparison|news), searchIntent, estimatedVolume(high|medium|low).",
            ],
        ];

        $response = $this->automation->callAiWithFallback('scraper', $messages, [
            'run_id' => $runId,
            'json_mode' => true,
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        if (!($response['success'] ?? false)) {
            return [
                'knowledge' => [
                    'title' => (string) ($item['title'] ?? ''),
                    'summary' => mb_substr((string) ($item['content'] ?? ''), 0, 300),
                    'topics' => [],
                    'keywords' => [],
                    'entities' => [],
                    'sentiment' => 'neutral',
                ],
                'article_ideas' => [],
                'cost_usd' => 0.0,
            ];
        }

        $json = $this->decodeJsonFromText((string) ($response['content'] ?? ''));
        if (!$json) {
            return [
                'knowledge' => [
                    'title' => (string) ($item['title'] ?? ''),
                    'summary' => mb_substr((string) ($item['content'] ?? ''), 0, 300),
                    'topics' => [],
                    'keywords' => [],
                    'entities' => [],
                    'sentiment' => 'neutral',
                ],
                'article_ideas' => [],
                'cost_usd' => (float) ($response['cost_usd'] ?? 0),
            ];
        }

        return [
            'knowledge' => [
                'title' => (string) ($json['title'] ?? $item['title']),
                'summary' => (string) ($json['summary'] ?? mb_substr((string) ($item['content'] ?? ''), 0, 300)),
                'topics' => is_array($json['topics'] ?? null) ? $json['topics'] : [],
                'keywords' => is_array($json['keywords'] ?? null) ? $json['keywords'] : [],
                'entities' => is_array($json['entities'] ?? null) ? $json['entities'] : [],
                'sentiment' => (string) ($json['sentiment'] ?? 'neutral'),
            ],
            'article_ideas' => is_array($json['articleIdeas'] ?? null) ? $json['articleIdeas'] : [],
            'cost_usd' => (float) ($response['cost_usd'] ?? 0),
        ];
    }

    /**
     * Inserts or enriches roadmap item from AI idea.
     *
     * @param array  $idea
     * @param string $source
     * @param int    $knowledgeId
     * @param int    $runId
     * @param int    $qualityScore
     * @return string created|enriched|skipped
     */
    private function upsertRoadmapIdea(array $idea, string $source, int $knowledgeId, int $runId, int $qualityScore = 50): string
    {
        $title = trim((string) ($idea['title'] ?? ''));
        $keyword = trim((string) ($idea['keyword'] ?? ''));
        $contentType = strtolower(trim((string) ($idea['contentType'] ?? 'blog')));
        if (!in_array($contentType, ['blog', 'review', 'comparison', 'news'], true)) {
            $contentType = 'blog';
        }
        if ($keyword === '' && $title === '') {
            return 'skipped';
        }
        if ($keyword === '') {
            $keyword = $title;
        }

        $slug = $this->sanitizeSlug($keyword);
        if ($slug === '') {
            return 'skipped';
        }

        $existing = RoadmapItem::getBySlugActive($slug);
        if (!$existing) {
            $existing = RoadmapItem::findSimilar($keyword, $contentType, 0.6);
        }
        if ($existing) {
            $existingIds = json_decode((string) ($existing['knowledge_item_ids'] ?? '[]'), true);
            if (!is_array($existingIds)) {
                $existingIds = [];
            }
            $mergedIds = array_values(array_unique(array_map('intval', array_merge($existingIds, [$knowledgeId]))));
            RoadmapItem::incrementEnrichment((int) $existing['id'], $mergedIds);
            $this->automation->log($runId, 'scraper', 'info', 'roadmap_enrich', 'Roadmap enrichment (dedup match)', [
                'slug' => $slug,
                'matched_id' => (int) $existing['id'],
                'matched_slug' => (string) ($existing['slug'] ?? ''),
            ]);
            return 'enriched';
        }

        $volumeLabel = strtolower((string) ($idea['estimatedVolume'] ?? 'medium'));
        $estimatedVolume = $this->mapEstimatedVolume($volumeLabel);
        $priority = $this->mapPriorityFromVolume($volumeLabel);
        $searchIntent = strtolower((string) ($idea['searchIntent'] ?? 'informational'));
        if (!in_array($searchIntent, ['informational', 'commercial', 'transactional', 'navigational'], true)) {
            $searchIntent = 'informational';
        }

        $autoApproveThreshold = $this->getScraperSetting('scraper_auto_approve_threshold', 60);
        $initialStatus = ($autoApproveThreshold > 0 && $qualityScore >= $autoApproveThreshold) ? 'pending' : 'needs_review';

        RoadmapItem::create([
            'title' => $title !== '' ? $title : ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'content_type' => $contentType,
            'description' => 'Generated from scraper source: ' . $source,
            'primary_keyword' => $keyword,
            'search_intent' => $searchIntent,
            'estimated_volume' => $estimatedVolume,
            'priority' => $priority,
            'source' => $source,
            'status' => $initialStatus,
            'knowledge_item_ids' => [$knowledgeId],
        ]);

        $this->automation->log($runId, 'scraper', 'info', 'roadmap_create', 'Roadmap item created', [
            'slug' => $slug,
            'content_type' => $contentType,
            'status' => $initialStatus,
            'quality_score' => $qualityScore,
        ]);

        return 'created';
    }

    /**
     * Parses RSS or Atom feed XML into normalized items.
     *
     * @param string $xml
     * @param string $feedName
     * @return array
     */
    private function parseRssFeed(string $xml, string $feedName): array
    {
        $xml = trim($xml);
        if ($xml === '') {
            return [];
        }

        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($feed === false) {
            return [];
        }

        $items = [];
        if (isset($feed->channel->item)) {
            foreach ($feed->channel->item as $item) {
                $items[] = [
                    'title' => (string) ($item->title ?? ''),
                    'link' => (string) ($item->link ?? ''),
                    'description' => strip_tags((string) ($item->description ?? '')),
                    'published_ts' => strtotime((string) ($item->pubDate ?? '')) ?: 0,
                ];
            }
            return $items;
        }

        if (isset($feed->entry)) {
            foreach ($feed->entry as $entry) {
                $link = '';
                if (isset($entry->link)) {
                    foreach ($entry->link as $linkNode) {
                        $attr = $linkNode->attributes();
                        if (isset($attr['href'])) {
                            $link = (string) $attr['href'];
                            break;
                        }
                    }
                }
                $items[] = [
                    'title' => (string) ($entry->title ?? ''),
                    'link' => $link,
                    'description' => strip_tags((string) ($entry->summary ?? $entry->content ?? '')),
                    'published_ts' => strtotime((string) ($entry->updated ?? $entry->published ?? '')) ?: 0,
                ];
            }
            return $items;
        }

        return [];
    }

    /**
     * Checks if text contains any keyword.
     *
     * @param string $text
     * @param array  $keywords
     * @return bool
     */
    private function containsAnyKeyword(string $text, array $keywords, bool $trackStats = false): bool
    {
        $matched = false;
        foreach ($keywords as $keyword) {
            if (str_contains($text, mb_strtolower((string) $keyword))) {
                $matched = true;
                if ($trackStats) {
                    $this->keywordHits[$keyword] = ($this->keywordHits[$keyword] ?? 0) + 1;
                } else {
                    return true;
                }
            }
        }
        return $matched;
    }

    /** @var array<string,int> Keyword match counts for the current run */
    private array $keywordHits = [];

    /**
     * Save keyword performance stats after a scraper run.
     * Merges current run hits into the cumulative stats stored in settings.
     */
    private function saveKeywordStats(): void
    {
        if (empty($this->keywordHits)) {
            return;
        }
        $existing = json_decode((string) (Setting::get('scraper_keyword_stats') ?? '{}'), true) ?: [];
        foreach ($this->keywordHits as $kw => $count) {
            $existing[$kw] = ($existing[$kw] ?? 0) + $count;
        }
        // Keep only top 500 keywords by hit count to avoid unbounded growth
        arsort($existing);
        $existing = array_slice($existing, 0, 500, true);
        Setting::set('scraper_keyword_stats', json_encode($existing));
        $this->keywordHits = [];
    }

    /**
     * Auto-learn new keywords from recently scraped knowledge items.
     * Extracts topics, keywords, and entities from AI-analyzed content,
     * then adds high-frequency new terms to the scraper keyword list.
     *
     * @param int $runId
     * @return int Number of new keywords learned
     */
    private function learnKeywordsFromContent(int $runId): int
    {
        try {
            // Get topics/keywords/entities from items created in the last 2 hours
            $rows = Database::query(
                "SELECT topics, keywords, entities FROM knowledge_items WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)"
            );

            if (!$rows) {
                return 0;
            }

            // Collect all terms with frequency counts
            $termCounts = [];
            foreach ($rows as $row) {
                $allTerms = [];
                foreach (['topics', 'keywords', 'entities'] as $field) {
                    $decoded = json_decode((string) ($row[$field] ?? '[]'), true);
                    if (!is_array($decoded)) {
                        continue;
                    }
                    foreach ($decoded as $item) {
                        if (is_string($item)) {
                            $allTerms[] = mb_strtolower(trim($item));
                        } elseif (is_array($item)) {
                            // Handle nested objects like {"name": "React", "type": "tool"}
                            $name = (string) ($item['name'] ?? $item[0] ?? '');
                            if ($name !== '') {
                                $allTerms[] = mb_strtolower(trim($name));
                            }
                        }
                    }
                }

                foreach ($allTerms as $term) {
                    if (mb_strlen($term) < 2 || mb_strlen($term) > 50) {
                        continue;
                    }
                    $termCounts[$term] = ($termCounts[$term] ?? 0) + 1;
                }
            }

            if (empty($termCounts)) {
                return 0;
            }

            // Get current keywords
            $currentKeywords = $this->getScraperKeywords();
            $currentSet = array_flip(array_map('mb_strtolower', $currentKeywords));

            // Filter: must appear in 2+ items and not already be a keyword
            $newTerms = [];
            foreach ($termCounts as $term => $count) {
                if ($count >= 2 && !isset($currentSet[$term])) {
                    $newTerms[$term] = $count;
                }
            }

            if (empty($newTerms)) {
                return 0;
            }

            // Sort by frequency descending, take top 20 per run
            arsort($newTerms);
            $toAdd = array_slice(array_keys($newTerms), 0, 20);

            // Merge and save
            $updatedKeywords = array_merge($currentKeywords, $toAdd);
            Setting::set('scraper_keywords', json_encode($updatedKeywords));

            $this->automation->log($runId, 'scraper', 'info', 'keywords_learned', 'Auto-learned new keywords from content', [
                'new_keywords' => $toAdd,
                'total_keywords' => count($updatedKeywords),
            ]);

            return count($toAdd);
        } catch (Throwable $e) {
            $this->automation->log($runId, 'scraper', 'warning', 'keyword_learn_fail', 'Keyword auto-learn failed', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Decodes JSON from raw AI text (supports fenced blocks).
     *
     * @param string $text
     * @return array|null
     */
    private function decodeJsonFromText(string $text): ?array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        // Strip markdown code fences
        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```[a-zA-Z]*\s*/', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;
            $trimmed = trim($trimmed);
        }

        // Attempt 1: direct decode
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Attempt 2: extract JSON object from surrounding text
        $first = strpos($trimmed, '{');
        $last = strrpos($trimmed, '}');
        if ($first !== false && $last !== false && $last > $first) {
            $jsonStr = substr($trimmed, $first, $last - $first + 1);
            $decoded = json_decode($jsonStr, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Attempt 3: fix common AI JSON issues
        $fixable = $jsonStr ?? $trimmed;
        // Remove trailing commas before } or ]
        $fixable = preg_replace('/,\s*([\]}])/', '$1', $fixable) ?? $fixable;
        // Fix unescaped newlines inside string values
        $fixable = $this->fixJsonUnescapedNewlines($fixable);
        $decoded = json_decode($fixable, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Attempt 4: try fixing with a more aggressive cleanup
        // Some AI models output literal tab/newline in content strings
        $fixable2 = $jsonStr ?? $trimmed;
        // Replace literal control characters inside strings
        $fixable2 = preg_replace_callback('/"(?:[^"\\\\]|\\\\.)*"/s', static function (array $m): string {
            $val = $m[0];
            // Escape unescaped control chars inside the string value
            $inner = substr($val, 1, -1);
            $inner = str_replace(["\r\n", "\r", "\n", "\t"], ["\\n", "\\n", "\\n", "\\t"], $inner);
            return '"' . $inner . '"';
        }, $fixable2) ?? $fixable2;
        // Remove trailing commas again after fix
        $fixable2 = preg_replace('/,\s*([\]}])/', '$1', $fixable2) ?? $fixable2;
        $decoded = json_decode($fixable2, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    /**
     * Fixes unescaped newlines inside JSON string values.
     */
    private function fixJsonUnescapedNewlines(string $json): string
    {
        $result = '';
        $inString = false;
        $escaped = false;
        $len = strlen($json);

        for ($i = 0; $i < $len; $i++) {
            $ch = $json[$i];

            if ($escaped) {
                $result .= $ch;
                $escaped = false;
                continue;
            }

            if ($ch === '\\') {
                $result .= $ch;
                $escaped = true;
                continue;
            }

            if ($ch === '"') {
                $inString = !$inString;
                $result .= $ch;
                continue;
            }

            if ($inString) {
                if ($ch === "\n") {
                    $result .= '\\n';
                    continue;
                }
                if ($ch === "\r") {
                    continue;
                }
                if ($ch === "\t") {
                    $result .= '\\t';
                    continue;
                }
            }

            $result .= $ch;
        }

        return $result;
    }

    /**
     * Maps estimated volume label to numeric value.
     *
     * @param string $label
     * @return int
     */
    private function mapEstimatedVolume(string $label): int
    {
        return match ($label) {
            'high' => 80,
            'low' => 20,
            default => 50,
        };
    }

    /**
     * Maps estimated volume label to roadmap priority.
     *
     * @param string $label
     * @return int
     */
    private function mapPriorityFromVolume(string $label): int
    {
        return match ($label) {
            'high' => 80,
            'low' => 30,
            default => 50,
        };
    }

    /**
     * Creates a URL-safe slug.
     *
     * @param string $text
     * @return string
     */
    private function sanitizeSlug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }

    /**
     * Builds context arrays for writer prompt.
     *
     * @param array $roadmap
     * @return array
     */
    private function buildWriterContext(array $roadmap): array
    {
        $knowledgeIds = json_decode((string) ($roadmap['knowledge_item_ids'] ?? '[]'), true);
        if (!is_array($knowledgeIds)) {
            $knowledgeIds = [];
        }

        $knowledgeItems = KnowledgeItem::getByIds($knowledgeIds);
        $recentArticles = Article::getRecent(20);

        return [
            'knowledge_items' => $knowledgeItems,
            'recent_articles' => $recentArticles,
            'site_profile' => [
                'site_name' => (string) (Setting::get('site_name') ?? 'DevLync'),
                'domain' => SITE_DOMAIN,
                'niche' => SITE_NICHE,
            ],
        ];
    }

    /**
     * Generates article payload via AI with fallback template.
     *
     * @param array $roadmap
     * @param array $context
     * @param int   $runId
     * @return array
     */
    private function generateArticlePayload(array $roadmap, array $context, int $runId): array
    {
        $title = (string) ($roadmap['title'] ?? '');
        $keyword = (string) ($roadmap['primary_keyword'] ?? '');
        $type = (string) ($roadmap['content_type'] ?? 'blog');
        $intent = (string) ($roadmap['search_intent'] ?? 'informational');
        $siteName = (string) ($context['site_profile']['site_name'] ?? 'DevLync');
        $siteNiche = (string) ($context['site_profile']['niche'] ?? 'Developer Tools Discovery & Reviews');

        $contextText = '';
        foreach ($context['knowledge_items'] as $item) {
            $contextText .= "- {$item['title']}: " . mb_substr((string) ($item['summary'] ?? ''), 0, 300) . "\n";
        }
        $existingText = '';
        foreach ($context['recent_articles'] as $article) {
            $prefix = $this->contentTypePrefix((string) ($article['content_type'] ?? 'blog'));
            $existingText .= "- {$article['title']} (/{$prefix}/{$article['slug']})\n";
        }

        // Content type-specific instructions
        $typeInstructions = match ($type) {
            'review' => <<<'REVIEW'
CONTENT TYPE: In-Depth Review
- Provide hands-on analysis as if you personally tested the tool
- Include a clear verdict with overall rating (1-10 scale)
- List specific pros and cons with real examples
- Compare pricing tiers if applicable
- Include "Who is this for?" and "Who should skip it?" sections
- Add prosAndCons object with "pros" and "cons" arrays
- Set overallRating (float, 1-10)
REVIEW,
            'comparison' => <<<'COMPARISON'
CONTENT TYPE: Head-to-Head Comparison
- Compare tools side-by-side with a structured comparison table
- Be fair and balanced — acknowledge strengths of each tool
- Include a comparisonTable array of objects with feature, tool1, tool2 keys
- Declare a clear winner with reasoning (winnerProduct, winnerReason)
- Help reader decide based on their specific use case
COMPARISON,
            'news' => <<<'NEWS'
CONTENT TYPE: Tech News / Analysis
- Lead with the most important facts (inverted pyramid)
- Include key facts, timeline, and industry impact
- Add expert opinions or community reactions where relevant
- Keep tone informative and timely
- Include keyFacts array and industryImpact string
NEWS,
            default => <<<'BLOG'
CONTENT TYPE: Blog / Guide / Tutorial
- Write an actionable, comprehensive guide
- Include step-by-step instructions where applicable
- Add code snippets or configuration examples if relevant
- Make it immediately useful to the reader
BLOG,
        };

        $systemPrompt = <<<SYSTEM
You are a senior content writer for {$siteName}, a publication focused on {$siteNiche}.

=== VOICE & TONE ===
- Write like a knowledgeable developer friend — authoritative yet conversational
- Use "you" to address the reader directly
- Be opinionated and take clear stances — avoid wishy-washy "it depends" conclusions
- Use concrete examples, numbers, and specifics — never vague generalities
- Keep sentences punchy. Mix short and medium-length sentences for rhythm
- No corporate buzzwords, no filler phrases like "In today's fast-paced world"
- No clichés like "game-changer", "revolutionary", "cutting-edge", "seamless"

=== ANTI-PLAGIARISM & ORIGINALITY ===
- Write 100% original content. Do NOT copy or closely paraphrase any source
- Synthesize information from the provided knowledge context into your own analysis
- Add unique insights, opinions, and practical recommendations
- If referencing facts or data, restate them in your own words with attribution
- Every sentence must pass plagiarism detection tools (Copyscape, Grammarly)

=== QUALITY STANDARDS ===
- Minimum 1500 words for blog/review/comparison, 800 words for news
- Every claim should be backed by reasoning or evidence
- Include practical, actionable advice — not just descriptions
- Use proper Markdown: ## for H2, ### for H3, **bold** for emphasis, `code` for technical terms
- Include 4-8 H2 sections minimum with descriptive headings (not generic like "Introduction")
- Write a compelling hook in the first paragraph that makes the reader want to continue
- End with a clear, decisive conclusion

=== SEO REQUIREMENTS ===
- Focus keyword must appear in: title, first 100 words, one H2, meta description, slug
- Use focus keyword naturally 3-5 times (no keyword stuffing)
- Include 3-5 related/semantic keywords throughout the content
- Meta title: 50-60 characters, compelling and click-worthy
- Meta description: 150-160 characters, includes keyword and a clear value proposition
- Use descriptive alt text for image prompts
- Suggest 2-3 internal links from the existing articles list where relevant

=== FORMATTING ===
- Use bullet points and numbered lists for scannable content
- Include a table of contents hint via clear H2 structure
- Add a "Key Takeaways" or "TL;DR" section near the top
- Include an FAQ section with 3-5 questions real developers would ask

Return response as a single valid JSON object. No markdown fences. No explanation outside JSON.
SYSTEM;

        $userPrompt = <<<USER
Write a {$type} article for {$siteName}.

TOPIC: {$title}
FOCUS KEYWORD: {$keyword}
SEARCH INTENT: {$intent}

{$typeInstructions}

=== KNOWLEDGE CONTEXT (use as research, do NOT copy) ===
{$contextText}

=== EXISTING ARTICLES (avoid duplicate topics, suggest internal links) ===
{$existingText}

=== REQUIRED JSON STRUCTURE ===
{
  "title": "Compelling, keyword-rich title (50-65 chars)",
  "slug": "url-friendly-slug",
  "metaTitle": "SEO meta title (50-60 chars)",
  "metaDescription": "Compelling meta description with keyword (150-160 chars)",
  "excerpt": "2-3 sentence article summary for cards/previews",
  "focusKeyword": "{$keyword}",
  "content": "Full article in Markdown (## H2, ### H3, **bold**, `code`, lists, tables)",
  "directAnswer": "1-2 sentence direct answer for featured snippet / People Also Ask",
  "keyTakeaways": ["Takeaway 1", "Takeaway 2", "Takeaway 3", "Takeaway 4"],
  "faq": [
    {"question": "Real question developers ask?", "answer": "Concise, helpful answer"},
    {"question": "Another question?", "answer": "Another answer"}
  ],
  "tags": ["tag1", "tag2", "tag3"],
  "seoScore": 85,
  "wordCount": 1800,
  "internalLinks": [{"slug": "existing-article-slug", "anchorText": "descriptive anchor"}],
  "sources": [{"title": "Source name", "url": "https://..."}],
  "imagePrompts": [{"prompt": "Detailed image description for AI generation, professional style, no text"}],
  "overallRating": null,
  "prosAndCons": {"pros": [], "cons": []},
  "comparisonTable": [],
  "winnerProduct": "",
  "winnerReason": "",
  "keyFacts": [],
  "industryImpact": "",
  "expertOpinions": []
}
USER;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $ai = $this->automation->callAiWithFallback('writer', $messages, [
            'run_id' => $runId,
            'json_mode' => true,
            'temperature' => 0.7,
            'max_tokens' => 16000,
            'timeout' => 120,
        ]);

        $module = 'writer_' . $type;
        $payload = [];
        if ($ai['success'] ?? false) {
            $payload = $this->decodeJsonFromText((string) ($ai['content'] ?? '')) ?? [];
            if (!$payload) {
                $this->automation->log($runId, $module, 'warning', 'ai_json_parse_fail', 'AI returned content but JSON parse failed', [
                    'provider' => $ai['provider'] ?? 'unknown',
                    'content_length' => mb_strlen((string) ($ai['content'] ?? '')),
                    'content_preview' => mb_substr((string) ($ai['content'] ?? ''), 0, 500),
                ]);
            }
        } else {
            $this->automation->log($runId, $module, 'warning', 'ai_call_failed', 'AI writer call failed — using fallback template', [
                'error' => $ai['error'] ?? 'unknown',
                'provider' => $ai['provider'] ?? 'none',
            ]);
        }

        if (!$payload) {
            $payload = $this->buildFallbackArticlePayload($roadmap, $context);
        }

        return [
            'article_payload' => $payload,
            'ai_success' => (bool) ($ai['success'] ?? false),
            'provider' => (string) ($ai['provider'] ?? 'template'),
            'model' => (string) ($ai['model'] ?? 'template'),
            'cost_usd' => (float) ($ai['cost_usd'] ?? 0),
            'usage' => $ai['usage'] ?? ['input_tokens' => 0, 'output_tokens' => 0],
        ];
    }

    /**
     * Runs SEO review pass.
     *
     * @param array  $payload
     * @param string $keyword
     * @param int    $runId
     * @return array
     */
    private function runSeoReview(array $payload, string $keyword, int $runId): array
    {
        $title = (string) ($payload['title'] ?? '');
        $metaTitle = (string) ($payload['metaTitle'] ?? '');
        $metaDesc = (string) ($payload['metaDescription'] ?? '');
        $excerpt = (string) ($payload['excerpt'] ?? '');
        $directAnswer = (string) ($payload['directAnswer'] ?? '');
        $contentPreview = mb_substr((string) ($payload['content'] ?? ''), 0, 2000);

        $seoSystemPrompt = <<<'SEO'
You are an expert SEO editor specializing in tech content. Your job is to review and improve article metadata for maximum search visibility and click-through rate.

EVALUATION CRITERIA:
1. Title Tag: 50-60 chars, keyword near front, compelling, not clickbait
2. Meta Description: 150-160 chars, includes keyword, clear value prop, call-to-action
3. Direct Answer: Concise 1-2 sentence answer optimized for featured snippets / People Also Ask
4. Excerpt: 2-3 sentences that hook the reader and include the keyword
5. Keyword Usage: Natural placement in first 100 words, H2 headings, throughout content
6. Content Quality: Check for filler, clichés, vague statements

SCORING (0-100):
- 90+: Exceptional — publish-ready, strong keyword placement, compelling meta
- 70-89: Good — minor improvements possible
- 50-69: Needs work — missing keyword placement, weak meta, thin content
- Below 50: Major issues — rewrite recommended

Return strict JSON only. No explanation outside JSON.
SEO;

        $seoUserPrompt = <<<USER
Review this article and return improved SEO metadata.

FOCUS KEYWORD: {$keyword}
CURRENT TITLE: {$title}
CURRENT META TITLE: {$metaTitle}
CURRENT META DESC: {$metaDesc}
CURRENT EXCERPT: {$excerpt}
CURRENT DIRECT ANSWER: {$directAnswer}

CONTENT PREVIEW:
{$contentPreview}

Return JSON:
{
  "seoScore": 85,
  "issues": ["Issue 1", "Issue 2"],
  "improvedFields": {
    "metaTitle": "Improved meta title (50-60 chars) or null if already good",
    "metaDescription": "Improved meta description (150-160 chars) or null",
    "directAnswer": "Improved featured snippet answer or null",
    "excerpt": "Improved excerpt or null"
  }
}
USER;

        $messages = [
            ['role' => 'system', 'content' => $seoSystemPrompt],
            ['role' => 'user', 'content' => $seoUserPrompt],
        ];

        $ai = $this->automation->callAiWithFallback('seo_review', $messages, [
            'run_id' => $runId,
            'json_mode' => true,
            'temperature' => 0.3,
            'max_tokens' => 2000,
            'timeout' => 60,
        ]);

        if (!($ai['success'] ?? false)) {
            return ['success' => false, 'cost_usd' => 0.0, 'usage' => ['input_tokens' => 0, 'output_tokens' => 0]];
        }

        return [
            'success' => true,
            'review' => $this->decodeJsonFromText((string) ($ai['content'] ?? '')) ?? [],
            'provider' => (string) ($ai['provider'] ?? ''),
            'model' => (string) ($ai['model'] ?? ''),
            'cost_usd' => (float) ($ai['cost_usd'] ?? 0),
            'usage' => $ai['usage'] ?? ['input_tokens' => 0, 'output_tokens' => 0],
        ];
    }

    /**
     * Applies SEO review improvements to payload.
     *
     * @param array $payload
     * @param array $seoReview
     * @return array
     */
    private function mergeSeoReview(array $payload, array $seoReview): array
    {
        if (!($seoReview['success'] ?? false)) {
            return $payload;
        }

        $review = $seoReview['review'] ?? [];
        if (isset($review['seoScore'])) {
            $payload['seoScore'] = (int) $review['seoScore'];
        }

        $improved = is_array($review['improvedFields'] ?? null) ? $review['improvedFields'] : [];
        foreach (['metaTitle', 'metaDescription', 'directAnswer', 'excerpt'] as $field) {
            if (!empty($improved[$field])) {
                $payload[$field] = (string) $improved[$field];
            }
        }

        return $payload;
    }

    /**
     * Ensures article slug is unique.
     *
     * @param string $slug
     * @return string
     */
    private function ensureUniqueSlug(string $slug): string
    {
        $candidate = $slug !== '' ? $slug : 'article-' . date('Ymd-His');
        $db = Database::getInstance();
        $base = $candidate;
        $i = 1;

        while ($db->queryOne('SELECT id FROM articles WHERE slug = ? LIMIT 1', [$candidate])) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    /**
     * Saves article payload to articles table.
     *
     * @param array $payload
     * @param array $roadmap
     * @param array $generateResult
     * @param array $seoReview
     * @return int
     */
    private function saveArticleFromPayload(array $payload, array $roadmap, array $generateResult, array $seoReview): int
    {
        $type = (string) ($roadmap['content_type'] ?? 'blog');
        $status = 'review';
        $wordCount = max(1, str_word_count(strip_tags((string) ($payload['content'] ?? ''))));
        $readingTime = max(1, (int) ceil($wordCount / 220));
        $totalCost = (float) ($generateResult['cost_usd'] ?? 0) + (float) ($seoReview['cost_usd'] ?? 0);
        $authorId = max(1, (int) (Setting::get('automation_default_author_id') ?? '1'));

        $articleData = [
            'title' => (string) ($payload['title'] ?? $roadmap['title']),
            'slug' => (string) ($payload['slug'] ?? ''),
            'content_type' => $type,
            'content' => (string) ($payload['content'] ?? ''),
            'excerpt' => (string) ($payload['excerpt'] ?? ''),
            'meta_title' => (string) ($payload['metaTitle'] ?? $roadmap['title']),
            'meta_description' => (string) ($payload['metaDescription'] ?? ''),
            'focus_keyword' => (string) ($payload['focusKeyword'] ?? $roadmap['primary_keyword']),
            'secondary_keywords' => [],
            'search_intent' => (string) ($roadmap['search_intent'] ?? 'informational'),
            'seo_score' => (int) ($payload['seoScore'] ?? 0),
            'schema_type' => $type === 'review' ? 'Review' : 'Article',
            'word_count' => $wordCount,
            'reading_time' => $readingTime,
            'direct_answer' => (string) ($payload['directAnswer'] ?? ''),
            'key_takeaways' => is_array($payload['keyTakeaways'] ?? null) ? $payload['keyTakeaways'] : [],
            'faq' => is_array($payload['faq'] ?? null) ? $payload['faq'] : [],
            'internal_links' => is_array($payload['internalLinks'] ?? null) ? $payload['internalLinks'] : [],
            'sources' => is_array($payload['sources'] ?? null) ? $payload['sources'] : [],
            'overall_rating' => isset($payload['overallRating']) ? (float) $payload['overallRating'] : null,
            'pros' => is_array($payload['prosAndCons']['pros'] ?? null) ? $payload['prosAndCons']['pros'] : [],
            'cons' => is_array($payload['prosAndCons']['cons'] ?? null) ? $payload['prosAndCons']['cons'] : [],
            'comparison_table' => is_array($payload['comparisonTable'] ?? null) ? $payload['comparisonTable'] : [],
            'winner_product' => (string) ($payload['winnerProduct'] ?? ''),
            'winner_reason' => (string) ($payload['winnerReason'] ?? ''),
            'key_facts' => is_array($payload['keyFacts'] ?? null) ? $payload['keyFacts'] : [],
            'industry_impact' => (string) ($payload['industryImpact'] ?? ''),
            'expert_opinions' => is_array($payload['expertOpinions'] ?? null) ? $payload['expertOpinions'] : [],
            'featured_image_url' => (string) ($payload['featured_image_url'] ?? ''),
            'featured_image_alt' => (string) ($payload['featured_image_alt'] ?? ''),
            'featured_image_prompt' => (string) ($payload['imagePrompts'][0]['prompt'] ?? ''),
            'author_id' => $authorId,
            'category_id' => !empty($roadmap['category_id']) ? (int) $roadmap['category_id'] : null,
            'roadmap_item_id' => (int) $roadmap['id'],
            'status' => $status,
            'published_at' => null,
            'generation_cost' => $totalCost,
            'model_used' => (string) ($generateResult['provider'] ?? '') . '/' . (string) ($generateResult['model'] ?? ''),
        ];

        $articleId = Article::create($articleData);

        CostRecord::create([
            'article_id' => $articleId,
            'step' => 'generate',
            'model' => (string) ($generateResult['model'] ?? ''),
            'provider' => (string) ($generateResult['provider'] ?? ''),
            'input_tokens' => (int) (($generateResult['usage']['input_tokens'] ?? 0)),
            'output_tokens' => (int) (($generateResult['usage']['output_tokens'] ?? 0)),
            'cost' => (float) ($generateResult['cost_usd'] ?? 0),
        ]);

        if (($seoReview['success'] ?? false) && !empty($seoReview['provider'])) {
            CostRecord::create([
                'article_id' => $articleId,
                'step' => 'seo_review',
                'model' => (string) ($seoReview['model'] ?? ''),
                'provider' => (string) ($seoReview['provider'] ?? ''),
                'input_tokens' => (int) (($seoReview['usage']['input_tokens'] ?? 0)),
                'output_tokens' => (int) (($seoReview['usage']['output_tokens'] ?? 0)),
                'cost' => (float) ($seoReview['cost_usd'] ?? 0),
            ]);
        }

        return $articleId;
    }

    /**
     * Validates generated article quality before publish flow.
     *
     * @param int   $articleId
     * @param array $roadmap
     * @param array $payload
     * @return array{passed: bool, issues: array, metrics: array}
     */
    private function qualityGate(int $articleId, array $roadmap, array $payload): array
    {
        $article = Article::getById($articleId) ?? [];
        $type = (string) ($roadmap['content_type'] ?? 'blog');
        $minWordCount = $type === 'news' ? 800 : 1500;

        $title = trim((string) ($article['title'] ?? $payload['title'] ?? ''));
        $excerpt = trim((string) ($article['excerpt'] ?? $payload['excerpt'] ?? ''));
        $metaDescription = trim((string) ($article['meta_description'] ?? $payload['metaDescription'] ?? ''));
        $content = (string) ($article['content'] ?? $payload['content'] ?? '');
        $seoScore = (int) ($article['seo_score'] ?? $payload['seoScore'] ?? 0);
        $wordCount = (int) ($article['word_count'] ?? 0);
        if ($wordCount <= 0) {
            $wordCount = max(0, str_word_count(strip_tags($content)));
        }

        $h2Count = preg_match_all('/<h2\b[^>]*>/i', $content, $tmp);
        $h2Count = $h2Count === false ? 0 : $h2Count;
        $hasUnclosedTags = $this->hasUnclosedHtmlTags($content);

        $issues = [];
        if ($wordCount < $minWordCount) {
            $issues[] = "Word count {$wordCount} below minimum {$minWordCount}";
        }
        if ($title === '' || $excerpt === '' || $metaDescription === '' || trim(strip_tags($content)) === '') {
            $issues[] = 'Missing required fields (title, excerpt, meta description, or content)';
        }
        if ($seoScore < 50) {
            $issues[] = "SEO score {$seoScore} below minimum 50";
        }
        if ($h2Count < 3) {
            $issues[] = "Only {$h2Count} H2 sections found (minimum 3)";
        }
        if ($hasUnclosedTags) {
            $issues[] = 'Detected unclosed or mismatched HTML tags';
        }

        $combinedText = implode("\n", [$title, $excerpt, $metaDescription, strip_tags($content)]);
        $placeholderPatterns = [
            '/\[\s*todo\s*\]/i' => '[TODO]',
            '/lorem ipsum/i' => 'Lorem ipsum',
            '/\{\{\s*[^}]+\s*\}\}/' => '{{variable}}',
        ];
        foreach ($placeholderPatterns as $pattern => $label) {
            if (preg_match($pattern, $combinedText)) {
                $issues[] = "Placeholder text detected: {$label}";
                break;
            }
        }

        return [
            'passed' => empty($issues),
            'issues' => $issues,
            'metrics' => [
                'content_type' => $type,
                'word_count' => $wordCount,
                'min_word_count' => $minWordCount,
                'seo_score' => $seoScore,
                'h2_count' => $h2Count,
                'has_unclosed_html_tags' => $hasUnclosedTags,
            ],
        ];
    }

    /**
     * Detects whether HTML contains unclosed or mismatched tags.
     *
     * @param string $html
     * @return bool
     */
    private function hasUnclosedHtmlTags(string $html): bool
    {
        if (trim($html) === '') {
            return false;
        }

        $voidTags = [
            'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
            'link', 'meta', 'param', 'source', 'track', 'wbr',
        ];
        $stack = [];

        if (!preg_match_all('/<\s*(\/?)([a-zA-Z][a-zA-Z0-9]*)\b[^>]*?(\/?)\s*>/', $html, $matches, PREG_SET_ORDER)) {
            return false;
        }

        foreach ($matches as $match) {
            $isClosing = $match[1] === '/';
            $tag = strtolower((string) ($match[2] ?? ''));
            $isSelfClosing = ($match[3] ?? '') === '/' || in_array($tag, $voidTags, true);

            if ($tag === '') {
                continue;
            }

            if ($isClosing) {
                if (!$stack) {
                    return true;
                }
                $openTag = array_pop($stack);
                if ($openTag !== $tag) {
                    return true;
                }
                continue;
            }

            if (!$isSelfClosing) {
                $stack[] = $tag;
            }
        }

        return !empty($stack);
    }

    /**
     * Post-processes article after initial insert.
     *
     * @param int        $articleId
     * @param array      $payload
     * @param array      $roadmap
     * @param array|null $image
     * @return void
     */
    private function postProcessArticle(int $articleId, array $payload, array $roadmap, ?array $image): void
    {
        if (!empty($payload['tags']) && is_array($payload['tags'])) {
            $tagIds = [];
            foreach ($payload['tags'] as $tagName) {
                $tagName = trim((string) $tagName);
                if ($tagName !== '') {
                    $tagIds[] = Tag::findOrCreate($tagName);
                }
            }
            if ($tagIds) {
                Tag::syncForArticle($articleId, $tagIds);
            }
        }

        // Auto-discover brands from article payload and create pending affiliate entries
        try {
            $brands = AffiliateLink::autoDiscoverBrands($payload, $roadmap);
            if ($brands) {
                error_log("[AutomationRunner] Auto-discovered brands for article {$articleId}: " . implode(', ', $brands));
            }
        } catch (Throwable $e) {
            error_log("[AutomationRunner] Brand auto-discovery failed for article {$articleId}: " . $e->getMessage());
        }

        $rawContent = (string) (Article::getById($articleId)['content'] ?? '');
        $contentForAffiliates = $rawContent;
        try {
            $fixedLinks = $this->fixInternalLinkPaths($rawContent);
            $contentForAffiliates = (string) ($fixedLinks['content'] ?? $rawContent);
        } catch (Throwable $e) {
            error_log("[AutomationRunner] Internal link fix failed for article {$articleId}: " . $e->getMessage());
        }

        try {
            $aff = AffiliateLink::processContent($contentForAffiliates, $articleId);
            Article::updateField($articleId, 'content', (string) ($aff['content'] ?? $contentForAffiliates));
            Article::updateField($articleId, 'has_affiliate_links', ((int) ($aff['linksAdded'] ?? 0) + (int) ($aff['linksPending'] ?? 0)) > 0 ? 1 : 0);
            Article::updateField($articleId, 'updated_content_at', date('Y-m-d H:i:s'));
        } catch (Throwable $e) {
            if ($contentForAffiliates !== $rawContent) {
                Article::updateField($articleId, 'content', $contentForAffiliates);
                Article::updateField($articleId, 'updated_content_at', date('Y-m-d H:i:s'));
            }
            error_log("[AutomationRunner] Affiliate injection failed for article {$articleId}: " . $e->getMessage());
        }

        if ($image !== null) {
            ImageLibrary::create([
                'filename' => basename((string) $image['path']),
                'filepath' => (string) $image['path'],
                'file_url' => (string) $image['url'],
                'file_size' => (int) (@filesize((string) $image['path']) ?: 0),
                'width' => (int) ($image['width'] ?? 0),
                'height' => (int) ($image['height'] ?? 0),
                'format' => 'webp',
                'source_type' => 'generated',
                'ai_prompt' => (string) ($payload['imagePrompts'][0]['prompt'] ?? ''),
                'alt_text' => (string) ($payload['featured_image_alt'] ?? ''),
                'article_id' => $articleId,
                'image_slot' => 'featured',
                'placement' => 'header',
                'status' => 'ready',
            ]);
        }

        // Queue downstream modules only for published articles.
        $article = Article::getById($articleId);
        if ($article && (string) ($article['status'] ?? '') === 'published') {
            try {
                $this->enqueueSocialQueue($articleId, $article);
                $articleUrl = $this->buildArticleUrlFromRecord($article);
                if ($articleUrl !== '') {
                    $this->enqueueIndexerUrl($articleUrl);
                }
            } catch (Throwable) {
                // Keep publish flow resilient if queue writes fail.
            }
        }
    }

    /**
     * Corrects wrong internal URL prefixes (/reviews vs /comparisons etc.) based on known slug->type map.
     *
     * @param string $content
     * @return array{content: string, fixed: int}
     */
    private function fixInternalLinkPaths(string $content): array
    {
        if ($content === '' || stripos($content, 'href=') === false) {
            return ['content' => $content, 'fixed' => 0];
        }

        $rows = Database::getInstance()->query(
            "SELECT slug, content_type
             FROM articles
             WHERE status = 'published'
               AND slug IS NOT NULL
               AND slug != ''"
        );
        if (!$rows) {
            return ['content' => $content, 'fixed' => 0];
        }

        $slugMap = [];
        foreach ($rows as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $slugMap[strtolower($slug)] = [
                'slug' => $slug,
                'prefix' => $this->contentTypePrefix((string) ($row['content_type'] ?? 'blog')),
            ];
        }
        if (!$slugMap) {
            return ['content' => $content, 'fixed' => 0];
        }

        $siteHost = strtolower((string) (parse_url((string) SITE_URL, PHP_URL_HOST) ?? ''));
        $fixed = 0;
        $updated = preg_replace_callback(
            '/<a\b([^>]*?)href=(["\'])([^"\']+)\2([^>]*)>/i',
            function (array $matches) use ($slugMap, $siteHost, &$fixed): string {
                $href = trim(html_entity_decode((string) ($matches[3] ?? ''), ENT_QUOTES, 'UTF-8'));
                if (
                    $href === ''
                    || str_starts_with($href, '#')
                    || preg_match('#^(mailto:|tel:|javascript:)#i', $href)
                ) {
                    return $matches[0];
                }

                if (!preg_match('#^(https?://[^/]+)?/(blog|reviews|comparisons|news)/([^/?#]+)([?#].*)?$#i', $href, $parts)) {
                    return $matches[0];
                }

                $base = (string) ($parts[1] ?? '');
                if ($base !== '') {
                    $linkHost = strtolower((string) (parse_url($base, PHP_URL_HOST) ?? ''));
                    if ($siteHost !== '' && $linkHost !== '' && $linkHost !== $siteHost) {
                        return $matches[0];
                    }
                }

                $currentPrefix = strtolower((string) ($parts[2] ?? ''));
                $slugKey = strtolower(rawurldecode((string) ($parts[3] ?? '')));
                if (!isset($slugMap[$slugKey])) {
                    return $matches[0];
                }

                $target = $slugMap[$slugKey];
                $correctPrefix = (string) ($target['prefix'] ?? '');
                if ($correctPrefix === '' || $currentPrefix === $correctPrefix) {
                    return $matches[0];
                }

                $suffix = (string) ($parts[4] ?? '');
                $canonicalSlug = rawurlencode((string) ($target['slug'] ?? $slugKey));
                $newHref = ($base !== '' ? rtrim($base, '/') : '') . '/' . $correctPrefix . '/' . $canonicalSlug . $suffix;
                $fixed++;

                return '<a' . $matches[1] . 'href=' . $matches[2]
                    . htmlspecialchars($newHref, ENT_QUOTES, 'UTF-8')
                    . $matches[2] . $matches[4] . '>';
            },
            $content
        );

        return [
            'content' => $updated ?? $content,
            'fixed' => $fixed,
        ];
    }

    /**
     * Generates featured image with fallback chain: fal.ai → Gemini → DALL-E.
     * Tries each provider in order until one succeeds.
     *
     * @param string $slug
     * @param array  $payload
     * @param int    $runId
     * @return array|null
     */
    private function generateFeaturedImage(string $slug, array $payload, int $runId): ?array
    {
        $module = 'writer_' . ($payload['content_type'] ?? 'blog');

        // Check if image generation is enabled
        $enabled = Setting::get('image_generation_enabled');
        if ($enabled === 'false' || $enabled === '0') {
            $this->automation->log($runId, $module, 'info', 'image_skip', 'Image generation disabled in settings');
            return null;
        }

        $prompt = trim((string) ($payload['imagePrompts'][0]['prompt'] ?? ''));
        if ($prompt === '') {
            $prompt = 'Developer tools dashboard, modern workspace, coding atmosphere, no text';
        }

        // Apply image style from settings
        $style = (string) (Setting::get('image_style') ?? 'professional');
        $styleModifier = match ($style) {
            'vibrant' => ', vibrant colors, bold design, eye-catching',
            'minimal' => ', minimalist flat design, simple clean layout, white space',
            'photorealistic' => ', photorealistic, ultra-realistic photograph, studio lighting',
            'illustration' => ', digital illustration, artistic hand-drawn style, creative',
            default => ', professional clean design, modern aesthetic',
        };
        $fullPrompt = $prompt . $styleModifier . ', blog header image, 16:9 aspect ratio, no text, no watermark';

        // Build provider chain based on settings
        $providerSetting = (string) (Setting::get('image_provider') ?? 'auto');
        $quality = (string) (Setting::get('image_quality') ?? 'standard');
        $providers = [];

        if ($providerSetting === 'gemini' || $providerSetting === 'auto') {
            $providers['gemini'] = fn() => $this->generateImageViaGemini($fullPrompt);
        }
        if ($providerSetting === 'dalle' || $providerSetting === 'auto') {
            $providers['dalle'] = fn() => $this->generateImageViaDalle($fullPrompt, $quality);
        }

        if (empty($providers)) {
            $this->automation->log($runId, $module, 'warning', 'image_skip', 'No image providers configured');
            return null;
        }

        foreach ($providers as $providerName => $generator) {
            try {
                $imageUrl = $generator();
                if ($imageUrl !== null && $imageUrl !== '') {
                    $this->automation->log($runId, $module, 'info', 'image_generated', 'Image generated', [
                        'provider' => $providerName,
                        'style' => $style,
                    ]);
                    return $this->downloadAndSaveImage($imageUrl, $slug);
                }
                $this->automation->log($runId, $module, 'warning', 'image_provider_fail', "Image provider {$providerName} returned empty", [
                    'provider' => $providerName,
                ]);
            } catch (Throwable $e) {
                $this->automation->log($runId, $module, 'warning', 'image_provider_fail', "Image provider {$providerName} failed", [
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->automation->log($runId, $module, 'warning', 'image_skip', 'All image providers failed', [
            'tried' => array_keys($providers),
        ]);
        return null;
    }

    /**
     * Resolves an API key from settings table, then supervisor_settings as fallback.
     */
    private function resolveApiKey(string ...$keys): string
    {
        // Check settings table first
        foreach ($keys as $key) {
            $val = trim((string) (Setting::get($key) ?? ''));
            if ($val !== '') {
                return $val;
            }
        }
        // Check supervisor_settings as fallback
        $db = Database::getInstance();
        foreach ($keys as $key) {
            $row = $db->queryOne("SELECT setting_value FROM supervisor_settings WHERE setting_key = ? LIMIT 1", [$key]);
            $val = trim((string) ($row['setting_value'] ?? ''));
            if ($val !== '') {
                return $val;
            }
        }
        return '';
    }

    /**
     * Generate image via Gemini (gemini-2.0-flash-exp with image generation).
     */
    private function generateImageViaGemini(string $prompt): ?string
    {
        $apiKey = $this->resolveApiKey('gemini_api_key');
        if ($apiKey === '') {
            return null;
        }

        $response = HttpClient::postJson(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=' . $apiKey,
            [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => 'Generate an image based on this description. Return ONLY the image, no text: ' . $prompt]]],
                ],
                'generationConfig' => [
                    'responseModalities' => ['IMAGE', 'TEXT'],
                ],
            ],
            ['Content-Type: application/json'],
            60,
            ['retries' => 1, 'verify_ssl' => true]
        );

        if (!$response['success']) {
            return null;
        }

        // Extract inline image data from response
        $candidates = $response['json']['candidates'] ?? [];
        foreach ($candidates as $candidate) {
            $parts = $candidate['content']['parts'] ?? [];
            foreach ($parts as $part) {
                if (isset($part['inlineData']['data']) && isset($part['inlineData']['mimeType'])) {
                    $b64 = (string) $part['inlineData']['data'];
                    if ($b64 !== '') {
                        $ext = str_contains($part['inlineData']['mimeType'], 'png') ? '.png' : '.jpg';
                        $tmpPath = sys_get_temp_dir() . '/devlync_img_' . uniqid() . $ext;
                        file_put_contents($tmpPath, base64_decode($b64));
                        return $tmpPath;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Generate image via OpenAI DALL-E 3.
     */
    private function generateImageViaDalle(string $prompt, string $quality = 'standard'): ?string
    {
        $apiKey = $this->resolveApiKey('openai_api_key', 'chatgpt_api_key');
        if ($apiKey === '') {
            return null;
        }

        $response = HttpClient::postJson(
            'https://api.openai.com/v1/images/generations',
            [
                'model' => 'dall-e-3',
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1792x1024',
                'quality' => in_array($quality, ['standard', 'hd'], true) ? $quality : 'standard',
            ],
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            60,
            ['retries' => 1, 'verify_ssl' => true]
        );

        if (!$response['success']) {
            return null;
        }

        return (string) ($response['json']['data'][0]['url'] ?? '') ?: null;
    }

    /**
     * Downloads and stores image as webp.
     *
     * @param string $imageUrl
     * @param string $slug
     * @return array|null
     */
    private function downloadAndSaveImage(string $imageUrl, string $slug): ?array
    {
        $this->automation->ensureRuntimeDirectories();
        $targetDir = ROOT_PATH . '/uploads/images';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        // Handle local temp files (from Gemini base64 decode)
        $imageData = null;
        if (str_starts_with($imageUrl, '/') || str_starts_with($imageUrl, 'C:') || str_starts_with($imageUrl, sys_get_temp_dir())) {
            if (is_file($imageUrl)) {
                $imageData = file_get_contents($imageUrl);
                @unlink($imageUrl);
            }
        }

        if ($imageData === null) {
            $download = HttpClient::get($imageUrl, ['Accept: image/*'], 30, ['retries' => 1, 'verify_ssl' => true]);
            if (!$download['success'] || empty($download['body'])) {
                return null;
            }
            $imageData = (string) $download['body'];
        }

        if (empty($imageData)) {
            return null;
        }

        $filename = $slug . '-featured.webp';
        $path = $targetDir . '/' . $filename;
        $width = 0;
        $height = 0;

        if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
            $im = @imagecreatefromstring($imageData);
            if ($im !== false) {
                $width = imagesx($im);
                $height = imagesy($im);
                imagewebp($im, $path, 85);
                imagedestroy($im);
            } else {
                file_put_contents($path, $imageData);
            }
        } else {
            file_put_contents($path, $imageData);
        }

        $url = url('/uploads/images/' . $filename);
        return [
            'path' => $path,
            'url' => $url,
            'alt' => ucwords(str_replace('-', ' ', $slug)) . ' featured image',
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Converts markdown to HTML.
     *
     * @param string $markdown
     * @return string
     */
    private function renderMarkdownToHtml(string $markdown): string
    {
        // Strip FAQ section from content (it's rendered separately via structured faq field)
        $markdown = preg_replace('/^#{1,3}\s*(?:FAQ|Frequently Asked Questions).*$/ms', '', $markdown) ?? $markdown;
        $markdown = trim($markdown);

        if (class_exists('\League\CommonMark\CommonMarkConverter')) {
            $converter = new \League\CommonMark\CommonMarkConverter();
            return (string) $converter->convert($markdown);
        }

        // Robust fallback markdown-to-HTML parser
        $lines = explode("\n", $markdown);
        $html = '';
        $inList = false;      // 'ul' or 'ol' or false
        $inCodeBlock = false;
        $codeContent = '';
        $inTable = false;
        $tableRows = [];

        $flushList = function () use (&$html, &$inList) {
            if ($inList) {
                $html .= "</{$inList}>\n";
                $inList = false;
            }
        };

        $flushTable = function () use (&$html, &$inTable, &$tableRows) {
            if (!$inTable || empty($tableRows)) {
                $inTable = false;
                $tableRows = [];
                return;
            }
            $html .= '<div class="overflow-x-auto my-6"><table class="min-w-full border border-gray-200 rounded-lg text-sm">' . "\n";
            foreach ($tableRows as $i => $cells) {
                if ($i === 0) {
                    $html .= '<thead class="bg-gray-100"><tr>';
                    foreach ($cells as $cell) {
                        $html .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 border-b border-gray-200">' . trim($cell) . '</th>';
                    }
                    $html .= "</tr></thead>\n<tbody>\n";
                } else {
                    $rowClass = $i % 2 === 0 ? ' class="bg-gray-50"' : '';
                    $html .= "<tr{$rowClass}>";
                    foreach ($cells as $cell) {
                        $html .= '<td class="px-4 py-3 border-b border-gray-100">' . trim($cell) . '</td>';
                    }
                    $html .= "</tr>\n";
                }
            }
            $html .= "</tbody></table></div>\n";
            $inTable = false;
            $tableRows = [];
        };

        $inlineFormat = function (string $text): string {
            $s = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            // Code spans
            $s = preg_replace('/`([^`]+)`/', '<code class="bg-gray-100 text-red-600 px-1.5 py-0.5 rounded text-sm font-mono">$1</code>', $s) ?? $s;
            // Bold + italic
            $s = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $s) ?? $s;
            // Bold
            $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s) ?? $s;
            // Italic
            $s = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $s) ?? $s;
            // Links
            $s = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" class="text-blue-600 hover:underline">$1</a>', $s) ?? $s;
            return $s;
        };

        foreach ($lines as $line) {
            $trimLine = rtrim($line);

            // Code blocks
            if (str_starts_with(trim($trimLine), '```')) {
                if ($inCodeBlock) {
                    $html .= '<pre class="bg-gray-900 text-gray-100 rounded-xl p-4 overflow-x-auto my-4 text-sm font-mono"><code>' . htmlspecialchars($codeContent) . '</code></pre>' . "\n";
                    $codeContent = '';
                    $inCodeBlock = false;
                } else {
                    $flushList();
                    $flushTable();
                    $inCodeBlock = true;
                }
                continue;
            }
            if ($inCodeBlock) {
                $codeContent .= $line . "\n";
                continue;
            }

            // Table rows (pipe-delimited)
            if (preg_match('/^\|(.+)\|$/', $trimLine, $m)) {
                // Check if separator row (|---|---|)
                if (preg_match('/^\|[\s\-:|]+\|$/', $trimLine)) {
                    continue; // skip separator
                }
                $flushList();
                if (!$inTable) {
                    $inTable = true;
                    $tableRows = [];
                }
                $cells = array_map(fn($c) => $inlineFormat(trim($c)), explode('|', trim($m[1])));
                $tableRows[] = $cells;
                continue;
            }

            // If we were in a table and hit a non-table line, flush
            if ($inTable) {
                $flushTable();
            }

            // Blank line
            if (trim($trimLine) === '') {
                $flushList();
                continue;
            }

            // Headings
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimLine, $m)) {
                $flushList();
                $level = strlen($m[1]);
                $text = $inlineFormat($m[2]);
                $id = strtolower(preg_replace('/[^a-z0-9]+/', '-', strip_tags($text)) ?? '');
                $html .= "<h{$level} id=\"{$id}\">{$text}</h{$level}>\n";
                continue;
            }

            // Horizontal rule
            if (preg_match('/^[\-\*_]{3,}$/', trim($trimLine))) {
                $flushList();
                $html .= "<hr class=\"my-6 border-gray-200\">\n";
                continue;
            }

            // Unordered list
            if (preg_match('/^(\s*)[-*+]\s+(.+)$/', $trimLine, $m)) {
                if ($inList !== 'ul') {
                    $flushList();
                    $inList = 'ul';
                    $html .= "<ul class=\"list-disc list-inside space-y-1 my-3 text-gray-700\">\n";
                }
                $html .= '<li>' . $inlineFormat($m[2]) . "</li>\n";
                continue;
            }

            // Ordered list
            if (preg_match('/^(\s*)\d+[.)]\s+(.+)$/', $trimLine, $m)) {
                if ($inList !== 'ol') {
                    $flushList();
                    $inList = 'ol';
                    $html .= "<ol class=\"list-decimal list-inside space-y-1 my-3 text-gray-700\">\n";
                }
                $html .= '<li>' . $inlineFormat($m[2]) . "</li>\n";
                continue;
            }

            // Blockquote
            if (preg_match('/^>\s*(.*)$/', $trimLine, $m)) {
                $flushList();
                $html .= '<blockquote class="border-l-4 border-blue-400 pl-4 py-2 my-4 text-gray-600 italic">' . $inlineFormat($m[1]) . "</blockquote>\n";
                continue;
            }

            // Paragraph
            $flushList();
            $html .= '<p class="my-3 text-gray-700 leading-relaxed">' . $inlineFormat($trimLine) . "</p>\n";
        }

        // Close any open blocks
        $flushList();
        $flushTable();
        if ($inCodeBlock && $codeContent !== '') {
            $html .= '<pre class="bg-gray-900 text-gray-100 rounded-xl p-4 overflow-x-auto my-4 text-sm font-mono"><code>' . htmlspecialchars($codeContent) . '</code></pre>' . "\n";
        }

        return $html;
    }

    /**
     * Builds fallback payload when AI generation fails.
     *
     * @param array $roadmap
     * @param array $context
     * @return array
     */
    private function buildFallbackArticlePayload(array $roadmap, array $context): array
    {
        $title = (string) ($roadmap['title'] ?? $roadmap['primary_keyword'] ?? 'Developer Tools Update');
        $keyword = (string) ($roadmap['primary_keyword'] ?? $title);
        $summaryLines = [];
        foreach ($context['knowledge_items'] as $item) {
            $summaryLines[] = '- ' . (string) ($item['title'] ?? '');
        }

        return [
            'title' => $title,
            'slug' => $this->sanitizeSlug($keyword),
            'metaTitle' => mb_substr($title . ' | DevLync', 0, 65),
            'metaDescription' => mb_substr("Detailed {$roadmap['content_type']} article about {$keyword}.", 0, 155),
            'excerpt' => mb_substr("Practical guide about {$keyword} with insights from recent developer discussions.", 0, 190),
            'focusKeyword' => $keyword,
            'content' => "# {$title}\n\nThis article was generated from DevLync automation context.\n\n## What We Found\n" . implode("\n", $summaryLines) . "\n\n## Final Thoughts\nUse this as a starting draft and refine details before publishing.",
            'directAnswer' => "This article summarizes current insights about {$keyword} and gives a practical overview for developers.",
            'keyTakeaways' => ['Topic is trending', 'Multiple sources mention the tool', 'Review practical use-cases', 'Compare alternatives before choosing'],
            'faq' => [],
            'tags' => [$keyword, 'developer-tools'],
            'seoScore' => 65,
            'wordCount' => 300,
            'imagePrompts' => [['slot' => 'featured', 'prompt' => "{$keyword} modern developer workspace", 'altText' => "{$keyword} overview image"]],
        ];
    }

    /**
     * Module 3: Social media poster.
     *
     * @param int $runId
     * @return array
     */
    private function runSocial(int $runId): array
    {
        $db = Database::getInstance();
        $rows = $db->query(
            'SELECT * FROM social_post_queue
             WHERE status = \'queued\'
               AND (scheduled_at IS NULL OR scheduled_at <= NOW())
             ORDER BY id ASC
             LIMIT 100'
        );

        if (!$rows) {
            return ['status' => 'skipped', 'reason' => 'No queued social posts', 'processed' => 0, 'failed' => 0];
        }

        $platformConfigs = $this->getSocialPlatformConfigMap();
        $socialClient = new SocialMediaClient();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['article_id']][] = $row;
        }

        $processed = 0;
        $succeeded = 0;
        $failed = 0;
        $skipped = 0;
        $disabled = 0;

        foreach ($grouped as $articleId => $queueRows) {
            $article = Article::getById($articleId);
            if (!$article) {
                foreach ($queueRows as $item) {
                    $this->updateSocialQueueRow((int) $item['id'], 'failed', null, null, 'Article not found');
                    $processed++;
                    $failed++;
                }
                continue;
            }

            $articleUrl = trim((string) ($queueRows[0]['article_url'] ?? ''));
            if ($articleUrl === '') {
                $articleUrl = $this->buildArticleUrlFromRecord($article);
            }

            $generated = $this->generateSocialPostsForArticle($article, $articleUrl, $runId);
            foreach ($queueRows as $item) {
                $queueId = (int) $item['id'];
                $platform = trim((string) $item['platform']);
                $text = $this->clampSocialText(
                    $platform,
                    (string) ($generated[$platform] ?? $this->buildFallbackSocialPost($platform, $article, $articleUrl))
                );

                $db->execute(
                    'UPDATE social_post_queue
                     SET status = \'processing\',
                         content_text = ?,
                         content_json = ?,
                         error_message = NULL
                     WHERE id = ?',
                    [
                        $text,
                        json_encode(['generated_at' => gmdate('c'), 'platform' => $platform], JSON_UNESCAPED_UNICODE),
                        $queueId,
                    ]
                );

                $config = $platformConfigs[$platform] ?? null;
                if ($config === null) {
                    $this->updateSocialQueueRow($queueId, 'failed', null, null, 'Platform config not found');
                    $this->mirrorLegacySocialPost((int) $item['article_id'], $platform, $text, (string) ($item['image_url'] ?? ''), 'failed', null, 'Platform config not found');
                    $processed++;
                    $failed++;
                    continue;
                }

                if ((int) ($config['is_enabled'] ?? 0) !== 1) {
                    $this->updateSocialQueueRow($queueId, 'disabled', null, null, 'Platform disabled');
                    $this->mirrorLegacySocialPost((int) $item['article_id'], $platform, $text, (string) ($item['image_url'] ?? ''), 'failed', null, 'Platform disabled');
                    $processed++;
                    $disabled++;
                    continue;
                }

                if ($this->isSocialRateLimited($platform, $config)) {
                    $this->updateSocialQueueRow($queueId, 'queued', null, null, null);
                    $skipped++;
                    $processed++;
                    continue;
                }

                $image = $socialClient->preparePlatformImage($platform, $article);
                if (in_array($platform, ['instagram', 'pinterest'], true) && $image === null) {
                    $this->updateSocialQueueRow($queueId, 'skipped', null, null, 'no_image');
                    $this->mirrorLegacySocialPost((int) $item['article_id'], $platform, $text, '', 'failed', null, 'no_image');
                    $processed++;
                    $skipped++;
                    continue;
                }

                $post = $socialClient->publish($platform, $article, $text, $articleUrl, $image);
                $status = (string) ($post['status'] ?? 'failed');
                $postUrl = isset($post['post_url']) ? (string) $post['post_url'] : null;
                $postId = isset($post['platform_post_id']) ? (string) $post['platform_post_id'] : null;
                $error = isset($post['error']) ? (string) $post['error'] : null;

                if ($status === 'posted' && !empty($post['success'])) {
                    $this->updateSocialQueueRow($queueId, 'posted', $postUrl, $postId, null);
                    $this->mirrorLegacySocialPost((int) $item['article_id'], $platform, $text, (string) ($image['public_url'] ?? ''), 'posted', $postUrl, null);
                    $succeeded++;
                } elseif ($status === 'skipped') {
                    $this->updateSocialQueueRow($queueId, 'skipped', null, null, $error ?? 'Skipped');
                    $this->mirrorLegacySocialPost((int) $item['article_id'], $platform, $text, (string) ($image['public_url'] ?? ''), 'failed', null, $error ?? 'Skipped');
                    $skipped++;
                } else {
                    $this->updateSocialQueueRow($queueId, 'failed', null, null, $error ?? 'Post failed');
                    $this->mirrorLegacySocialPost((int) $item['article_id'], $platform, $text, (string) ($image['public_url'] ?? ''), 'failed', null, $error ?? 'Post failed');
                    $failed++;
                }
                $processed++;
            }
        }

        $result = [
            'status' => 'completed',
            'processed' => $processed,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'skipped' => $skipped,
            'disabled' => $disabled,
        ];
        $this->automation->log($runId, 'social', 'info', 'social_done', 'Social queue processed', $result);
        return $result;
    }

    /**
     * Module 4: Affiliate updater + broken-link check.
     *
     * @param int $runId
     * @return array
     */
    private function runAffiliate(int $runId): array
    {
        $db = Database::getInstance();
        $activeLinks = $db->query(
            'SELECT id, brand_name, brand_slug, brand_aliases, affiliate_url, notes
             FROM affiliate_links
             WHERE status = \'active\'
               AND affiliate_url IS NOT NULL
               AND affiliate_url != \'\''
        );

        if (!$activeLinks) {
            return ['status' => 'skipped', 'reason' => 'No active affiliate URLs', 'processed' => 0, 'failed' => 0];
        }

        $articles = $db->query(
            'SELECT id, slug, content
             FROM articles
             WHERE status = \'published\'
               AND (content LIKE \'%affiliate-pending%\' OR content LIKE \'%href="#%\' OR content LIKE \'%#affiliate-%\')
             ORDER BY id ASC
             LIMIT 500'
        );

        $processed = 0;
        $updatedArticles = 0;
        $replacements = 0;
        foreach ($articles as $article) {
            $content = (string) ($article['content'] ?? '');
            $articleChanged = false;

            foreach ($activeLinks as $link) {
                $aliases = [(string) $link['brand_name'], (string) $link['brand_slug']];
                $jsonAliases = json_decode((string) ($link['brand_aliases'] ?? '[]'), true);
                if (is_array($jsonAliases)) {
                    foreach ($jsonAliases as $alias) {
                        $alias = trim((string) $alias);
                        if ($alias !== '') {
                            $aliases[] = $alias;
                        }
                    }
                }

                $mentions = false;
                foreach ($aliases as $alias) {
                    if ($alias !== '' && stripos($content, $alias) !== false) {
                        $mentions = true;
                        break;
                    }
                }
                if (!$mentions) {
                    continue;
                }

                $replace = $this->replaceAffiliateDummyLinks($content, $link, $aliases);
                if ($replace['count'] > 0) {
                    $content = $replace['content'];
                    $replacements += (int) $replace['count'];
                    $articleChanged = true;
                    $db->execute(
                        'UPDATE affiliate_link_usage
                         SET is_dummy = 0
                         WHERE affiliate_link_id = ?
                           AND article_id = ?',
                        [(int) $link['id'], (int) $article['id']]
                    );
                }
            }

            if ($articleChanged) {
                Article::updateField((int) $article['id'], 'content', $content);
                Article::updateField((int) $article['id'], 'has_affiliate_links', 1);
                Article::updateField((int) $article['id'], 'updated_content_at', date('Y-m-d H:i:s'));
                $updatedArticles++;
            }
            $processed++;
        }

        if ($updatedArticles > 0) {
            (new Cache())->clear();
        }

        $broken = $this->checkBrokenAffiliateLinks($activeLinks, $runId);
        $result = [
            'status' => 'completed',
            'processed' => $processed,
            'succeeded' => max(0, $updatedArticles),
            'failed' => 0,
            'updated_articles' => $updatedArticles,
            'replacements' => $replacements,
            'broken_links' => $broken,
        ];
        $this->automation->log($runId, 'affiliate', 'info', 'affiliate_done', 'Affiliate updater completed', $result);
        return $result;
    }

    /**
     * Module 5: Indexer (Google + IndexNow + sitemap pings).
     *
     * @param int $runId
     * @return array
     */
    private function runIndexer(int $runId): array
    {
        $urls = $this->collectIndexerUrls($runId, 30);
        if (!$urls) {
            return ['status' => 'skipped', 'reason' => 'No URLs pending for indexing', 'processed' => 0, 'failed' => 0];
        }

        $googleToken = $this->getGoogleIndexingAccessToken($runId);
        $googleByUrl = [];
        $googleSuccess = 0;
        $googleFailed = 0;
        foreach ($urls as $url) {
            if ($googleToken === null) {
                $googleByUrl[$url] = false;
                continue;
            }
            $ok = $this->notifyGoogleIndexing($url, $googleToken, $runId);
            $googleByUrl[$url] = $ok;
            if ($ok) {
                $googleSuccess++;
            } else {
                $googleFailed++;
            }
        }

        $indexNow = $this->pingIndexNow($urls, $runId);
        $sitemap = $this->pingSitemapEndpoints($runId);

        $retry = [];
        foreach ($urls as $url) {
            $googleOk = (bool) ($googleByUrl[$url] ?? false);
            if (!$googleOk && !$indexNow['success']) {
                $retry[] = $url;
            }
        }
        $this->saveIndexerQueue($retry);

        $result = [
            'status' => 'completed',
            'processed' => count($urls),
            'succeeded' => $googleSuccess + ($indexNow['success'] ? count($urls) : 0),
            'failed' => count($retry),
            'urls' => $urls,
            'google' => [
                'enabled' => $googleToken !== null,
                'success' => $googleSuccess,
                'failed' => $googleFailed,
            ],
            'indexnow' => $indexNow,
            'sitemap' => $sitemap,
            'retry_queued' => count($retry),
        ];
        $this->automation->log($runId, 'indexer', 'info', 'indexer_done', 'Indexer module completed', $result);
        return $result;
    }

    /**
     * Loads social platform config map.
     *
     * @return array
     */
    private function getSocialPlatformConfigMap(): array
    {
        $rows = Database::getInstance()->query('SELECT * FROM social_platform_configs');
        $map = [];
        foreach ($rows as $row) {
            $platform = (string) ($row['platform'] ?? '');
            if ($platform !== '') {
                $map[$platform] = $row;
            }
        }
        return $map;
    }

    /**
     * Generates platform-specific social posts using one AI call.
     *
     * @param array  $article
     * @param string $articleUrl
     * @param int    $runId
     * @return array
     */
    private function generateSocialPostsForArticle(array $article, string $articleUrl, int $runId): array
    {
        $fallback = $this->buildFallbackSocialPosts($article, $articleUrl);
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a social media specialist for DevLync. Return strict JSON only.',
            ],
            [
                'role' => 'user',
                'content' =>
                    "Create social posts for this article.\n"
                    . "Article URL: {$articleUrl}\n"
                    . 'Title: ' . (string) ($article['title'] ?? '') . "\n"
                    . 'Type: ' . (string) ($article['content_type'] ?? 'blog') . "\n"
                    . 'Excerpt: ' . (string) ($article['excerpt'] ?? '') . "\n"
                    . "Return JSON keys only: twitter, linkedin, facebook, instagram, pinterest, youtube, threads, bluesky.\n"
                    . "Do NOT include URL in twitter/linkedin/facebook text. Keep platform style natural.",
            ],
        ];

        $ai = $this->automation->callAiWithFallback('social', $messages, [
            'run_id' => $runId,
            'json_mode' => true,
            'temperature' => 0.8,
            'max_tokens' => 3000,
        ]);

        if (!($ai['success'] ?? false)) {
            return $fallback;
        }

        $decoded = $this->decodeJsonFromText((string) ($ai['content'] ?? ''));
        if (!is_array($decoded)) {
            return $fallback;
        }

        foreach (array_keys($fallback) as $platform) {
            $candidate = trim((string) ($decoded[$platform] ?? ''));
            if ($candidate !== '') {
                $fallback[$platform] = $this->clampSocialText($platform, $candidate);
            }
        }

        return $fallback;
    }

    /**
     * Builds deterministic social text when AI is unavailable.
     *
     * @param array  $article
     * @param string $articleUrl
     * @return array
     */
    private function buildFallbackSocialPosts(array $article, string $articleUrl): array
    {
        $title = trim((string) ($article['title'] ?? 'New DevLync article'));
        $excerpt = trim((string) ($article['excerpt'] ?? 'Read our latest developer tools insights.'));
        $short = $this->truncate($excerpt, 140);

        return [
            'twitter' => $this->truncate($title . ' - ' . $short, 260),
            'linkedin' => $this->truncate($title . "\n\n" . $excerpt . "\n\nRead more on DevLync.", 2900),
            'facebook' => $this->truncate('What do you think about this? ' . $title . ' ' . $short, 60000),
            'instagram' => $this->truncate($title . "\n\n" . $short . "\n\nLink in bio.", 2000),
            'pinterest' => $this->truncate($title . ' - ' . $short, 450),
            'youtube' => $this->truncate($title . "\n\n" . $excerpt, 4000),
            'threads' => $this->truncate($title . ' ' . $short, 450),
            'bluesky' => $this->truncate($title . ' ' . $short, 240),
        ];
    }

    /**
     * Returns fallback text for one platform.
     *
     * @param string $platform
     * @param array  $article
     * @param string $articleUrl
     * @return string
     */
    private function buildFallbackSocialPost(string $platform, array $article, string $articleUrl): string
    {
        $all = $this->buildFallbackSocialPosts($article, $articleUrl);
        return (string) ($all[$platform] ?? $all['twitter']);
    }

    /**
     * Enforces platform character budget.
     *
     * @param string $platform
     * @param string $text
     * @return string
     */
    private function clampSocialText(string $platform, string $text): string
    {
        $limits = [
            'twitter' => 260,
            'linkedin' => 3000,
            'facebook' => 63200,
            'instagram' => 2200,
            'pinterest' => 500,
            'youtube' => 4000,
            'threads' => 500,
            'bluesky' => 260,
        ];
        $limit = $limits[$platform] ?? 500;
        return $this->truncate(trim($text), $limit);
    }

    /**
     * Updates one social queue row.
     *
     * @param int         $id
     * @param string      $status
     * @param string|null $postUrl
     * @param string|null $platformPostId
     * @param string|null $error
     * @return void
     */
    private function updateSocialQueueRow(
        int $id,
        string $status,
        ?string $postUrl,
        ?string $platformPostId,
        ?string $error
    ): void {
        $status = in_array($status, ['queued', 'processing', 'posted', 'failed', 'skipped', 'disabled'], true) ? $status : 'failed';
        Database::getInstance()->execute(
            'UPDATE social_post_queue
             SET status = ?,
                 post_url = ?,
                 platform_post_id = ?,
                 error_message = ?,
                 posted_at = CASE WHEN ? = \'posted\' THEN NOW() ELSE posted_at END
             WHERE id = ?',
            [$status, $postUrl, $platformPostId, $error, $status, $id]
        );
    }

    /**
     * Checks whether a platform has exceeded its configured rate limits.
     *
     * @param string $platform
     * @param array  $config
     * @return bool
     */
    private function isSocialRateLimited(string $platform, array $config): bool
    {
        $db = Database::getInstance();
        $perHour = (int) ($config['rate_limit_per_hour'] ?? 10);
        $perDay = (int) ($config['rate_limit_per_day'] ?? 50);

        $postedLastHour = (int) ($db->queryOne(
            'SELECT COUNT(*) AS c
             FROM social_post_queue
             WHERE platform = ?
               AND status = \'posted\'
               AND posted_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)',
            [$platform]
        )['c'] ?? 0);

        if ($postedLastHour >= $perHour) {
            return true;
        }

        $postedToday = (int) ($db->queryOne(
            'SELECT COUNT(*) AS c
             FROM social_post_queue
             WHERE platform = ?
               AND status = \'posted\'
               AND posted_at >= CURDATE()',
            [$platform]
        )['c'] ?? 0);

        return $postedToday >= $perDay;
    }

    /**
     * Writes to legacy social_posts table for compatibility.
     *
     * @param int         $articleId
     * @param string      $platform
     * @param string      $content
     * @param string      $imageUrl
     * @param string      $status
     * @param string|null $postUrl
     * @param string|null $error
     * @return void
     */
    private function mirrorLegacySocialPost(
        int $articleId,
        string $platform,
        string $content,
        string $imageUrl,
        string $status,
        ?string $postUrl,
        ?string $error
    ): void {
        $legacyStatus = $status === 'posted' ? 'posted' : 'failed';
        try {
            Database::getInstance()->execute(
                'INSERT INTO social_posts
                 (article_id, platform, content, image_url, post_url, status, posted_at, error_message)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $articleId,
                    $platform,
                    $content,
                    $imageUrl !== '' ? $imageUrl : null,
                    $postUrl,
                    $legacyStatus,
                    $legacyStatus === 'posted' ? date('Y-m-d H:i:s') : null,
                    $error,
                ]
            );
        } catch (Throwable) {
            // Ignore legacy mirror failures to avoid blocking queue updates.
        }
    }

    /**
     * Inserts social queue items for all enabled platforms.
     *
     * @param int   $articleId
     * @param array $article
     * @return int
     */
    private function enqueueSocialQueue(int $articleId, array $article): int
    {
        $db = Database::getInstance();
        $articleUrl = $this->buildArticleUrlFromRecord($article);
        if ($articleUrl === '') {
            return 0;
        }

        $platforms = $db->query(
            'SELECT platform
             FROM social_platform_configs
             WHERE is_enabled = 1
             ORDER BY id ASC'
        );
        if (!$platforms) {
            return 0;
        }

        $queued = 0;
        foreach ($platforms as $platformRow) {
            $platform = (string) ($platformRow['platform'] ?? '');
            if ($platform === '') {
                continue;
            }

            $exists = $db->queryOne(
                'SELECT id
                 FROM social_post_queue
                 WHERE article_id = ?
                   AND platform = ?
                   AND status IN (\'queued\', \'processing\', \'posted\')
                 LIMIT 1',
                [$articleId, $platform]
            );
            if ($exists) {
                continue;
            }

            $db->execute(
                'INSERT INTO social_post_queue
                 (article_id, platform, image_url, article_url, status)
                 VALUES (?, ?, ?, ?, \'queued\')',
                [
                    $articleId,
                    $platform,
                    (string) ($article['featured_image_url'] ?? ''),
                    $articleUrl,
                ]
            );
            $queued++;
        }

        return $queued;
    }

    /**
     * Builds full article URL from content type + slug.
     *
     * @param array $article
     * @return string
     */
    private function buildArticleUrlFromRecord(array $article): string
    {
        $slug = trim((string) ($article['slug'] ?? ''));
        if ($slug === '') {
            return '';
        }

        $prefix = $this->contentTypePrefix((string) ($article['content_type'] ?? 'blog'));

        return SITE_URL . '/' . $prefix . '/' . rawurlencode($slug);
    }

    /**
     * Resolves article URL prefix from content type.
     *
     * @param string $type
     * @return string
     */
    private function contentTypePrefix(string $type): string
    {
        $type = trim(strtolower($type));
        return match ($type) {
            'review' => 'reviews',
            'comparison' => 'comparisons',
            'news' => 'news',
            default => 'blog',
        };
    }

    /**
     * Adds one URL to indexer queue.
     *
     * @param string $url
     * @return void
     */
    private function enqueueIndexerUrl(string $url): void
    {
        $url = trim($url);
        if ($url === '') {
            return;
        }
        $queue = $this->getIndexerQueue();
        $queue[] = $url;
        $this->saveIndexerQueue(array_slice(array_values(array_unique($queue)), -200));
    }

    /**
     * Loads indexer queue from settings JSON.
     *
     * @return array
     */
    private function getIndexerQueue(): array
    {
        $decoded = json_decode((string) (Setting::get('automation_indexer_queue') ?? '[]'), true);
        if (!is_array($decoded)) {
            return [];
        }

        $urls = [];
        foreach ($decoded as $value) {
            $url = trim((string) $value);
            if ($url !== '') {
                $urls[] = $url;
            }
        }
        return array_values(array_unique($urls));
    }

    /**
     * Persists indexer queue to settings.
     *
     * @param array $urls
     * @return void
     */
    private function saveIndexerQueue(array $urls): void
    {
        $clean = [];
        foreach ($urls as $url) {
            $url = trim((string) $url);
            if ($url !== '') {
                $clean[] = $url;
            }
        }
        Setting::set('automation_indexer_queue', (string) json_encode(array_values(array_unique($clean)), JSON_UNESCAPED_UNICODE));
    }

    /**
     * Truncates text at max characters.
     *
     * @param string $text
     * @param int    $maxChars
     * @return string
     */
    private function truncate(string $text, int $maxChars): string
    {
        $text = trim($text);
        if (mb_strlen($text, 'UTF-8') <= $maxChars) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, max(1, $maxChars - 3), 'UTF-8')) . '...';
    }

    /**
     * Replaces pending/dummy affiliate links with active URLs.
     *
     * @param string $content
     * @param array  $link
     * @param array  $aliases
     * @return array{content: string, count: int}
     */
    private function replaceAffiliateDummyLinks(string $content, array $link, array $aliases): array
    {
        $count = 0;
        $url = (string) ($link['affiliate_url'] ?? '');
        $slug = preg_quote((string) ($link['brand_slug'] ?? ''), '/');
        if ($url === '' || $slug === '') {
            return ['content' => $content, 'count' => 0];
        }

        $content = preg_replace_callback(
            '/<a([^>]*?)href="#affiliate-' . $slug . '"([^>]*)>/i',
            static function (array $m) use ($url, &$count): string {
                $count++;
                return '<a' . $m[1] . 'href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'
                    . $m[2] . ' rel="nofollow sponsored" target="_blank">';
            },
            $content
        ) ?? $content;

        $content = preg_replace_callback(
            '/<a([^>]*?)href="#"([^>]*?)data-brand="' . $slug . '"([^>]*)>/i',
            static function (array $m) use ($url, &$count): string {
                $count++;
                $attrs = $m[1] . 'href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' . $m[2] . $m[3];
                $attrs = preg_replace('/affiliate-pending/i', 'affiliate-active', $attrs) ?? $attrs;
                if (!preg_match('/\brel=/i', $attrs)) {
                    $attrs .= ' rel="nofollow sponsored"';
                }
                if (!preg_match('/\btarget=/i', $attrs)) {
                    $attrs .= ' target="_blank"';
                }
                return '<a' . $attrs . '>';
            },
            $content
        ) ?? $content;

        foreach ($aliases as $alias) {
            $alias = trim((string) $alias);
            if ($alias === '') {
                continue;
            }
            $pattern = '/<a([^>]*?)href="#"([^>]*)>\s*' . preg_quote($alias, '/') . '\s*<\/a>/i';
            $content = preg_replace_callback(
                $pattern,
                static function (array $m) use ($url, $alias, &$count): string {
                    $count++;
                    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'
                        . $m[1] . $m[2]
                        . ' rel="nofollow sponsored" target="_blank">'
                        . htmlspecialchars($alias, ENT_QUOTES, 'UTF-8') . '</a>';
                },
                $content
            ) ?? $content;
        }

        return ['content' => $content, 'count' => $count];
    }

    /**
     * Daily broken-link check for active affiliate URLs.
     *
     * @param array $activeLinks
     * @param int   $runId
     * @return int
     */
    private function checkBrokenAffiliateLinks(array $activeLinks, int $runId): int
    {
        $db = Database::getInstance();
        $supervisor = new Supervisor();
        $broken = 0;

        foreach ($activeLinks as $link) {
            $url = trim((string) ($link['affiliate_url'] ?? ''));
            if ($url === '') {
                continue;
            }

            $head = $this->checkUrlHead($url);
            if ($head['ok']) {
                continue;
            }

            $broken++;
            $note = trim((string) ($link['notes'] ?? ''));
            $append = '[' . gmdate('Y-m-d H:i:s') . ' UTC] Broken URL (' . $head['status_code'] . '): ' . $url;
            $newNotes = $note === '' ? $append : $note . "\n" . $append;

            // Compat: schema enum may not support "broken"; use "paused" and log suggestion.
            $db->execute(
                'UPDATE affiliate_links
                 SET status = \'paused\',
                     notes = ?,
                     updated_at = NOW()
                 WHERE id = ?',
                [$newNotes, (int) $link['id']]
            );

            $msg = 'Broken affiliate link detected: ' . (string) ($link['brand_name'] ?? 'unknown');
            $this->automation->log($runId, 'affiliate', 'warning', 'broken_link', $msg, [
                'affiliate_id' => (int) $link['id'],
                'status_code' => (int) $head['status_code'],
                'url' => $url,
            ]);

            try {
                $supervisor->addSuggestion([
                    'category' => 'affiliate',
                    'priority' => 'high',
                    'title' => $msg,
                    'description' => 'URL returned HTTP ' . (int) $head['status_code'] . '. Link moved to paused state.',
                    'impact_area' => 'Revenue',
                    'impact_score' => 85,
                    'effort_score' => 20,
                    'estimated_time' => '10 min',
                    'affected_urls' => [$url],
                ]);
                $supervisor->sendTelegramAlert($msg . ' (HTTP ' . (int) $head['status_code'] . ')', 'warning');
            } catch (Throwable) {
                // Non-blocking: supervisor integration already handled at run level.
            }
        }

        return $broken;
    }

    /**
     * Performs HEAD request with redirect tracking.
     *
     * @param string $url
     * @return array{ok: bool, status_code: int, redirect_count: int}
     */
    private function checkUrlHead(string $url): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'status_code' => 0, 'redirect_count' => 0];
        }

        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'DevLync-AffiliateChecker/1.0',
        ]);

        curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirects = (int) curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
        curl_close($ch);

        $ok = $statusCode === 200 && $redirects <= 3;
        return ['ok' => $ok, 'status_code' => $statusCode, 'redirect_count' => $redirects];
    }

    /**
     * Collects URL queue for indexer from settings + fresh articles.
     *
     * @param int $runId
     * @param int $limit
     * @return array
     */
    private function collectIndexerUrls(int $runId, int $limit): array
    {
        $queue = $this->getIndexerQueue();
        $urls = [];
        foreach ($queue as $url) {
            if ($url !== '' && count($urls) < $limit) {
                $urls[] = $url;
            }
        }

        if (count($urls) >= $limit) {
            return array_values(array_unique($urls));
        }

        $db = Database::getInstance();
        $lastId = (int) (Setting::get('automation_indexer_last_article_id') ?? '0');
        $rows = $db->query(
            'SELECT id, slug, content_type
             FROM articles
             WHERE status = \'published\'
               AND id > ?
             ORDER BY id ASC
             LIMIT ?',
            [$lastId, max(1, $limit - count($urls))]
        );

        $maxSeenId = $lastId;
        foreach ($rows as $row) {
            $maxSeenId = max($maxSeenId, (int) $row['id']);
            $url = $this->buildArticleUrlFromRecord($row);
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        if ($maxSeenId > $lastId) {
            Setting::set('automation_indexer_last_article_id', (string) $maxSeenId);
            $this->automation->log($runId, 'indexer', 'debug', 'indexer_cursor', 'Indexer cursor advanced', [
                'last_article_id' => $maxSeenId,
            ]);
        }

        return array_values(array_unique($urls));
    }

    /**
     * Gets Google Indexing API OAuth token from service account key.
     *
     * @param int $runId
     * @return string|null
     */
    private function getGoogleIndexingAccessToken(int $runId): ?string
    {
        $raw = trim((string) (Setting::get('google_indexing_service_key') ?? ''));
        if ($raw === '') {
            return null;
        }

        $service = null;
        if (str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $service = $decoded;
            }
        } elseif (is_file($raw)) {
            $decoded = json_decode((string) @file_get_contents($raw), true);
            if (is_array($decoded)) {
                $service = $decoded;
            }
        }

        if (!is_array($service)) {
            $this->automation->log($runId, 'indexer', 'warning', 'google_auth_skip', 'Invalid Google service account JSON');
            return null;
        }

        $clientEmail = trim((string) ($service['client_email'] ?? ''));
        $privateKey = trim((string) ($service['private_key'] ?? ''));
        $tokenUri = trim((string) ($service['token_uri'] ?? 'https://oauth2.googleapis.com/token'));
        if ($clientEmail === '' || $privateKey === '') {
            $this->automation->log($runId, 'indexer', 'warning', 'google_auth_skip', 'Google service account missing client_email/private_key');
            return null;
        }

        $jwt = $this->buildGoogleServiceJwt($clientEmail, $privateKey, $tokenUri);
        if ($jwt === null) {
            $this->automation->log($runId, 'indexer', 'warning', 'google_auth_skip', 'Failed to sign Google JWT');
            return null;
        }

        $token = HttpClient::post(
            $tokenUri,
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            ['Content-Type: application/x-www-form-urlencoded'],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );

        if (!$token['success']) {
            $this->automation->log($runId, 'indexer', 'warning', 'google_auth_skip', 'Google token request failed', [
                'error' => $token['error'] ?? 'unknown',
            ]);
            return null;
        }

        $access = trim((string) ($token['json']['access_token'] ?? ''));
        return $access !== '' ? $access : null;
    }

    /**
     * Builds service-account signed JWT for Google OAuth token request.
     *
     * @param string $clientEmail
     * @param string $privateKey
     * @param string $tokenUri
     * @return string|null
     */
    private function buildGoogleServiceJwt(string $clientEmail, string $privateKey, string $tokenUri): ?string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $now = time();
        $claims = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/indexing',
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $encodedHeader = $this->base64UrlEncode((string) json_encode($header, JSON_UNESCAPED_SLASHES));
        $encodedClaims = $this->base64UrlEncode((string) json_encode($claims, JSON_UNESCAPED_SLASHES));
        $payload = $encodedHeader . '.' . $encodedClaims;

        $signature = '';
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            return null;
        }
        $ok = openssl_sign($payload, $signature, $key, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            return null;
        }

        return $payload . '.' . $this->base64UrlEncode($signature);
    }

    /**
     * Base64 URL-safe encoding.
     *
     * @param string $value
     * @return string
     */
    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * Sends one URL update to Google Indexing API.
     *
     * @param string $url
     * @param string $accessToken
     * @param int    $runId
     * @return bool
     */
    private function notifyGoogleIndexing(string $url, string $accessToken, int $runId): bool
    {
        $response = HttpClient::postJson(
            'https://indexing.googleapis.com/v3/urlNotifications:publish',
            [
                'url' => $url,
                'type' => 'URL_UPDATED',
            ],
            [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );

        $ok = (bool) ($response['success'] ?? false);
        $this->automation->log($runId, 'indexer', $ok ? 'info' : 'warning', 'google_indexing', 'Google indexing notify', [
            'url' => $url,
            'success' => $ok,
            'status_code' => (int) ($response['status_code'] ?? 0),
            'error' => $response['error'] ?? null,
        ]);
        return $ok;
    }

    /**
     * Sends IndexNow batch ping.
     *
     * @param array $urls
     * @param int   $runId
     * @return array{success: bool, status_code: int, error: string|null}
     */
    private function pingIndexNow(array $urls, int $runId): array
    {
        $key = trim((string) (Setting::get('indexnow_key') ?? ''));
        if ($key === '') {
            return ['success' => false, 'status_code' => 0, 'error' => 'indexnow_key missing'];
        }

        $payload = [
            'host' => SITE_DOMAIN,
            'key' => $key,
            'keyLocation' => SITE_URL . '/' . $key . '.txt',
            'urlList' => array_values(array_unique($urls)),
        ];

        $response = HttpClient::postJson(
            'https://api.indexnow.org/indexnow',
            $payload,
            ['Content-Type: application/json'],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );

        $ok = (bool) ($response['success'] ?? false);
        $this->automation->log($runId, 'indexer', $ok ? 'info' : 'warning', 'indexnow', 'IndexNow ping', [
            'success' => $ok,
            'status_code' => (int) ($response['status_code'] ?? 0),
            'error' => $response['error'] ?? null,
            'count' => count($urls),
        ]);

        return [
            'success' => $ok,
            'status_code' => (int) ($response['status_code'] ?? 0),
            'error' => isset($response['error']) ? (string) $response['error'] : null,
        ];
    }

    /**
     * Pings sitemap endpoints for Google and Bing.
     *
     * @param int $runId
     * @return array
     */
    private function pingSitemapEndpoints(int $runId): array
    {
        $sitemapUrl = SITE_URL . '/sitemap.xml';
        $endpoints = [
            'google' => 'https://www.google.com/ping?sitemap=' . rawurlencode($sitemapUrl),
            'bing' => 'https://www.bing.com/ping?sitemap=' . rawurlencode($sitemapUrl),
        ];

        $results = [];
        foreach ($endpoints as $name => $url) {
            $response = HttpClient::get($url, ['Accept: */*'], 20, ['retries' => 1, 'verify_ssl' => true]);
            $results[$name] = [
                'success' => (bool) ($response['success'] ?? false),
                'status_code' => (int) ($response['status_code'] ?? 0),
            ];
            $this->automation->log($runId, 'indexer', $response['success'] ? 'info' : 'warning', 'sitemap_ping', 'Sitemap ping sent', [
                'target' => $name,
                'status_code' => (int) ($response['status_code'] ?? 0),
                'success' => (bool) ($response['success'] ?? false),
            ]);
        }

        return $results;
    }

    /**
     * Module 6: Weekly report and Telegram delivery.
     *
     * @param int $runId
     * @return array
     */
    private function runReport(int $runId): array
    {
        $db = Database::getInstance();

        $articles = $this->rowsToIntMap(
            $db->query(
                'SELECT content_type, COUNT(*) AS c
                 FROM articles
                 WHERE status = \'published\'
                   AND published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY content_type'
            ),
            'content_type',
            'c'
        );

        $aiCosts = $db->queryOne(
            'SELECT
                COALESCE(SUM(cost), 0) AS total_cost,
                COUNT(*) AS calls,
                COALESCE(SUM(input_tokens), 0) AS input_tokens,
                COALESCE(SUM(output_tokens), 0) AS output_tokens
             FROM cost_records
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
        ) ?? [];

        $knowledge = $this->rowsToIntMap(
            $db->query(
                'SELECT source_type, COUNT(*) AS c
                 FROM knowledge_items
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY source_type'
            ),
            'source_type',
            'c'
        );

        $roadmapStatus = $this->rowsToIntMap(
            $db->query('SELECT status, COUNT(*) AS c FROM roadmap_items GROUP BY status'),
            'status',
            'c'
        );

        $images = $this->rowsToIntMap(
            $db->query(
                'SELECT source_type, COUNT(*) AS c
                 FROM image_library
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY source_type'
            ),
            'source_type',
            'c'
        );

        $affiliate = $this->rowsToIntMap(
            $db->query('SELECT status, COUNT(*) AS c FROM affiliate_links GROUP BY status'),
            'status',
            'c'
        );
        $dummyUsage = (int) (($db->queryOne('SELECT COUNT(*) AS c FROM affiliate_link_usage WHERE is_dummy = 1')['c'] ?? 0));

        $socialRows = $db->query(
            'SELECT platform, status, COUNT(*) AS c
             FROM social_post_queue
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY platform, status'
        );
        $socialTotals = ['posted' => 0, 'failed' => 0, 'queued' => 0, 'other' => 0];
        foreach ($socialRows as $row) {
            $status = (string) ($row['status'] ?? '');
            $count = (int) ($row['c'] ?? 0);
            if ($status === 'posted') {
                $socialTotals['posted'] += $count;
            } elseif ($status === 'failed') {
                $socialTotals['failed'] += $count;
            } elseif ($status === 'queued') {
                $socialTotals['queued'] += $count;
            } else {
                $socialTotals['other'] += $count;
            }
        }

        $automationRuns = $db->query(
            'SELECT module, status, COUNT(*) AS c, AVG(duration_seconds) AS avg_duration
             FROM automation_runs
             WHERE started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
               AND status != \'running\'
             GROUP BY module, status'
        );

        $topArticle = $db->queryOne(
            'SELECT title, seo_score, word_count
             FROM articles
             WHERE status = \'published\'
               AND published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY seo_score DESC, published_at DESC
             LIMIT 1'
        ) ?? [];

        $overall = $db->queryOne(
            'SELECT COUNT(*) AS total_articles, AVG(seo_score) AS avg_seo, AVG(word_count) AS avg_words
             FROM articles
             WHERE status = \'published\''
        ) ?? [];

        $supervisorScore = $this->getSupervisorScore($db);
        $reportText = $this->formatWeeklyReportText(
            $articles,
            $knowledge,
            $socialTotals,
            $affiliate,
            $dummyUsage,
            $aiCosts,
            $topArticle,
            $overall,
            $supervisorScore
        );

        $telegramSent = false;
        try {
            $telegramSent = (new Supervisor())->sendTelegramAlert($reportText, 'info');
        } catch (Throwable $e) {
            $this->automation->log($runId, 'report', 'warning', 'telegram_fail', 'Weekly report Telegram send failed', [
                'error' => $e->getMessage(),
            ]);
        }

        $result = [
            'status' => 'completed',
            'processed' => 1,
            'succeeded' => 1,
            'failed' => 0,
            'telegram_sent' => $telegramSent,
            'articles' => $articles,
            'knowledge' => $knowledge,
            'roadmap_status' => $roadmapStatus,
            'images' => $images,
            'affiliate' => $affiliate,
            'affiliate_dummy_usage' => $dummyUsage,
            'social_totals' => $socialTotals,
            'automation_runs' => $automationRuns,
            'top_article' => $topArticle,
            'overall' => [
                'total_articles' => (int) ($overall['total_articles'] ?? 0),
                'avg_seo' => round((float) ($overall['avg_seo'] ?? 0), 2),
                'avg_words' => (int) round((float) ($overall['avg_words'] ?? 0)),
            ],
            'ai_costs' => [
                'total_cost' => round((float) ($aiCosts['total_cost'] ?? 0), 6),
                'calls' => (int) ($aiCosts['calls'] ?? 0),
                'input_tokens' => (int) ($aiCosts['input_tokens'] ?? 0),
                'output_tokens' => (int) ($aiCosts['output_tokens'] ?? 0),
            ],
            'supervisor_score' => $supervisorScore,
        ];

        $this->automation->log($runId, 'report', 'info', 'weekly_report_done', 'Weekly report generated', [
            'telegram_sent' => $telegramSent,
            'total_articles_week' => array_sum($articles),
            'total_cost_week' => round((float) ($aiCosts['total_cost'] ?? 0), 6),
        ]);

        return $result;
    }

    /**
     * Converts grouped rows into a key=>int map.
     *
     * @param array  $rows
     * @param string $keyColumn
     * @param string $valueColumn
     * @return array
     */
    private function rowsToIntMap(array $rows, string $keyColumn, string $valueColumn): array
    {
        $map = [];
        foreach ($rows as $row) {
            $key = (string) ($row[$keyColumn] ?? '');
            if ($key === '') {
                continue;
            }
            $map[$key] = (int) ($row[$valueColumn] ?? 0);
        }
        return $map;
    }

    /**
     * Returns supervisor score from settings or live calculator.
     *
     * @param Database $db
     * @return int
     */
    private function getSupervisorScore(Database $db): int
    {
        try {
            $score = $db->queryOne(
                'SELECT setting_value
                 FROM supervisor_settings
                 WHERE setting_key = \'website_score\'
                 LIMIT 1'
            );
            if ($score && is_numeric((string) ($score['setting_value'] ?? ''))) {
                return (int) round((float) $score['setting_value']);
            }
        } catch (Throwable) {
            // Fallback below.
        }

        try {
            return (new Supervisor())->calculateWebsiteScore();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Formats weekly report text for Telegram.
     *
     * @param array $articles
     * @param array $knowledge
     * @param array $socialTotals
     * @param array $affiliate
     * @param int   $dummyUsage
     * @param array $aiCosts
     * @param array $topArticle
     * @param array $overall
     * @param int   $supervisorScore
     * @return string
     */
    private function formatWeeklyReportText(
        array $articles,
        array $knowledge,
        array $socialTotals,
        array $affiliate,
        int $dummyUsage,
        array $aiCosts,
        array $topArticle,
        array $overall,
        int $supervisorScore
    ): string {
        $totalArticlesWeek = array_sum($articles);
        $totalKnowledgeWeek = array_sum($knowledge);
        $socialTotal = (int) ($socialTotals['posted'] ?? 0)
            + (int) ($socialTotals['failed'] ?? 0)
            + (int) ($socialTotals['queued'] ?? 0)
            + (int) ($socialTotals['other'] ?? 0);
        $totalCost = round((float) ($aiCosts['total_cost'] ?? 0), 4);

        $topTitle = trim((string) ($topArticle['title'] ?? 'N/A'));
        $topSeo = (int) ($topArticle['seo_score'] ?? 0);
        $allArticles = (int) ($overall['total_articles'] ?? 0);

        $lines = [
            '📊 DEVLYNC WEEKLY REPORT',
            '══════════════════════════',
            '',
            "📝 Articles Published: {$totalArticlesWeek}",
            '  📰 Blog: ' . (int) ($articles['blog'] ?? 0),
            '  ⭐ Review: ' . (int) ($articles['review'] ?? 0),
            '  ⚖️ Comparison: ' . (int) ($articles['comparison'] ?? 0),
            '  🚨 News: ' . (int) ($articles['news'] ?? 0),
            '',
            "🧠 Knowledge Base: +{$totalKnowledgeWeek} items",
            '  🎥 YouTube: ' . (int) ($knowledge['youtube'] ?? 0),
            '  💬 Reddit: ' . (int) ($knowledge['reddit'] ?? 0),
            '  📡 RSS: ' . ((int) ($knowledge['rss'] ?? 0) + (int) ($knowledge['hackernews'] ?? 0) + (int) ($knowledge['devto'] ?? 0) + (int) ($knowledge['producthunt'] ?? 0)),
            '',
            "📱 Social Posts: {$socialTotal}",
            '  ✅ Posted: ' . (int) ($socialTotals['posted'] ?? 0),
            '  ❌ Failed: ' . (int) ($socialTotals['failed'] ?? 0),
            '  ⏳ Queued: ' . (int) ($socialTotals['queued'] ?? 0),
            '',
            '🔗 Affiliates: ' . (int) ($affiliate['active'] ?? 0) . ' active, ' . (int) ($affiliate['paused'] ?? 0) . ' paused, ' . $dummyUsage . ' dummy-usage rows',
            '💰 AI Cost: $' . number_format($totalCost, 4),
            "🏆 Top Article: {$topTitle} (SEO: {$topSeo}/100)",
            "🧠 Website Score: {$supervisorScore}/100",
            '',
            "📈 All Time: {$allArticles} published articles",
        ];

        return implode("\n", $lines);
    }
}
