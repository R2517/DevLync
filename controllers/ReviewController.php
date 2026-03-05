<?php
declare(strict_types=1);

/**
 * ReviewController
 * Handles /reviews listing and /reviews/{slug} single review pages.
 */
class ReviewController
{
    private const TYPE = 'review';

    /**
     * Renders the /reviews listing page with pagination.
     *
     * @return void
     */
    public function index(): void
    {
        global $tpl;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Article::getPublished(self::TYPE, $page);
        $meta = [
            'title' => 'Developer Tool Reviews — Honest Ratings | DevLync',
            'description' => 'Read our in-depth developer tool reviews. Every tool is tested in real projects with honest ratings, pros/cons analysis, and final verdicts.',
            'canonical' => 'https://devlync.com/reviews' . ($page > 1 ? '?page=' . $page : ''),
        ];
        $schemaMarkup = '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Developer Tool Reviews',
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'DevLync', 'url' => 'https://devlync.com'],
        ], JSON_UNESCAPED_SLASHES) . '</script>';
        $content = $tpl->renderPartial('articles/listing', array_merge($result, [
            'type' => self::TYPE,
            'typeLabel' => 'Reviews',
            'typeDesc' => 'In-depth developer tool reviews with real testing, honest ratings, and clear verdicts.',
            'page' => $page,
        ]));
        $footerCategories = Category::getActive();
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * Renders the /reviews/{slug} single review page.
     *
     * @param string $slug Article slug
     * @return void
     */
    public function show(string $slug): void
    {
        global $tpl;
        $cache = new Cache();
        $cacheKey = 'review_' . $slug;
        $cached = $cache->get($cacheKey);

        if (!$cached) {
            $article = Article::getBySlug($slug);
            if (!$article || $article['content_type'] !== self::TYPE) {
                http_response_code(404);
                include VIEWS_PATH . '/errors/404.php';
                return;
            }
            require_once __DIR__ . '/../models/AffiliateLink.php';
            $tags = Tag::getForArticle($article['id']);
            $relatedPosts = Article::getRelated($article['id'], 4);
            $cached = compact('article', 'tags', 'relatedPosts');
            $cache->set($cacheKey, $cached, CACHE_TTL_ARTICLE);
        }

        extract($cached);
        $meta = [
            'title' => $article['meta_title'] ?: ($article['title'] . ' | DevLync'),
            'description' => $article['meta_description'] ?: $article['excerpt'],
            'canonical' => 'https://devlync.com/reviews/' . $article['slug'],
            'og_type' => 'article',
            'og_image' => $article['featured_image_url'] ?? null,
            'og_image_alt' => $article['featured_image_alt'] ?? null,
        ];
        $schemaMarkup = '';
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('articles/review', compact('article', 'tags', 'relatedPosts'));
        include VIEWS_PATH . '/layouts/main.php';
    }
}
