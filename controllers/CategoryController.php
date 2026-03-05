<?php
declare(strict_types=1);

/**
 * CategoryController
 * Handles /category/{slug} pillar pages.
 */
class CategoryController
{
    /**
     * Renders a category pillar page with paginated articles.
     *
     * @param string $slug Category slug
     * @return void
     */
    public function show(string $slug): void
    {
        global $tpl;
        $cache = new Cache();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $cacheKey = 'cat_' . $slug . '_p' . $page;
        $cached = $cache->get($cacheKey);

        if (!$cached) {
            $category = Category::getBySlug($slug);
            if (!$category) {
                http_response_code(404);
                include VIEWS_PATH . '/errors/404.php';
                return;
            }
            $result = Article::getByCategoryId($category['id'], $page);
            $cached = compact('category', 'result');
            $cache->set($cacheKey, $cached, CACHE_TTL_PAGE);
        }

        extract($cached);
        $meta = [
            'title' => ($category['meta_title'] ?: $category['name'] . ' — Developer Tools | DevLync'),
            'description' => $category['meta_description'] ?: $category['description'],
            'canonical' => 'https://devlync.com/category/' . $category['slug'] . ($page > 1 ? '?page=' . $page : ''),
        ];
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('category/show', compact('category', 'result', 'page'));
        include VIEWS_PATH . '/layouts/main.php';
    }
}
