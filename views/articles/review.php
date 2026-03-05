<?php
/**
 * Review Article Template
 * Variables: $article (array), $tags (array), $relatedPosts (array), $tpl (Template)
 */
$_dj = fn($v) => is_string($v) && $v !== '' ? (json_decode($v, true) ?? []) : (is_array($v) ? $v : []);
$pros           = $_dj($article['pros'] ?? null);
$cons           = $_dj($article['cons'] ?? null);
$faq            = $_dj($article['faq'] ?? null);
$sources        = $_dj($article['sources'] ?? null);
$specifications = $_dj($article['specifications'] ?? null);
$alternatives   = $_dj($article['alternatives'] ?? null);
$keyTakeaways   = $_dj($article['key_takeaways'] ?? null);
$canonicalUrl = 'https://devlync.com/reviews/' . $article['slug'];

// Safety: decode entity-encoded HTML content (may occur from API imports)
if (!empty($article['content'])) {
    $article['content'] = html_entity_decode($article['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
// Post-process content: fix raw pipe tables, strip duplicate FAQ
require_once __DIR__ . '/components/content-processor.php';
if (!empty($article['content'])) {
    $article['content'] = processArticleContent($article['content']);
}

// Get affiliate link for this product
$affiliateUrl = null;
$productName = preg_replace('/\s+(review|rating|analysis).*/i', '', $article['title']);
$productSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $productName));
$affiliateData = AffiliateLink::getByBrandSlug($productSlug);
if ($affiliateData && $affiliateData['status'] === 'active') {
    $affiliateUrl = $affiliateData['affiliate_url'];
}
?>
<div id="reading-progress" class="fixed top-0 left-0 h-0.5 bg-purple-600 z-50" style="width:0%"></div>

<!-- Schema Markup -->
<?php include __DIR__ . '/components/schema-markup.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="lg:flex lg:gap-10">

        <article class="min-w-0 flex-1 max-w-3xl">
            <!-- Breadcrumb -->
            <?php
            $breadcrumbs = [
                ['label' => 'Home', 'url' => url('/')],
                ['label' => 'Reviews', 'url' => url('/reviews')],
                ['label' => $article['title'], 'url' => url('/reviews/' . $article['slug'])],
            ];
            include __DIR__ . '/components/breadcrumb.php';
            ?>

            <!-- Header -->
            <header class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <span
                        class="bg-purple-100 text-purple-700 text-xs font-semibold px-2.5 py-1 rounded-full">Review</span>
                    <?php if (!empty($article['category_name'])): ?>
                        <a href="<?= url('/category/' . htmlspecialchars($article['category_slug'])) ?>"
                            class="bg-gray-100 text-gray-600 text-xs font-medium px-2.5 py-1 rounded-full hover:bg-gray-200 transition-colors">
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
                    <?php if ($article['updated_content_at']): ?>
                        <span class="text-green-600 font-medium text-xs">Updated
                            <?= date('M j, Y', strtotime($article['updated_content_at'])) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($article['reading_time']): ?>
                        <span>
                            <?= (int) $article['reading_time'] ?> min read
                        </span>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Product Overview Box -->
            <?php if ($article['overall_rating']): ?>
                <div class="my-6 bg-white border-2 border-gray-100 rounded-2xl p-6 shadow-sm not-prose">
                    <div class="flex flex-col sm:flex-row gap-6">
                        <!-- Rating Circle -->
                        <?php $rating = $article['overall_rating'];
                        $productName = $productName;
                        include __DIR__ . '/components/rating-box.php'; ?>
                        <!-- Sub Ratings -->
                        <div class="flex-1">
                            <?php include __DIR__ . '/components/sub-ratings.php'; ?>
                            <?php if ($affiliateUrl): ?>
                                <a href="<?= htmlspecialchars($affiliateUrl) ?>" rel="nofollow noopener sponsored"
                                    target="_blank"
                                    class="mt-4 block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 rounded-xl transition-colors text-center">
                                    Visit
                                    <?= htmlspecialchars($productName) ?> →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($article['best_for'])): ?>
                        <p class="mt-4 text-sm text-gray-600">
                            <span class="font-semibold text-gray-900">Best For:</span>
                            <?= htmlspecialchars($article['best_for']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Quick Verdict / Direct Answer -->
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

            <!-- Pros & Cons -->
            <?php if (!empty($pros) || !empty($cons)): ?>
                <?php include __DIR__ . '/components/pros-cons.php'; ?>
            <?php endif; ?>

            <!-- Specs Table -->
            <?php if (!empty($specifications)): ?>
                <?php include __DIR__ . '/components/specs-table.php'; ?>
            <?php endif; ?>

            <!-- Affiliate Disclosure -->
            <?php if ($article['has_affiliate_links']): ?>
                <?php $ftcDisclosure = $article['ftc_disclosure'];
                include __DIR__ . '/components/affiliate-disclosure.php'; ?>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="article-content mt-6">
                <?= $article['content'] ?>
            </div>

            <!-- Testing Methodology -->
            <?php if (!empty($article['testing_methodology'])): ?>
                <div class="my-6 bg-indigo-50 border border-indigo-200 rounded-xl p-5 not-prose">
                    <h2 class="font-bold text-indigo-900 text-sm uppercase tracking-wide mb-2 flex items-center gap-2">
                        <i data-lucide="test-tube" class="w-4 h-4"></i> Testing Methodology
                    </h2>
                    <p class="text-indigo-800 text-sm leading-relaxed">
                        <?= htmlspecialchars($article['testing_methodology']) ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Who Should Use / Avoid -->
            <?php if (!empty($article['who_should_use']) || !empty($article['who_should_avoid'])): ?>
                <div class="grid sm:grid-cols-2 gap-4 my-6 not-prose">
                    <?php if (!empty($article['who_should_use'])): ?>
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                            <h3 class="font-bold text-green-800 text-sm mb-2">✅ Who Should Use This</h3>
                            <p class="text-green-700 text-sm">
                                <?= htmlspecialchars($article['who_should_use']) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($article['who_should_avoid'])): ?>
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <h3 class="font-bold text-red-800 text-sm mb-2">❌ Who Should Avoid This</h3>
                            <p class="text-red-700 text-sm">
                                <?= htmlspecialchars($article['who_should_avoid']) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Alternatives -->
            <?php if (!empty($alternatives)): ?>
                <div class="my-6 not-prose">
                    <h2 class="text-lg font-bold text-gray-900 mb-3">Alternatives to Consider</h2>
                    <div class="space-y-3">
                        <?php foreach ($alternatives as $alt): ?>
                            <?php if (empty($alt['name']))
                                continue; ?>
                            <a href="<?= !empty($alt['slug']) ? url('/reviews/' . htmlspecialchars($alt['slug'])) : '#' ?>"
                                class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-xl hover:border-blue-300 hover:bg-blue-50 transition-all group">
                                <div>
                                    <p class="font-semibold text-gray-900 group-hover:text-blue-700">
                                        <?= htmlspecialchars($alt['name']) ?>
                                    </p>
                                    <?php if (!empty($alt['tagline'])): ?>
                                        <p class="text-sm text-gray-500">
                                            <?= htmlspecialchars($alt['tagline']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Verdict Box -->
            <?php if (!empty($article['verdict'])): ?>
                <?php $verdict = $article['verdict'];
                $rating = $article['overall_rating'];
                include __DIR__ . '/components/verdict-box.php'; ?>
            <?php endif; ?>

            <!-- Affiliate Disclosure (bottom) -->
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
                            class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full hover:bg-purple-100 hover:text-purple-700 transition-colors">
                            #
                            <?= htmlspecialchars($tag['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Social Share -->
            <?php include __DIR__ . '/components/social-share.php'; ?>

        </article>

        <!-- Sidebar -->
        <aside class="hidden lg:block w-72 flex-shrink-0">
            <div class="sticky top-20 space-y-5">
                <?php if ($article['overall_rating']): ?>
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold mb-3">DevLync Score</p>
                        <?php $rating = $article['overall_rating'];
                        include __DIR__ . '/components/rating-box.php'; ?>
                        <?php if ($affiliateUrl): ?>
                            <a href="<?= htmlspecialchars($affiliateUrl) ?>" rel="nofollow noopener sponsored" target="_blank"
                                class="mt-3 block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 rounded-xl transition-colors text-center">
                                Try
                                <?= htmlspecialchars($productName) ?> →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="bg-purple-50 border border-purple-100 rounded-2xl p-5">
                    <p class="font-semibold text-purple-900 text-sm mb-1">More Reviews</p>
                    <a href="<?= url('/reviews') ?>" class="text-purple-700 text-sm hover:text-purple-900 font-medium">Browse all tool
                        reviews →</a>
                </div>
            </div>
        </aside>

    </div>
</div>