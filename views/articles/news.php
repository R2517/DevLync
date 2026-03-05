<?php
/**
 * News Article Template
 * Variables: $article (array), $tags (array), $relatedPosts (array)
 */
$_dj = fn($v) => is_string($v) && $v !== '' ? (json_decode($v, true) ?? []) : (is_array($v) ? $v : []);
$faq            = $_dj($article['faq'] ?? null);
$sources        = $_dj($article['sources'] ?? null);
$keyFacts       = $_dj($article['key_facts'] ?? null);
$expertOpinions = $_dj($article['expert_opinions'] ?? null);
$newsSources    = $_dj($article['news_sources'] ?? null);
$canonicalUrl = 'https://devlync.com/news/' . $article['slug'];
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
<div id="reading-progress" class="fixed top-0 left-0 h-0.5 bg-red-600 z-50" style="width:0%"></div>
<?php include __DIR__ . '/components/schema-markup.php'; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <article itemscope itemtype="https://schema.org/NewsArticle">
        <?php
        $breadcrumbs = [
            ['label' => 'Home', 'url' => url('/')],
            ['label' => 'News', 'url' => url('/news')],
            ['label' => $article['title'], 'url' => url('/news/' . $article['slug'])],
        ];
        include __DIR__ . '/components/breadcrumb.php';
        ?>

        <!-- Header -->
        <header class="mb-6">
            <div class="flex items-center gap-2 mb-3">
                <span class="bg-red-100 text-red-700 text-xs font-semibold px-2.5 py-1 rounded-full">News</span>
                <?php if (!empty($article['category_name'])): ?>
                    <a href="<?= url('/category/' . htmlspecialchars($article['category_slug'])) ?>"
                        class="bg-gray-100 text-gray-600 text-xs font-medium px-2.5 py-1 rounded-full">
                        <?= htmlspecialchars($article['category_name']) ?>
                    </a>
                <?php endif; ?>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight mb-4" itemprop="headline">
                <?= htmlspecialchars($article['title']) ?>
            </h1>
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                <?php if (!empty($article['dateline'])): ?>
                    <span class="font-semibold text-gray-700">
                        <?= htmlspecialchars($article['dateline']) ?>
                    </span>
                    <span>·</span>
                <?php endif; ?>
                <span class="flex items-center gap-1"><i data-lucide="user" class="w-4 h-4"></i>
                    <a href="<?= url('/author/' . htmlspecialchars($article['author_slug'] ?? 'devlync-team')) ?>"
                        class="hover:text-blue-600 font-medium">
                        <?= htmlspecialchars($article['author_name'] ?? 'DevLync Team') ?>
                    </a>
                </span>
                <?php if ($article['published_at']): ?>
                    <time datetime="<?= date('Y-m-d', strtotime($article['published_at'])) ?>" itemprop="datePublished">
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

        <!-- Featured Image -->
        <?php if ($article['featured_image_url']): ?>
            <figure class="mb-6" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
                <img src="<?= htmlspecialchars($article['featured_image_url']) ?>"
                    alt="<?= htmlspecialchars($article['featured_image_alt'] ?? $article['title']) ?>"
                    class="w-full rounded-2xl object-cover max-h-80" itemprop="url" loading="eager">
            </figure>
        <?php endif; ?>

        <!-- Key Facts Box -->
        <?php if (!empty($keyFacts)): ?>
            <?php include __DIR__ . '/components/key-facts.php'; ?>
        <?php endif; ?>

        <!-- Summary / Speakable -->
        <?php if (!empty($article['direct_answer'])): ?>
            <div class="my-5 text-gray-700 text-lg leading-relaxed font-medium border-l-4 border-red-500 pl-4 not-prose"
                itemprop="description">
                <?= htmlspecialchars($article['direct_answer']) ?>
            </div>
        <?php endif; ?>

        <!-- Social Share -->
        <?php include __DIR__ . '/components/social-share.php'; ?>

        <!-- Main Content -->
        <div class="article-content mt-4" itemprop="articleBody">
            <?= $article['content'] ?>
        </div>

        <!-- Industry Impact -->
        <?php if (!empty($article['industry_impact'])): ?>
            <div class="my-6 bg-purple-50 border border-purple-200 rounded-xl p-5 not-prose">
                <h2 class="font-bold text-purple-900 text-sm uppercase tracking-wide mb-2">📊 Industry Impact</h2>
                <p class="text-purple-800 text-sm leading-relaxed">
                    <?= htmlspecialchars($article['industry_impact']) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Expert Opinions -->
        <?php if (!empty($expertOpinions)): ?>
            <div class="my-6 space-y-4 not-prose">
                <h2 class="text-lg font-bold text-gray-900">Expert Opinions</h2>
                <?php foreach ($expertOpinions as $opinion): ?>
                    <blockquote class="bg-gray-50 border-l-4 border-blue-500 rounded-r-xl p-5">
                        <p class="text-gray-700 text-sm leading-relaxed italic mb-2">"
                            <?= htmlspecialchars($opinion['quote'] ?? $opinion) ?>"
                        </p>
                        <?php if (!empty($opinion['author'])): ?>
                            <cite class="text-xs text-gray-400 font-medium not-italic">—
                                <?= htmlspecialchars($opinion['author']) ?>
                            </cite>
                        <?php endif; ?>
                    </blockquote>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- FAQ -->
        <?php if (!empty($faq)): ?>
            <?php include __DIR__ . '/components/faq-accordion.php'; ?>
        <?php endif; ?>

        <!-- Sources -->
        <?php
        $allSources = array_merge($sources ?: [], $newsSources ?: []);
        if (!empty($allSources)):
            $sources = $allSources;
            ?>
            <?php include __DIR__ . '/components/sources-box.php'; ?>
        <?php endif; ?>

        <!-- Author Box -->
        <?php include __DIR__ . '/components/author-box.php'; ?>

        <!-- Related News -->
        <?php if (!empty($relatedPosts)): ?>
            <?php include __DIR__ . '/components/related-posts.php'; ?>
        <?php endif; ?>

        <!-- Tags -->
        <?php if (!empty($tags)): ?>
            <div class="flex flex-wrap gap-2 mt-4">
                <?php foreach ($tags as $tag): ?>
                    <a href="<?= url('/tag/' . htmlspecialchars($tag['slug'])) ?>"
                        class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full hover:bg-red-100 hover:text-red-700 transition-colors">
                        #
                        <?= htmlspecialchars($tag['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </article>
</div>