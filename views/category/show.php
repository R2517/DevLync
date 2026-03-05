<?php
/**
 * Category Show View
 * Variables: $category (array), $result (array with items/total/pages), $page (int)
 */
$items = $result['items'] ?? [];
$total = $result['total'] ?? 0;
$pages = $result['pages'] ?? 1;
?>

<!-- Hero Banner -->
<section class="relative overflow-hidden bg-gradient-to-br from-blue-950 via-indigo-950 to-purple-950 text-white py-16 px-4">
    <div class="absolute inset-0">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl"></div>
    </div>
    <div class="relative max-w-7xl mx-auto text-center">
        <?php if (!empty($category['icon'])): ?>
            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i data-lucide="<?= htmlspecialchars($category['icon']) ?>" class="w-7 h-7 text-white"></i>
            </div>
        <?php endif; ?>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black mb-3 tracking-tight"><?= htmlspecialchars($category['name']) ?></h1>
        <?php if (!empty($category['description'])): ?>
            <p class="text-lg text-white/60 max-w-2xl mx-auto"><?= htmlspecialchars($category['description']) ?></p>
        <?php endif; ?>
        <p class="mt-4 text-sm text-white/40"><?= number_format($total) ?> articles</p>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php if (empty($items)): ?>
        <div class="text-center py-20 dark:text-gray-600 text-gray-400">
            <p class="text-lg font-medium">No articles in this category yet.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($items as $article): ?>
                <?php $urlBase = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'][$article['content_type']] ?? 'blog'; ?>
                <article class="article-card dark:bg-white/[0.03] bg-white rounded-2xl dark:border-white/5 border border-gray-100 overflow-hidden group">
                    <?php if (!empty($article['featured_image_url'])): ?>
                        <a href="<?= url('/' . $urlBase . '/' . htmlspecialchars($article['slug'])) ?>" class="block h-44 dark:bg-gray-800 bg-gray-100 overflow-hidden">
                            <img src="<?= htmlspecialchars($article['featured_image_url']) ?>"
                                alt="<?= htmlspecialchars($article['featured_image_alt'] ?? $article['title']) ?>"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                        </a>
                    <?php endif; ?>
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-2">
                            <span class="dark:bg-blue-500/15 bg-blue-100 dark:text-blue-400 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full uppercase tracking-wider"><?= ucfirst($article['content_type']) ?></span>
                            <?php if (!empty($article['overall_rating'])): ?>
                                <span class="text-sm font-black bg-gradient-to-r from-blue-500 to-purple-500 bg-clip-text text-transparent"><?= number_format((float) $article['overall_rating'], 1) ?>/10</span>
                            <?php endif; ?>
                        </div>
                        <h2 class="font-bold dark:text-white text-gray-900 line-clamp-2 mb-1 leading-snug">
                            <a href="<?= url('/' . $urlBase . '/' . htmlspecialchars($article['slug'])) ?>" class="hover:text-blue-500 transition-colors"><?= htmlspecialchars($article['title']) ?></a>
                        </h2>
                        <?php if (!empty($article['excerpt'])): ?>
                            <p class="dark:text-gray-500 text-gray-500 text-sm line-clamp-2 mb-3"><?= htmlspecialchars($article['excerpt']) ?></p>
                        <?php endif; ?>
                        <div class="flex items-center justify-between text-xs dark:text-gray-600 text-gray-400 border-t dark:border-white/5 border-gray-50 pt-2">
                            <span><?= htmlspecialchars($article['author_name'] ?? 'DevLync') ?></span>
                            <?php if ($article['published_at']): ?>
                                <span><?= date('M j, Y', strtotime($article['published_at'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
            <nav class="flex justify-center gap-2 mt-10">
                <?php if ($page > 1): ?>
                    <a href="<?= url('/category/' . htmlspecialchars($category['slug'])) ?>?page=<?= $page - 1 ?>"
                        class="px-4 py-2 dark:bg-white/5 bg-white dark:border-white/10 border border-gray-200 rounded-xl text-sm font-medium dark:text-gray-400 text-gray-600 hover:dark:bg-white/10 hover:bg-gray-50 transition-colors">← Prev</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="px-4 py-2 rounded-xl text-sm font-bold bg-blue-500 text-white shadow-lg"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= url('/category/' . htmlspecialchars($category['slug'])) ?>?page=<?= $i ?>"
                            class="px-4 py-2 rounded-xl text-sm font-medium dark:bg-white/5 bg-white dark:border-white/10 border border-gray-200 dark:text-gray-400 text-gray-600 hover:dark:bg-white/10 hover:bg-gray-50 transition-colors"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $pages): ?>
                    <a href="<?= url('/category/' . htmlspecialchars($category['slug'])) ?>?page=<?= $page + 1 ?>"
                        class="px-4 py-2 dark:bg-white/5 bg-white dark:border-white/10 border border-gray-200 rounded-xl text-sm font-medium dark:text-gray-400 text-gray-600 hover:dark:bg-white/10 hover:bg-gray-50 transition-colors">Next →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>