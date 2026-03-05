<?php
declare(strict_types=1);

/**
 * HomeController
 * Renders the DevLync homepage with recent content across all types.
 */
class HomeController
{
    /**
     * Renders the homepage with recent articles, reviews, comparisons, and news.
     *
     * @return void
     */
    public function index(): void
    {
        global $tpl;

        $latestArticles = Article::getRecent(6);
        $latestReviews = Article::getPublished('review', 1, 4)['items'];
        $latestComparisons = Article::getPublished('comparison', 1, 3)['items'];
        $latestNews = Article::getPublished('news', 1, 4)['items'];
        $categories = Category::getActive();

        $meta = [
            'title' => 'DevLync — Developer Tools Discovery & Reviews',
            'description' => 'Discover the best developer tools with honest reviews, detailed comparisons, and the latest news. DevLync helps developers choose the right tools faster.',
            'canonical' => 'https://devlync.com',
            'og_type' => 'website',
            'og_image' => 'https://devlync.com/assets/images/og-default.webp',
        ];

        $schemaMarkup = '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'DevLync',
            'url' => 'https://devlync.com',
            'description' => 'Developer tools discovery, reviews, and comparisons.',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => 'https://devlync.com/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ]) . '</script>';

        $footerCategories = $categories;
        $content = $tpl->renderPartial('home/index', compact(
            'latestArticles',
            'latestReviews',
            'latestComparisons',
            'latestNews',
            'categories',
            'meta',
            'schemaMarkup'
        ));

        include VIEWS_PATH . '/layouts/main.php';
    }
}
