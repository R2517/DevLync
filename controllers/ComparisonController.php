<?php
declare(strict_types=1);

/**
 * ComparisonController
 * Handles /comparisons listing and /comparisons/{slug} pages.
 */
class ComparisonController
{
    private const TYPE = 'comparison';

    /**
     * Renders the /comparisons listing page.
     *
     * @return void
     */
    public function index(): void
    {
        global $tpl;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Article::getPublished(self::TYPE, $page);
        $meta = [
            'title' => 'Developer Tool Comparisons — Side-by-Side Analysis | DevLync',
            'description' => 'Compare developer tools side-by-side with detailed feature analysis, pricing breakdowns, and clear winner recommendations.',
            'canonical' => 'https://devlync.com/comparisons' . ($page > 1 ? '?page=' . $page : ''),
        ];
        $schemaMarkup = '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Developer Tool Comparisons',
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'DevLync', 'url' => 'https://devlync.com'],
        ], JSON_UNESCAPED_SLASHES) . '</script>';
        $content = $tpl->renderPartial('articles/listing', array_merge($result, [
            'type' => self::TYPE,
            'typeLabel' => 'Comparisons',
            'typeDesc' => 'Side-by-side developer tool comparisons with comparison tables and clear winners.',
            'page' => $page,
        ]));
        $footerCategories = Category::getActive();
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * Renders the /comparisons/{slug} single comparison page.
     *
     * @param string $slug Article slug
     * @return void
     */
    public function show(string $slug): void
    {
        global $tpl;
        $cache = new Cache();
        $cacheKey = 'comparison_' . $slug;
        $cached = $cache->get($cacheKey);

        if (!$cached) {
            $article = Article::getBySlug($slug);
            if (!$article || $article['content_type'] !== self::TYPE) {
                http_response_code(404);
                include VIEWS_PATH . '/errors/404.php';
                return;
            }
            $tags = Tag::getForArticle($article['id']);
            $relatedPosts = Article::getRelated($article['id'], 4);
            $cached = compact('article', 'tags', 'relatedPosts');
            $cache->set($cacheKey, $cached, CACHE_TTL_ARTICLE);
        }

        extract($cached);
        $meta = [
            'title' => $article['meta_title'] ?: ($article['title'] . ' | DevLync'),
            'description' => $article['meta_description'] ?: $article['excerpt'],
            'canonical' => 'https://devlync.com/comparisons/' . $article['slug'],
            'og_type' => 'article',
            'og_image' => $article['featured_image_url'] ?? null,
        ];
        $schemaMarkup = '';
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('articles/comparison', compact('article', 'tags', 'relatedPosts'));
        include VIEWS_PATH . '/layouts/main.php';
    }
}
