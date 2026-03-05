<?php
/**
 * Blog Article Template
 * Variables: $article (array), $tags (array), $relatedPosts (array), $tpl (Template)
 */
$typeColors = ['blog' => 'blue', 'review' => 'purple', 'comparison' => 'green', 'news' => 'red'];

$_dj = fn($v) => is_string($v) && $v !== '' ? (json_decode($v, true) ?? []) : (is_array($v) ? $v : []);

$pros           = $_dj($article['pros'] ?? null);
$cons           = $_dj($article['cons'] ?? null);
$faq            = $_dj($article['faq'] ?? null);
$keyTakeaways   = $_dj($article['key_takeaways'] ?? null);
$sources        = $_dj($article['sources'] ?? null);
$expertTips     = $_dj($article['expert_tips'] ?? null);
$commonMistakes = $_dj($article['common_mistakes'] ?? null);

$canonicalUrl = 'https://devlync.com/blog/' . $article['slug'];

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
<div id="reading-progress" class="fixed top-0 left-0 h-0.5 bg-blue-600 z-50" style="width:0%"></div>

<!-- Schema Markup -->
<?php include __DIR__ . '/components/schema-markup.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="lg:flex lg:gap-10">

        <!-- ─── Main Content ─── -->
        <article class="min-w-0 flex-1 max-w-3xl" itemscope itemtype="https://schema.org/BlogPosting">
            <meta itemprop="datePublished" content="<?= htmlspecialchars($article['published_at'] ?? '') ?>">
            <meta itemprop="dateModified"
                content="<?= htmlspecialchars($article['updated_content_at'] ?? $article['updated_at'] ?? '') ?>">

            <!-- Breadcrumb -->
            <?php
            $breadcrumbs = [
                ['label' => 'Home', 'url' => url('/')],
                ['label' => 'Blog', 'url' => url('/blog')],
                ['label' => $article['title'], 'url' => url('/blog/' . $article['slug'])],
            ];
            include __DIR__ . '/components/breadcrumb.php';
            ?>

            <!-- Header -->
            <header class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <span
                        class="bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full uppercase tracking-wide">Blog</span>
                    <?php if (!empty($article['category_name'])): ?>
                        <a href="<?= url('/category/' . htmlspecialchars($article['category_slug'])) ?>"
                            class="bg-gray-100 text-gray-600 text-xs font-medium px-2.5 py-1 rounded-full hover:bg-gray-200 transition-colors">
                            <?= htmlspecialchars($article['category_name']) ?>
                        </a>
                    <?php endif; ?>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight mb-4" itemprop="headline">
                    <?= htmlspecialchars($article['title']) ?>
                </h1>
                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                    <span class="flex items-center gap-1">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <a href="<?= url('/author/' . htmlspecialchars($article['author_slug'] ?? 'devlync-team')) ?>"
                            class="hover:text-blue-600 font-medium">
                            <?= htmlspecialchars($article['author_name'] ?? 'DevLync Team') ?>
                        </a>
                    </span>
                    <?php if ($article['published_at']): ?>
                        <span class="flex items-center gap-1">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <time datetime="<?= date('Y-m-d', strtotime($article['published_at'])) ?>">
                                <?= date('F j, Y', strtotime($article['published_at'])) ?>
                            </time>
                        </span>
                    <?php endif; ?>
                    <?php if ($article['reading_time']): ?>
                        <span class="flex items-center gap-1">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            <?= (int) $article['reading_time'] ?> min read
                        </span>
                    <?php endif; ?>
                    <?php if ($article['word_count']): ?>
                        <span class="text-gray-400">
                            <?= number_format((int) $article['word_count']) ?> words
                        </span>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Featured Image -->
            <?php if ($article['featured_image_url']): ?>
                <figure class="mb-6">
                    <img src="<?= htmlspecialchars($article['featured_image_url']) ?>"
                        alt="<?= htmlspecialchars($article['featured_image_alt'] ?? $article['title']) ?>"
                        class="w-full rounded-2xl object-cover max-h-96" loading="eager" itemprop="image">
                </figure>
            <?php elseif (!empty($article['featured_image_alt'])): ?>
                <?php include __DIR__ . '/components/image-placeholder.php'; ?>
            <?php endif; ?>

            <!-- Social Share (top) -->
            <?php include __DIR__ . '/components/social-share.php'; ?>

            <!-- Quick Answer -->
            <?php if (!empty($article['direct_answer'])): ?>
                <?php $directAnswer = $article['direct_answer'];
                include __DIR__ . '/components/quick-answer.php'; ?>
            <?php endif; ?>

            <!-- Key Takeaways -->
            <?php if (!empty($keyTakeaways)): ?>
                <?php include __DIR__ . '/components/key-takeaways.php'; ?>
            <?php endif; ?>

            <!-- Table of Contents -->
            <?php if (!empty($article['content'])): ?>
                <?php $content = $article['content'];
                include __DIR__ . '/components/toc.php'; ?>
            <?php endif; ?>

            <!-- Main Article Content -->
            <div class="article-content mt-6" itemprop="articleBody">
                <?= $article['content'] ?>
            </div>

            <!-- Common Mistakes -->
            <?php if (!empty($commonMistakes)): ?>
                <div class="my-6 bg-orange-50 border border-orange-200 rounded-xl p-5 not-prose">
                    <h2 class="font-bold text-orange-800 text-sm uppercase tracking-wide mb-3">⚠️ Common Mistakes to Avoid
                    </h2>
                    <ul class="space-y-2">
                        <?php foreach ($commonMistakes as $mistake): ?>
                            <li class="flex items-start gap-2 text-sm text-orange-800">
                                <span class="flex-shrink-0 mt-0.5">❌</span>
                                <?= htmlspecialchars($mistake) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Expert Tips -->
            <?php if (!empty($expertTips)): ?>
                <div class="my-6 bg-purple-50 border border-purple-200 rounded-xl p-5 not-prose">
                    <h2 class="font-bold text-purple-800 text-sm uppercase tracking-wide mb-3">💡 Expert Tips</h2>
                    <ul class="space-y-2">
                        <?php foreach ($expertTips as $tip): ?>
                            <li class="flex items-start gap-2 text-sm text-purple-800">
                                <span class="flex-shrink-0">✨</span>
                                <?= htmlspecialchars($tip) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Related Posts -->
            <?php if (!empty($relatedPosts)): ?>
                <?php include __DIR__ . '/components/related-posts.php'; ?>
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

            <!-- Last Updated -->
            <div class="flex items-center gap-2 text-xs text-gray-400 mt-4">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                Last updated:
                <?= date('F j, Y', strtotime($article['updated_at'] ?? 'now')) ?>
            </div>

            <!-- Social Share (bottom) -->
            <?php include __DIR__ . '/components/social-share.php'; ?>

            <!-- Tags -->
            <?php if (!empty($tags)): ?>
                <div class="flex flex-wrap gap-2 mt-4">
                    <?php foreach ($tags as $tag): ?>
                        <a href="<?= url('/tag/' . htmlspecialchars($tag['slug'])) ?>"
                            class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full hover:bg-blue-100 hover:text-blue-700 transition-colors">
                            #
                            <?= htmlspecialchars($tag['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <!-- ─── Sidebar ─── -->
        <aside class="hidden lg:block w-72 flex-shrink-0">
            <div class="sticky top-20 space-y-5">
                <!-- TOC Sidebar version rendered via JS scroll-spy handled in app.js -->
                <div class="bg-blue-600 text-white rounded-2xl p-5 text-center">
                    <p class="text-sm font-semibold mb-1">More Developer Tool Reviews</p>
                    <p class="text-xs text-blue-100 mb-3">Discover the best tools for your stack</p>
                    <a href="<?= url('/reviews') ?>"
                        class="block w-full bg-white text-blue-700 text-sm font-bold py-2 rounded-lg hover:bg-blue-50 transition-colors">Browse
                        All Reviews</a>
                </div>
                <?php if (!empty($relatedPosts)): ?>
                    <div class="bg-white border border-gray-100 rounded-2xl p-5">
                        <h3 class="font-bold text-gray-900 text-sm mb-3">Related Articles</h3>
                        <div class="space-y-3">
                            <?php foreach (array_slice($relatedPosts, 0, 3) as $post): ?>
                                <?php $urlBase = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'][$post['content_type']] ?? 'blog'; ?>
                                <a href="<?= url('/' . $urlBase . '/' . htmlspecialchars($post['slug'])) ?>"
                                    class="block hover:text-blue-600 transition-colors">
                                    <p class="text-sm font-medium text-gray-800 line-clamp-2 leading-snug hover:text-blue-600">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

    </div>
</div>