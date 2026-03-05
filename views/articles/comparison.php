<?php
/**
 * Comparison Article Template
 * Variables: $article (array), $tags (array), $relatedPosts (array)
 */
$_dj = fn($v) => is_string($v) && $v !== '' ? (json_decode($v, true) ?? []) : (is_array($v) ? $v : []);
$faq             = $_dj($article['faq'] ?? null);
$sources         = $_dj($article['sources'] ?? null);
$keyTakeaways    = $_dj($article['key_takeaways'] ?? null);
$comparisonTable = is_string($article['comparison_table'] ?? null) && ($article['comparison_table'] ?? '') !== '' ? (json_decode($article['comparison_table'], true) ?? null) : ($article['comparison_table'] ?? null);
$productReviews  = $_dj($article['product_reviews'] ?? null);
$canonicalUrl = 'https://devlync.com/comparisons/' . $article['slug'];
// Safety: decode entity-encoded HTML content (may occur from API imports)
if (!empty($article['content'])) {
    $article['content'] = html_entity_decode($article['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
// Post-process content: fix raw pipe tables, strip duplicate FAQ
require_once __DIR__ . '/components/content-processor.php';
if (!empty($article['content'])) {
    $article['content'] = processArticleContent($article['content']);
}
?>
<div id="reading-progress" class="fixed top-0 left-0 h-0.5 bg-green-600 z-50" style="width:0%"></div>
<?php include __DIR__ . '/components/schema-markup.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <article class="max-w-4xl mx-auto">
        <?php
        $breadcrumbs = [
            ['label' => 'Home', 'url' => url('/')],
            ['label' => 'Comparisons', 'url' => url('/comparisons')],
            ['label' => $article['title'], 'url' => url('/comparisons/' . $article['slug'])],
        ];
        include __DIR__ . '/components/breadcrumb.php';
        ?>

        <!-- Header -->
        <header class="mb-6">
            <div class="flex items-center gap-2 mb-3">
                <span
                    class="bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">Comparison</span>
                <?php if (!empty($article['category_name'])): ?>
                    <a href="<?= url('/category/' . htmlspecialchars($article['category_slug'])) ?>"
                        class="bg-gray-100 text-gray-600 text-xs font-medium px-2.5 py-1 rounded-full">
                        <?= htmlspecialchars($article['category_name']) ?>
                    </a>
                <?php endif; ?>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight mb-4">
                <?= htmlspecialchars($article['title']) ?>
            </h1>
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                <span class="flex items-center gap-1"><i data-lucide="user" class="w-4 h-4"></i>
                    <a href="<?= url('/author/' . htmlspecialchars($article['author_slug'] ?? 'devlync-team')) ?>"
                        class="hover:text-blue-600 font-medium">
                        <?= htmlspecialchars($article['author_name'] ?? 'DevLync Team') ?>
                    </a>
                </span>
                <?php if ($article['published_at']): ?>
                    <time datetime="<?= date('Y-m-d', strtotime($article['published_at'])) ?>">
                        <?= date('F j, Y', strtotime($article['published_at'])) ?>
                    </time>
                <?php endif; ?>
                <?php if ($article['reading_time']): ?>
                    <span>
                        <?= (int) $article['reading_time'] ?> min read
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <!-- Quick Winner Box -->
        <?php if (!empty($article['winner_product'])): ?>
            <div class="my-5 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-2xl p-5 not-prose">
                <p class="text-xs font-semibold uppercase tracking-widest mb-1 text-amber-100">🏆 Winner</p>
                <p class="text-xl font-extrabold">
                    <?= htmlspecialchars($article['winner_product']) ?>
                </p>
                <?php if (!empty($article['winner_reason'])): ?>
                    <p class="text-sm text-amber-100 mt-1">
                        <?= htmlspecialchars($article['winner_reason']) ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Featured Image -->
        <?php if ($article['featured_image_url']): ?>
            <figure class="mb-6">
                <img src="<?= htmlspecialchars($article['featured_image_url']) ?>"
                    alt="<?= htmlspecialchars($article['featured_image_alt'] ?? $article['title']) ?>"
                    class="w-full rounded-2xl object-cover max-h-80" loading="eager">
            </figure>
        <?php endif; ?>

        <!-- Quick Answer -->
        <?php if (!empty($article['direct_answer'])): ?>
            <?php $directAnswer = $article['direct_answer'];
            include __DIR__ . '/components/quick-answer.php'; ?>
        <?php endif; ?>

        <!-- Key Takeaways -->
        <?php if (!empty($keyTakeaways)): ?>
            <?php include __DIR__ . '/components/key-takeaways.php'; ?>
        <?php endif; ?>

        <!-- TOC -->
        <?php if (!empty($article['content'])): ?>
            <?php $content = $article['content'];
            include __DIR__ . '/components/toc.php'; ?>
        <?php endif; ?>

        <!-- Comparison Table -->
        <?php if (!empty($comparisonTable)): ?>
            <?php include __DIR__ . '/components/comparison-table.php'; ?>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="article-content mt-6">
            <?= $article['content'] ?>
        </div>

        <!-- Affiliate Disclosure -->
        <?php if ($article['has_affiliate_links']): ?>
            <?php $ftcDisclosure = $article['ftc_disclosure'];
            include __DIR__ . '/components/affiliate-disclosure.php'; ?>
        <?php endif; ?>

        <!-- FAQ -->
        <?php if (!empty($faq)): ?>
            <?php include __DIR__ . '/components/faq-accordion.php'; ?>
        <?php endif; ?>

        <!-- Sources -->
        <?php if (!empty($sources)): ?>
            <?php include __DIR__ . '/components/sources-box.php'; ?>
        <?php endif; ?>

        <!-- Author Box -->
        <?php include __DIR__ . '/components/author-box.php'; ?>

        <!-- Related Posts -->
        <?php if (!empty($relatedPosts)): ?>
            <?php include __DIR__ . '/components/related-posts.php'; ?>
        <?php endif; ?>

        <!-- Tags -->
        <?php if (!empty($tags)): ?>
            <div class="flex flex-wrap gap-2 mt-4">
                <?php foreach ($tags as $tag): ?>
                    <a href="<?= url('/tag/' . htmlspecialchars($tag['slug'])) ?>"
                        class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full hover:bg-green-100 hover:text-green-700 transition-colors">
                        #
                        <?= htmlspecialchars($tag['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Social Share -->
        <?php include __DIR__ . '/components/social-share.php'; ?>
    </article>
</div>