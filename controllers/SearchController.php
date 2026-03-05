<?php
declare(strict_types=1);

/**
 * SearchController
 * Handles /search?q= full-text search.
 */
class SearchController
{
    /**
     * Renders the search results page.
     *
     * @return void
     */
    public function index(): void
    {
        global $tpl;
        $query = trim(strip_tags($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $results = [];
        $total = 0;
        $pages = 1;

        if (strlen($query) >= 2) {
            $result = Article::search($query, $page);
            $results = $result['items'];
            $total = $result['total'];
            $pages = $result['pages'];
        }

        $meta = [
            'title' => $query ? '"' . $query . '" — Search Results | DevLync' : 'Search Developer Tools, Reviews & Guides | DevLync',
            'description' => $query
                ? 'Search results for "' . $query . '" on DevLync. Find developer tool reviews, comparisons, guides, and the latest software engineering news.'
                : 'Search DevLync for in-depth developer tool reviews, side-by-side comparisons, coding tutorials, and the latest software engineering news.',
            'robots' => 'noindex, follow',
            'canonical' => 'https://devlync.com/search' . ($query ? '?q=' . urlencode($query) : ''),
        ];
        $schemaMarkup = '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'SearchResultsPage',
            'name' => $meta['title'],
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'DevLync',
                'url' => 'https://devlync.com',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => 'https://devlync.com/search?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES) . '</script>';
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('search/index', compact('query', 'results', 'total', 'page', 'pages'));
        include VIEWS_PATH . '/layouts/main.php';
    }
}
