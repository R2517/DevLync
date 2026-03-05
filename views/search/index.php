<?php
/**
 * Search Results View
 * Variables: $query, $results, $total, $page, $pages
 */
$typeMap = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'];
?>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-2xl font-bold dark:text-white text-gray-900 mb-6">Search Developer Tools & Reviews</h1>

    <!-- Search Form -->
    <form action="<?= url('/search') ?>" method="GET" class="mb-8">
        <div class="flex gap-2">
            <input type="search" name="q" value="<?= htmlspecialchars($query) ?>"
                placeholder="Search developer tools, reviews..." id="search-input"
                class="flex-1 dark:bg-white/5 dark:border-white/10 border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white dark:placeholder-gray-500 transition-colors">
            <button type="submit"
                class="bg-blue-600 text-white font-semibold px-5 py-3 rounded-xl hover:bg-blue-700 transition-colors text-sm">Search</button>
        </div>
    </form>

    <?php if ($query): ?>
        <p class="text-lg font-semibold dark:text-white text-gray-900 mb-2">
            <?= $total > 0 ? number_format($total) . ' results for' : 'No results for' ?>
            "<span class="text-blue-600">
                <?= htmlspecialchars($query) ?>
            </span>"
        </p>
    <?php endif; ?>

    <?php if (empty($results)): ?>
        <div class="text-center py-10 text-gray-400">
            <p class="text-lg font-medium">No articles found<?= $query ? ' for "' . htmlspecialchars($query) . '"' : '' ?>.</p>
            <p class="text-sm mt-1">Try a different search term or browse by category below.</p>
        </div>
        <div class="mt-6 space-y-6 dark:text-gray-300 text-gray-700 leading-relaxed">
            <h2 class="text-lg font-bold dark:text-white text-gray-900">Popular Topics on DevLync</h2>
            <p>Not sure what to search for? Here are some of the topics our readers explore most frequently. From AI-powered coding assistants to DevOps pipelines, we cover the tools that modern developers rely on every day.</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <a href="<?= url('/reviews') ?>" class="block dark:bg-white/[0.03] bg-gray-50 dark:border-white/5 border border-gray-100 rounded-xl p-4 hover:border-blue-200 dark:hover:border-blue-500/20 hover:shadow-sm transition-all">
                    <p class="font-semibold dark:text-white text-gray-900">Developer Tool Reviews</p>
                    <p class="text-sm dark:text-gray-400 text-gray-500 mt-1">Honest, hands-on reviews of code editors, IDEs, cloud platforms, and more.</p>
                </a>
                <a href="<?= url('/comparisons') ?>" class="block dark:bg-white/[0.03] bg-gray-50 dark:border-white/5 border border-gray-100 rounded-xl p-4 hover:border-blue-200 dark:hover:border-blue-500/20 hover:shadow-sm transition-all">
                    <p class="font-semibold dark:text-white text-gray-900">Side-by-Side Comparisons</p>
                    <p class="text-sm text-gray-500 mt-1">Compare tools head-to-head with feature tables, pricing, and clear winners.</p>
                </a>
                <a href="<?= url('/blog') ?>" class="block dark:bg-white/[0.03] bg-gray-50 dark:border-white/5 border border-gray-100 rounded-xl p-4 hover:border-blue-200 dark:hover:border-blue-500/20 hover:shadow-sm transition-all">
                    <p class="font-semibold dark:text-white text-gray-900">Blog & Tutorials</p>
                    <p class="text-sm text-gray-500 mt-1">Guides, tips, and best practices for developer workflows and tooling.</p>
                </a>
                <a href="<?= url('/news') ?>" class="block dark:bg-white/[0.03] bg-gray-50 dark:border-white/5 border border-gray-100 rounded-xl p-4 hover:border-blue-200 dark:hover:border-blue-500/20 hover:shadow-sm transition-all">
                    <p class="font-semibold dark:text-white text-gray-900">Developer News</p>
                    <p class="text-sm text-gray-500 mt-1">Latest product launches, funding rounds, and industry updates.</p>
                </a>
            </div>
        </div>
    <?php else: ?>

        <div class="space-y-4 mt-6">
            <?php foreach ($results as $article): ?>
                <?php $urlBase = $typeMap[$article['content_type']] ?? 'blog'; ?>
                <a href="<?= url('/' . $urlBase . '/' . htmlspecialchars($article['slug'])) ?>"
                    class="flex gap-4 dark:bg-white/[0.03] bg-white dark:border-white/5 border border-gray-100 rounded-xl p-4 hover:dark:border-blue-500/20 hover:border-blue-200 hover:shadow-sm transition-all group">
                    <?php if (!empty($article['featured_image_url'])): ?>
                        <img src="<?= htmlspecialchars($article['featured_image_url']) ?>" alt=""
                            class="w-20 h-20 object-cover rounded-lg flex-shrink-0">
                    <?php endif; ?>
                    <div class="min-w-0">
                        <span class="text-xs font-semibold text-blue-500 uppercase">
                            <?= ucfirst($article['content_type']) ?>
                        </span>
                        <h2
                            class="font-semibold dark:text-white text-gray-900 group-hover:text-blue-500 line-clamp-2 text-sm leading-snug mt-0.5">
                            <?= htmlspecialchars($article['title']) ?>
                        </h2>
                        <?php if (!empty($article['excerpt'])): ?>
                            <p class="text-xs text-gray-400 line-clamp-1 mt-1">
                                <?= htmlspecialchars($article['excerpt']) ?>
                            </p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-300 mt-1">
                            <?= $article['published_at'] ? date('M j, Y', strtotime($article['published_at'])) : '' ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="flex justify-center gap-2 mt-10">
                <?php if ($page > 1): ?>
                    <a href="<?= url('/search') ?>?q=<?= urlencode($query) ?>&page=<?= $page - 1 ?>"
                        class="px-4 py-2 dark:bg-white/5 bg-white dark:border-white/10 border rounded-xl text-sm dark:text-gray-400 text-gray-600 hover:dark:bg-white/10 hover:bg-gray-50 transition-colors">← Prev</a>
                <?php endif; ?>
                <?php if ($page < $pages): ?>
                    <a href="<?= url('/search') ?>?q=<?= urlencode($query) ?>&page=<?= $page + 1 ?>"
                        class="px-4 py-2 dark:bg-white/5 bg-white dark:border-white/10 border rounded-xl text-sm dark:text-gray-400 text-gray-600 hover:dark:bg-white/10 hover:bg-gray-50 transition-colors">Next →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

    <?php endif; ?>
</div>