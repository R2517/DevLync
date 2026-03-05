<?php
declare(strict_types=1);

/**
 * AdminController
 * Admin panel pages: dashboard, articles, images, affiliates, settings, auth.
 * Uses Auth static methods throughout.
 */
class AdminController
{
    public function login(): void
    {
        if (Auth::isLoggedIn()) {
            header('Location: ' . url('/admin'));
            exit;
        }
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            if (Auth::login($password)) {
                header('Location: ' . url('/admin'));
                exit;
            }
            $error = 'Incorrect password. Please try again.';
        }
        include VIEWS_PATH . '/admin/login.php';
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . url('/admin/login'));
        exit;
    }

    public function dashboard(): void
    {
        Auth::requireAuth();
        $statusCounts = Article::countByStatus();
        $stats = [
            'articles' => Article::count(),
            'reviews' => Article::count('review'),
            'comparisons' => Article::count('comparison'),
            'news' => Article::count('news'),
            'pending' => $statusCounts['draft'] ?? 0,
            'affiliates' => count(AffiliateLink::getActive()),
            'roadmap' => RoadmapItem::countByStatus(),
            'cost_today' => CostRecord::getTotalToday(),
            'cost_month' => CostRecord::getTotalThisMonth(),
            'recent_knowledge' => KnowledgeItem::getRecent(5),
        ];
        $meta = ['title' => 'Dashboard'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/dashboard.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function categories(): void
    {
        Auth::requireAuth();
        $categories = Category::getAll();
        $meta = ['title' => 'Categories'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/categories.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function articles(): void
    {
        Auth::requireAuth();
        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Article::getForAdmin($status, $type, $page);
        $meta = ['title' => 'Articles'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/articles.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function publishArticle(string $id): void
    {
        Auth::requireAuth();
        Article::updateStatus((int) $id, 'published');
        (new Cache())->clear();
        header('Location: ' . url('/admin/articles'));
        exit;
    }

    public function createArticle(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? 'New Article');
            $type = $_POST['type'] ?? 'blog';
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
            // Ensure unique slug
            $existing = $db->queryOne("SELECT id FROM articles WHERE slug = ?", [$slug]);
            if ($existing) {
                $slug .= '-' . time();
            }
            $db->execute(
                "INSERT INTO articles (title, slug, content_type, content, status, created_at, updated_at) VALUES (?, ?, ?, '', 'draft', NOW(), NOW())",
                [$title, $slug, $type]
            );
            $id = $db->queryOne("SELECT LAST_INSERT_ID() as id")['id'] ?? null;
            if ($id) {
                header('Location: ' . url('/admin/articles/' . $id . '/edit'));
                exit;
            }
        }

        $meta = ['title' => 'Create Article'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/article_create.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function editArticle(string $id): void
    {
        Auth::requireAuth();
        $id = (int) $id;
        $db = Database::getInstance();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $_POST['title'] ?? '';
            $slug = $_POST['slug'] ?? '';
            $content = $_POST['content'] ?? '';
            $excerpt = $_POST['excerpt'] ?? '';
            $status = $_POST['status'] ?? 'draft';

            $db->execute(
                "UPDATE articles SET title = ?, slug = ?, content = ?, excerpt = ?, status = ? WHERE id = ?",
                [$title, $slug, $content, $excerpt, $status, $id]
            );

            (new Cache())->clear();
            header('Location: ' . url('/admin/articles'));
            exit;
        }

        $article = $db->queryOne("SELECT * FROM articles WHERE id = ?", [$id]);
        if (!$article) {
            http_response_code(404);
            $meta = ['title' => 'Not Found'];
            $content = '<div class="text-white">Article not found.</div>';
            include VIEWS_PATH . '/layouts/admin.php';
            return;
        }

        $meta = ['title' => 'Edit Article'];
        ob_start();
        include VIEWS_PATH . '/admin/article_edit.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function images(): void
    {
        Auth::requireAuth();
        $images = ImageLibrary::getNeedsReview();
        $meta = ['title' => 'Image Review'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/images.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function approveImage(string $id): void
    {
        Auth::requireAuth();
        ImageLibrary::updateStatus((int) $id, 'ready');
        header('Location: ' . url('/admin/images'));
        exit;
    }

    public function rejectImage(string $id): void
    {
        Auth::requireAuth();
        ImageLibrary::updateStatus((int) $id, 'rejected');
        header('Location: ' . url('/admin/images'));
        exit;
    }

    public function affiliates(): void
    {
        Auth::requireAuth();
        $affiliates = AffiliateLink::getAll();
        $pending = AffiliateLink::getPending();
        $meta = ['title' => 'Affiliates'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/affiliates.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function createAffiliate(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $brandName = trim($_POST['brand_name'] ?? '');
            $affiliateUrl = trim($_POST['affiliate_url'] ?? '');
            $brandSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $brandName));
            if ($brandName && $affiliateUrl) {
                // Ensure unique slug
                $existing = $db->queryOne("SELECT id FROM affiliate_links WHERE brand_slug = ?", [$brandSlug]);
                if ($existing) {
                    $brandSlug .= '-' . time();
                }
                $db->execute(
                    "INSERT INTO affiliate_links (brand_name, brand_slug, affiliate_url, status, created_at, updated_at) VALUES (?, ?, ?, 'active', NOW(), NOW())",
                    [$brandName, $brandSlug, $affiliateUrl]
                );
                header('Location: ' . url('/admin/affiliates'));
                exit;
            }
        }
        $meta = ['title' => 'Add Affiliate'];
        ob_start();
        include VIEWS_PATH . '/admin/affiliate_create.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function activateAffiliate(): void
    {
        Auth::requireAuth();
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/admin/affiliates'));
            exit;
        }

        $affiliateUrl = $this->normalizeAffiliateUrl((string) ($_POST['affiliate_url'] ?? ''));
        if ($affiliateUrl === '') {
            header('Location: ' . url('/admin/affiliates'));
            exit;
        }

        AffiliateLink::update($id, [
            'affiliate_url' => $affiliateUrl,
            'status' => 'active',
        ]);

        // Push real URL into all articles that have dummy/placeholder links for this brand
        $updated = AffiliateLink::updateAllArticles($id);

        header('Location: ' . url('/admin/affiliates') . '?activated=' . $id . '&articles_updated=' . $updated);
        exit;
    }

    public function updateAffiliate(): void
    {
        Auth::requireAuth();
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/admin/affiliates'));
            exit;
        }

        $data = [];
        if (isset($_POST['affiliate_url'])) {
            $data['affiliate_url'] = $this->normalizeAffiliateUrl((string) $_POST['affiliate_url']);
        }
        if (isset($_POST['status']) && in_array($_POST['status'], ['active', 'pending', 'paused', 'expired', 'inactive'], true)) {
            $data['status'] = $_POST['status'];
        }
        if (isset($_POST['brand_name'])) {
            $data['brand_name'] = trim($_POST['brand_name']);
        }
        if (isset($_POST['notes'])) {
            $data['notes'] = trim($_POST['notes']);
        }

        if ($data) {
            AffiliateLink::update($id, $data);

            // If URL was changed, push it into all articles
            if (array_key_exists('affiliate_url', $data) && $data['affiliate_url'] !== '') {
                AffiliateLink::updateAllArticles($id);
            }
        }

        header('Location: ' . url('/admin/affiliates') . '?updated=' . $id);
        exit;
    }

    /**
     * Ensures affiliate links are stored as absolute URLs.
     *
     * @param string $url
     * @return string
     */
    private function normalizeAffiliateUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, '#')) {
            return $url;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        return $url;
    }

    public function deleteAffiliate(): void
    {
        Auth::requireAuth();
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id) {
            Database::getInstance()->execute('DELETE FROM affiliate_link_usage WHERE affiliate_link_id = ?', [$id]);
            Database::getInstance()->execute('DELETE FROM affiliate_links WHERE id = ?', [$id]);
        }
        header('Location: ' . url('/admin/affiliates') . '?deleted=1');
        exit;
    }

    public function roadmap(): void
    {
        Auth::requireAuth();
        $status = $_GET['status'] ?? 'pending';
        $result = RoadmapItem::getAll($status);
        $counts = RoadmapItem::countByStatus();
        $items = $result['items'] ?? [];
        $meta = ['title' => 'Roadmap'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/roadmap.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function createRoadmapItem(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $keyword = trim($_POST['primary_keyword'] ?? '');
            $type = $_POST['content_type'] ?? 'blog';
            $priority = (int) ($_POST['priority'] ?? 3);

            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
            $existing = $db->queryOne("SELECT id FROM roadmap_items WHERE slug = ?", [$slug]);
            if ($existing) {
                $slug .= '-' . time();
            }

            if ($title) {
                $db->execute(
                    "INSERT INTO roadmap_items (title, slug, primary_keyword, content_type, priority, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())",
                    [$title, $slug, $keyword, $type, $priority]
                );
                header('Location: ' . url('/admin/roadmap'));
                exit;
            }
        }
        $meta = ['title' => 'Add Keyword'];
        ob_start();
        include VIEWS_PATH . '/admin/roadmap_create.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function costs(): void
    {
        Auth::requireAuth();
        $breakdown = CostRecord::getDailyBreakdown(30);
        $today = CostRecord::getTotalToday();
        $month = CostRecord::getTotalThisMonth();
        $meta = ['title' => 'AI Costs'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/costs.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function settings(): void
    {
        Auth::requireAuth();
        $flashMessage = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $allowed = [
                'site_name',
                'api_key',
                'n8n_webhook_url',
                'gemini_api_key',
                'fal_api_key',
                'youtube_api_key',
                'telegram_bot_token',
                'telegram_chat_id',
            ];
            foreach ($allowed as $key) {
                if (isset($_POST[$key])) {
                    Setting::set($key, $_POST[$key]);
                }
            }
            $flashMessage = 'Settings saved.';
        }
        $all = Setting::getAll();
        $meta = ['title' => 'Settings'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/settings.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }

    public function clearCache(): void
    {
        Auth::requireAuth();
        (new Cache())->clear();
        $flashMessage = 'Cache cleared successfully.';
        // Re-render dashboard with flash
        $statusCounts = Article::countByStatus();
        $stats = [
            'articles' => Article::count(),
            'reviews' => Article::count('review'),
            'comparisons' => Article::count('comparison'),
            'news' => Article::count('news'),
            'pending' => $statusCounts['draft'] ?? 0,
            'affiliates' => count(AffiliateLink::getActive()),
            'roadmap' => RoadmapItem::countByStatus(),
            'cost_today' => CostRecord::getTotalToday(),
            'cost_month' => CostRecord::getTotalThisMonth(),
            'recent_knowledge' => KnowledgeItem::getRecent(5),
        ];
        $meta = ['title' => 'Dashboard'];
        $content = '';
        ob_start();
        include VIEWS_PATH . '/admin/dashboard.php';
        $content = ob_get_clean();
        include VIEWS_PATH . '/layouts/admin.php';
    }
}
