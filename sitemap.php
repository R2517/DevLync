<?php
declare(strict_types=1);

/**
 * Sitemap Generator
 * Renders XML sitemap for all published articles, categories, and static pages.
 * Access: /sitemap.xml
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/Article.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Author.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
$siteUrl = 'https://devlync.com';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- Static Pages -->
    <url>
        <loc><?= $siteUrl ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <?php foreach (['blog', 'reviews', 'comparisons', 'news', 'about', 'editorial-policy', 'affiliate-disclosure', 'contact', 'privacy-policy', 'fact-checking-policy'] as $page): ?>
        <url>
            <loc><?= $siteUrl ?>/<?= $page ?></loc>
            <changefreq>weekly</changefreq>
            <priority><?= in_array($page, ['blog', 'reviews', 'comparisons', 'news']) ? '0.9' : '0.5' ?></priority>
        </url>
    <?php endforeach; ?>

    <!-- Categories -->
    <?php foreach (Category::getActive() as $cat): ?>
        <url>
            <loc><?= $siteUrl ?>/category/<?= htmlspecialchars($cat['slug']) ?></loc>
            <changefreq>weekly</changefreq>
            <priority>0.7</priority>
        </url>
    <?php endforeach; ?>

    <!-- Authors -->
    <?php foreach (Author::getAll() as $author): ?>
        <url>
            <loc><?= $siteUrl ?>/author/<?= htmlspecialchars($author['slug']) ?></loc>
            <changefreq>weekly</changefreq>
            <priority>0.6</priority>
        </url>
    <?php endforeach; ?>

    <!-- Articles -->
    <?php
    $typeMap = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'];
    foreach (Article::getSitemapArticles() as $article):
        $base = $typeMap[$article['content_type']] ?? 'blog';
        $loc = $siteUrl . '/' . $base . '/' . htmlspecialchars($article['slug']);
        $modified = $article['updated_content_at'] ?? $article['updated_at'] ?? $article['published_at'];
        $priority = ['review' => '0.8', 'comparison' => '0.8', 'blog' => '0.7', 'news' => '0.6'][$article['content_type']] ?? '0.6';
        ?>
        <url>
            <loc><?= $loc ?></loc>
            <?php if ($modified): ?>
                <lastmod><?= date('Y-m-d', strtotime($modified)) ?></lastmod>
            <?php endif; ?>
            <changefreq>monthly</changefreq>
            <priority><?= $priority ?></priority>
            <?php if (!empty($article['featured_image_url'])): ?>
                <image:image>
                    <image:loc><?= (str_starts_with($article['featured_image_url'], 'http') ? '' : $siteUrl) . htmlspecialchars($article['featured_image_url']) ?></image:loc>
                    <?php if (!empty($article['featured_image_alt'])): ?>
                        <image:caption><?= htmlspecialchars($article['featured_image_alt']) ?></image:caption>
                    <?php endif; ?>
                </image:image>
            <?php endif; ?>
        </url>
    <?php endforeach; ?>
</urlset>