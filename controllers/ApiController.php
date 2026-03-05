<?php
declare(strict_types=1);

/**
 * ApiController
 * All n8n webhook endpoints. Requires X-API-Key header.
 */
class ApiController
{
    /**
     * Sends a JSON response and exits.
     *
     * @param mixed $data
     * @param int   $status
     * @return never
     */
    private static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * GET /api/* — Health check ping for Supervisor monitoring.
     * Returns 200 with endpoint info without triggering any action.
     */
    public function healthPing(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        self::json([
            'status' => 'ok',
            'endpoint' => parse_url($uri, PHP_URL_PATH),
            'method' => 'POST required',
            'note' => 'This endpoint accepts POST requests with X-API-Key header.',
        ]);
    }

    /**
     * POST /api/articles/publish
     * Publishes or updates an article from n8n.
     *
     * @return void
     */
    public function publishArticle(): void
    {
        ApiAuth::verify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::json(['error' => 'Method not allowed'], 405);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || empty($body['title']) || empty($body['content']) || empty($body['content_type'])) {
            self::json(['error' => 'Missing required fields: title, content, content_type'], 400);
        }

        // Prepare and upsert article
        $existingId = null;
        if (!empty($body['slug'])) {
            $existing = Article::getBySlug($body['slug']);
            if ($existing) {
                $existingId = $existing['id'];
            }
        }

        $data = [
            'title' => $body['title'],
            'slug' => $body['slug'] ?? null,
            'content' => $body['content'],
            'content_type' => $body['content_type'],
            'excerpt' => $body['excerpt'] ?? null,
            'meta_title' => $body['meta_title'] ?? null,
            'meta_description' => $body['meta_description'] ?? null,
            'direct_answer' => $body['direct_answer'] ?? null,
            'key_takeaways' => isset($body['key_takeaways']) ? json_encode($body['key_takeaways']) : null,
            'faq' => isset($body['faq']) ? json_encode($body['faq']) : null,
            'pros' => isset($body['pros']) ? json_encode($body['pros']) : null,
            'cons' => isset($body['cons']) ? json_encode($body['cons']) : null,
            'overall_rating' => $body['overall_rating'] ?? null,
            'rating_features' => $body['rating_features'] ?? null,
            'rating_pricing' => $body['rating_pricing'] ?? null,
            'rating_performance' => $body['rating_performance'] ?? null,
            'rating_support' => $body['rating_support'] ?? null,
            'rating_ease_of_use' => $body['rating_ease_of_use'] ?? null,
            'verdict' => $body['verdict'] ?? null,
            'sources' => isset($body['sources']) ? json_encode($body['sources']) : null,
            'author_id' => $body['author_id'] ?? null,
            'category_id' => $body['category_id'] ?? null,
            'status' => 'draft', // Admin review before publish
            'comparison_table' => isset($body['comparison_table']) ? json_encode($body['comparison_table']) : null,
            'winner_product' => $body['winner_product'] ?? null,
            'key_facts' => isset($body['key_facts']) ? json_encode($body['key_facts']) : null,
            'word_count' => $body['word_count'] ?? null,
            'reading_time' => $body['reading_time'] ?? null,
        ];

        if ($existingId) {
            Article::update($existingId, $data);
            $articleId = $existingId;
            $action = 'updated';
        } else {
            $articleId = Article::create($data);
            $action = 'created';
        }

        // Sync tags
        if (!empty($body['tags']) && is_array($body['tags'])) {
            Tag::syncForArticle($articleId, $body['tags']);
        }

        // Update roadmap item status
        if (!empty($body['roadmap_id'])) {
            RoadmapItem::updateStatus((int) $body['roadmap_id'], 'published', $articleId);
        }

