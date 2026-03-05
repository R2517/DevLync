<?php
declare(strict_types=1);

/**
 * TagController
 * Handles /tag/{slug} tag archive pages.
 */
class TagController
{
    /**
     * Renders a tag archive page with paginated articles.
     *
     * @param string $slug Tag slug
     * @return void
     */
    public function show(string $slug): void
    {
        global $tpl;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $tag = Tag::getBySlug($slug);
        if (!$tag) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }
        $result = Article::getByTagId($tag['id'], $page);
        $meta = [
            'title' => '#' . $tag['name'] . ' — Tagged Articles | DevLync',
            'description' => 'Browse articles tagged "' . $tag['name'] . '" on DevLync.',
            'canonical' => 'https://devlync.com/tag/' . $tag['slug'] . ($page > 1 ? '?page=' . $page : ''),
            'robots' => 'noindex, follow',
        ];
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('tag/show', compact('tag', 'result', 'page'));
        include VIEWS_PATH . '/layouts/main.php';
    }
}
