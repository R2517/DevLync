<?php
declare(strict_types=1);

/**
 * AuthorController
 * Handles /author/{slug} EEAT author profile pages.
 */
class AuthorController
{
    /**
     * Renders an author profile page with their published articles.
     *
     * @param string $slug Author slug
     * @return void
     */
    public function show(string $slug): void
    {
        global $tpl;
        $author = Author::getBySlug($slug);
        if (!$author) {
            http_response_code(404);
            include VIEWS_PATH . '/errors/404.php';
            return;
        }
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Article::getByAuthorId($author['id'], $page);
        $meta = [
            'title' => $author['name'] . ' — Author | DevLync',
            'description' => $author['bio'] ? substr($author['bio'], 0, 155) : ('Articles by ' . $author['name'] . ' on DevLync.'),
            'canonical' => 'https://devlync.com/author/' . $author['slug'],
        ];
        // Author schema
        $authorSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $author['name'],
            'url' => 'https://devlync.com/author/' . $author['slug'],
            'description' => $author['bio'] ?? '',
        ];
        if (!empty($author['avatar_url'])) {
            $authorSchema['image'] = 'https://devlync.com' . $author['avatar_url'];
        }
        $schemaMarkup = '<script type="application/ld+json">' . json_encode($authorSchema) . '</script>';
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('author/show', compact('author', 'result', 'page', 'schemaMarkup'));
        include VIEWS_PATH . '/layouts/main.php';
    }
}
