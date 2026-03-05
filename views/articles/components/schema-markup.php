<?php
/**
 * Schema Markup Component
 * Generates JSON-LD structured data for any article type.
 * Variables: $article (array), $tags (array), $canonicalUrl (string)
 */
$type = $article['content_type'] ?? 'blog';
$siteUrl = 'https://devlync.com';
$schema = [];

$baseArticle = [
    '@context' => 'https://schema.org',
    'headline' => $article['title'] ?? '',
    'description' => $article['meta_description'] ?? $article['excerpt'] ?? '',
    'url' => $canonicalUrl ?? ($siteUrl . '/' . (['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'][$type] ?? 'blog') . '/' . ($article['slug'] ?? '')),
    'datePublished' => $article['published_at'] ?? '',
    'dateModified' => $article['updated_content_at'] ?? $article['updated_at'] ?? '',
    'wordCount' => (int) ($article['word_count'] ?? 0),
    'author' => [
        '@type' => 'Organization',
        'name' => $article['author_name'] ?? 'DevLync Team',
        'url' => $siteUrl . '/author/' . ($article['author_slug'] ?? 'devlync-team'),
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'DevLync',
        'url' => $siteUrl,
        'logo' => ['@type' => 'ImageObject', 'url' => $siteUrl . '/assets/images/logo.webp'],
    ],
];

if (!empty($article['featured_image_url'])) {
    $baseArticle['image'] = [
        '@type' => 'ImageObject',
        'url' => (str_starts_with($article['featured_image_url'], 'http') ? '' : $siteUrl) . $article['featured_image_url'],
        'width' => 1200,
        'height' => 675,
    ];
}

switch ($type) {
    case 'review':
        $schema = array_merge(['@type' => 'Review'], $baseArticle);
        if (!empty($article['overall_rating'])) {
            $schema['reviewRating'] = [
                '@type' => 'Rating',
                'ratingValue' => (float) $article['overall_rating'],
                'bestRating' => 10,
                'worstRating' => 0,
            ];
        }
        $schema['itemReviewed'] = [
            '@type' => 'SoftwareApplication',
            'name' => preg_replace('/\s+(review|rating|analysis).*/i', '', $article['title'] ?? ''),
        ];
        break;

    case 'comparison':
        $schema = array_merge(['@type' => 'Article'], $baseArticle);
        break;

    case 'news':
        $schema = array_merge(['@type' => 'NewsArticle'], $baseArticle);
        if (!empty($article['dateline'])) {
            $schema['dateline'] = $article['dateline'];
        }
        break;

    default: // blog
        $schema = array_merge(['@type' => 'BlogPosting'], $baseArticle);
        break;
}

// Breadcrumb List
$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $siteUrl],
        ['@type' => 'ListItem', 'position' => 2, 'name' => ['blog' => 'Blog', 'review' => 'Reviews', 'comparison' => 'Comparisons', 'news' => 'News'][$type] ?? ucfirst($type), 'item' => $siteUrl . '/' . (['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'][$type] ?? 'blog')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $article['title'] ?? ''],
    ],
];

echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
echo '<script type="application/ld+json">' . json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