        // Track cost
        if (!empty($body['cost'])) {
            CostRecord::create([
                'article_id' => $articleId,
                'step' => 'full_article',
                'model' => $body['cost']['model'] ?? 'unknown',
                'provider' => $body['cost']['provider'] ?? 'unknown',
                'input_tokens' => $body['cost']['input_tokens'] ?? 0,
                'output_tokens' => $body['cost']['output_tokens'] ?? 0,
                'cost' => $body['cost']['total'] ?? 0,
            ]);
        }

        // Invalidate cache
        (new Cache())->delete('blog_' . ($body['slug'] ?? ''));
        (new Cache())->delete('review_' . ($body['slug'] ?? ''));
        (new Cache())->delete('comparison_' . ($body['slug'] ?? ''));
        (new Cache())->delete('news_' . ($body['slug'] ?? ''));

        self::json(['success' => true, 'action' => $action, 'article_id' => $articleId]);
    }

    /**
     * POST /api/images/upload
     * Receives a base64-encoded image from n8n and stores it.
     *
     * @return void
     */
    public function uploadImage(): void
    {
        ApiAuth::verify();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::json(['error' => 'Method not allowed'], 405);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (empty($body['image_data']) || empty($body['article_id']) || empty($body['image_slot'])) {
            self::json(['error' => 'Missing required fields: image_data, article_id, image_slot'], 400);
        }

        $articleId = (int) $body['article_id'];
        $slot = preg_replace('/[^a-z0-9_-]/i', '', $body['image_slot']);
        $article = Article::getById($articleId);
        if (!$article) {
            self::json(['error' => 'Article not found'], 404);
        }

        $dir = IMAGES_PATH . '/' . $article['slug'];
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $imageData = base64_decode($body['image_data'], true);
        if ($imageData === false || strlen($imageData) < 8) {
            self::json(['error' => 'Invalid base64 image data'], 400);
        }
        $tmpFile = tempnam(sys_get_temp_dir(), 'dl_img_');
        file_put_contents($tmpFile, $imageData);

        $imageInfo = getimagesize($tmpFile);
        if (!$imageInfo || !in_array($imageInfo['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            unlink($tmpFile);
            self::json(['error' => 'Invalid or unsupported image format'], 400);
        }

        $filename = $slot . '.webp';
        $filepath = $dir . '/' . $filename;
        $fileUrl = '/assets/images/articles/' . $article['slug'] . '/' . $filename;

        $srcImage = match ($imageInfo['mime']) {
            'image/jpeg' => imagecreatefromjpeg($tmpFile),
            'image/png' => imagecreatefrompng($tmpFile),
            'image/webp' => imagecreatefromwebp($tmpFile),
            default => null,
        };
        unlink($tmpFile);

        if (!$srcImage) {
            self::json(['error' => 'Unsupported image format'], 400);
        }

        $origW = imagesx($srcImage);
        $origH = imagesy($srcImage);
        if ($origW > IMAGE_MAX_WIDTH) {
            $ratio = IMAGE_MAX_WIDTH / $origW;
            $newW = IMAGE_MAX_WIDTH;
            $newH = (int) ($origH * $ratio);
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $srcImage, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($srcImage);
            $srcImage = $resized;
        }

        imagewebp($srcImage, $filepath, IMAGE_QUALITY_WEBP);
        imagedestroy($srcImage);

        // Save to library
        ImageLibrary::create([
            'filename' => $filename,
            'filepath' => $filepath,
            'file_url' => $fileUrl,
            'file_size' => filesize($filepath),
            'width' => (int) (imagesx(imagecreatefromwebp($filepath))),
            'height' => (int) (imagesy(imagecreatefromwebp($filepath))),
            'format' => 'webp',
            'source_type' => $body['source_type'] ?? 'ai_generated',
            'ai_prompt' => $body['prompt'] ?? null,
            'alt_text' => $body['alt_text'] ?? null,
            'article_id' => $articleId,
            'image_slot' => $slot,
            'status' => 'needs_review',
        ]);

        // Update article field based on slot
        if ($slot === 'featured') {
            Article::updateField($articleId, 'featured_image_url', $fileUrl);
            Article::updateField($articleId, 'featured_image_alt', $body['alt_text'] ?? '');
        }

        // Invalidate article cache
        (new Cache())->delete('blog_' . $article['slug']);
        (new Cache())->delete('review_' . $article['slug']);
        (new Cache())->delete('comparison_' . $article['slug']);
        (new Cache())->delete('news_' . $article['slug']);

        self::json(['success' => true, 'file_url' => $fileUrl]);
    }

    /**
     * POST /api/knowledge/add
     * Adds scraped content to the knowledge base.
     *
     * @return void
     */
    public function addKnowledge(): void
    {
        ApiAuth::verify();
        $body = json_decode(file_get_contents('php://input'), true);
        if (empty($body['title']) || empty($body['content'])) {
            self::json(['error' => 'Missing required fields: title, content'], 400);
        }

        // Dedup check
        if (!empty($body['source_id']) && KnowledgeItem::existsBySourceId($body['source_id'])) {
            self::json(['success' => true, 'action' => 'skipped', 'reason' => 'duplicate'], 200);
        }

        $id = KnowledgeItem::create($body);
        ScrapeLog::create([
            'session_id' => $body['session_id'] ?? null,
            'source_type' => $body['source_type'] ?? 'manual',
            'query' => $body['query'] ?? null,
            'items_found' => 1,
            'items_saved' => 1,
            'items_skipped' => 0,
            'duration_seconds' => $body['duration_seconds'] ?? 0,
        ]);

        self::json(['success' => true, 'action' => 'created', 'knowledge_id' => $id]);
    }

    /**
     * POST /api/affiliate/process
     * Parses article content and inserts affiliate links.
     *
     * @return void
     */
    public function processAffiliateLinks(): void
    {
        ApiAuth::verify();
        $body = json_decode(file_get_contents('php://input'), true);
        if (empty($body['article_id'])) {
            self::json(['error' => 'Missing required field: article_id'], 400);
        }

        $articleId = (int) $body['article_id'];
        $article = Article::getById($articleId);
        if (!$article) {
            self::json(['error' => 'Article not found'], 404);
        }

        $result = AffiliateLink::processContent($article['content'], $articleId);
        Article::updateField($articleId, 'content', $result['content']);
        Article::updateField($articleId, 'has_affiliate_links', $result['linksAdded'] > 0 ? 1 : 0);

        self::json([
            'success' => true,
            'links_added' => $result['linksAdded'],
            'links_pending' => $result['linksPending'],
        ]);
    }

    /**
     * POST /api/cache/clear
     * Clears the file cache for specific URLs or all pages.
     *
     * @return void
     */
    public function clearCache(): void
    {
        ApiAuth::verify();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $cache = new Cache();
        if (!empty($body['url'])) {
            $cache->deleteForUrl($body['url']);
            self::json(['success' => true, 'cleared' => $body['url']]);
        } else {
            $cache->clear();
            self::json(['success' => true, 'cleared' => 'all']);
        }
    }

    /**
     * GET /api/track-click?brand=
     * Tracks affiliate link click for analytics.
     *
     * @return void
     */
    public function trackClick(): void
    {
        $brand = $_GET['brand'] ?? '';
        if ($brand) {
            AffiliateLink::trackClick($brand);
        }
        http_response_code(204);
        exit;
    }

    /**
     * POST /api/roadmap/add
     * Adds a batch of keyword roadmap items from n8n.
     *
     * @return void
     */
    public function addRoadmapItems(): void
    {
        ApiAuth::verify();
        $body = json_decode(file_get_contents('php://input'), true);
        if (empty($body['items']) || !is_array($body['items'])) {
            self::json(['error' => 'Missing required field: items (array)'], 400);
        }

        $created = 0;
        foreach ($body['items'] as $item) {
            if (empty($item['title']) || empty($item['content_type'])) {
                continue;
            }
            RoadmapItem::create($item);
            $created++;
        }

        self::json(['success' => true, 'created' => $created]);
    }
}
