<?php
declare(strict_types=1);

/**
 * RSS Feed Generator
 * Access: /feed
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/Article.php';

header('Content-Type: application/rss+xml; charset=utf-8');
$siteUrl = 'https://devlync.com';
$articles = Article::getRecent(20);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>DevLync — Developer Tools Reviews &amp; News</title>
        <link>
        <?= $siteUrl ?>
        </link>
        <description>Honest developer tool reviews, comparisons, and the latest ecosystem news.</description>
        <language>en-us</language>
        <lastBuildDate>
            <?= date('r') ?>
        </lastBuildDate>
        <atom:link href="<?= $siteUrl ?>/feed" rel="self" type="application/rss+xml" />
        <image>
            <url>
                <?= $siteUrl ?>/assets/images/favicon.png
            </url>
            <title>DevLync</title>
            <link>
            <?= $siteUrl ?>
            </link>
        </image>
        <?php
        $typeMap = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'];
        foreach ($articles as $article):
            $base = $typeMap[$article['content_type']] ?? 'blog';
            $url = $siteUrl . '/' . $base . '/' . $article['slug'];
            $date = $article['published_at'] ? date('r', strtotime($article['published_at'])) : date('r');
            ?>
            <item>
                <title>
                    <![CDATA[<?= $article['title'] ?>]]>
                </title>
                <link>
                <?= $url ?>
                </link>
                <guid isPermaLink="true">
                    <?= $url ?>
                </guid>
                <pubDate>
                    <?= $date ?>
                </pubDate>
                <category>
                    <?= ucfirst($article['content_type']) ?>
                </category>
                <?php if (!empty($article['excerpt'])): ?>
                    <description>
                        <![CDATA[<?= $article['excerpt'] ?>]]>
                    </description>
                <?php endif; ?>
                <?php if (!empty($article['featured_image_url'])): ?>
                    <enclosure url="<?= $siteUrl . $article['featured_image_url'] ?>" type="image/webp" length="0" />
                <?php endif; ?>
            </item>
        <?php endforeach; ?>
    </channel>
</rss>