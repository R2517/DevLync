<?php
declare(strict_types=1);

/**
 * BlogController
 * Handles /blog listing and /blog/{slug} single article pages.
 */
class BlogController
{
    private const TYPE = 'blog';

    /**
     * Renders the /blog listing page with pagination.
     *
     * @return void
     */
    public function index(): void
    {
        global $tpl;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Article::getPublished(self::TYPE, $page);
        $meta = [
            'title' => 'Developer Blog — Tips, Guides & Tutorials | DevLync',
            'description' => 'Read developer blog posts, how-to guides, and tutorials on the best developer tools, coding workflows, and software engineering best practices.',
            'canonical' => 'https://devlync.com/blog' . ($page > 1 ? '?page=' . $page : ''),
        ];
        $schemaMarkup = '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Developer Blog',
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'DevLync', 'url' => 'https://devlync.com'],
        ], JSON_UNESCAPED_SLASHES) . '</script>';
        $content = $tpl->renderPartial('articles/listing', array_merge($result, [
            'type' => self::TYPE,
            'typeLabel' => 'Blog',
            'typeDesc' => 'Developer guides, tutorials, and insights from the DevLync team.',
            'page' => $page,
        ]));
        $footerCategories = Category::getActive();
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * Renders the /blog/{slug} single article page.
     *
     * @param string $slug Article slug
     * @return void
     */
    public function show(string $slug): void
    {
        global $tpl;
        $cache = new Cache();
        $cacheKey = 'blog_' . $slug;
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
            'canonical' => 'https://devlync.com/blog/' . $article['slug'],
            'og_type' => 'article',
            'og_image' => $article['featured_image_url'] ?? null,
            'og_image_alt' => $article['featured_image_alt'] ?? null,
        ];
        $schemaMarkup = '';
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('articles/blog', compact('article', 'tags', 'relatedPosts'));
        include VIEWS_PATH . '/layouts/main.php';
    }
}
