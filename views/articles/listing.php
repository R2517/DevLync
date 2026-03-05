<?php
/**
 * Article Listing Page
 * Used for /blog, /reviews, /comparisons, /news
 * Variables: $type, $items (array), $total, $page, $pages, $typeLabel, $typeDesc
 */
$typeColors = ['blog' => 'blue', 'review' => 'purple', 'comparison' => 'emerald', 'news' => 'rose'];
$color = $typeColors[$type] ?? 'blue';
$urlMap = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'];
$urlBase = $urlMap[$type] ?? 'blog';
$heroGradients = [
    'blog' => 'from-blue-950 via-blue-900 to-indigo-950',
    'review' => 'from-purple-950 via-purple-900 to-fuchsia-950',
    'comparison' => 'from-emerald-950 via-emerald-900 to-teal-950',
    'news' => 'from-rose-950 via-rose-900 to-red-950',
];
$heroGrad = $heroGradients[$type] ?? $heroGradients['blog'];
?>

<!-- Hero Banner -->
<section class="relative overflow-hidden bg-gradient-to-br <?= $heroGrad ?> text-white py-16 px-4">
    <div class="absolute inset-0">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-<?= $color ?>-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-<?= $color ?>-400/10 rounded-full blur-3xl"></div>
    </div>
    <div class="relative max-w-7xl mx-auto text-center">
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black mb-3 tracking-tight"><?= htmlspecialchars($typeLabel ?? ucfirst($type)) ?></h1>
        <p class="text-lg text-white/60 max-w-2xl mx-auto"><?= htmlspecialchars($typeDesc ?? '') ?></p>
        <?php if ($total > 0): ?>
            <p class="mt-4 text-sm text-white/40"><?= number_format($total) ?> articles published</p>
        <?php endif; ?>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php if (empty($items)): ?>
        <div class="text-center py-20 dark:text-gray-600 text-gray-400">
            <i data-lucide="file-text" class="w-12 h-12 mx-auto mb-3 opacity-30"></i>
            <p class="text-lg font-medium">No articles found yet.</p>
            <p class="text-sm mt-1">Check back soon — we publish new content regularly.</p>
        </div>
    <?php else: ?>

        <!-- Article Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($items as $article): ?>
                <article class="article-card dark:bg-white/[0.03] bg-white rounded-2xl dark:border-white/5 border border-gray-100 overflow-hidden group">
                    <!-- Image -->
                    <a href="<?= url('/' . $urlBase . '/' . htmlspecialchars($article['slug'])) ?>" class="block w-full h-44 dark:bg-gray-800 bg-gray-100 overflow-hidden">
                        <?php if (!empty($article['featured_image_url'])): ?>
                            <img src="<?= htmlspecialchars($article['featured_image_url']) ?>"
                                alt="<?= htmlspecialchars($article['featured_image_alt'] ?? $article['title']) ?>"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy"
                                onerror="this.src='<?= url('/assets/images/placeholder.svg') ?>'">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <i data-lucide="file-text" class="w-12 h-12 dark:text-gray-600 text-<?= $color ?>-300"></i>
                            </div>
                        <?php endif; ?>
                    </a>

                    <div class="p-5">
                        <!-- Badge + Rating -->
                        <div class="flex items-center justify-between mb-2">
                            <span class="dark:bg-<?= $color ?>-500/15 bg-<?= $color ?>-100 dark:text-<?= $color ?>-400 text-<?= $color ?>-700 text-xs font-bold px-2.5 py-1 rounded-full uppercase tracking-wider"><?= ucfirst($type) ?></span>
                            <?php if (!empty($article['overall_rating'])): ?>
                                <span class="text-sm font-black bg-gradient-to-r from-<?= $color ?>-500 to-<?= $color ?>-400 bg-clip-text text-transparent"><?= number_format((float) $article['overall_rating'], 1) ?>/10</span>
                            <?php endif; ?>
                        </div>

                        <!-- Title -->
                        <h2 class="font-bold dark:text-white text-gray-900 line-clamp-2 leading-snug mb-2">
                            <a href="<?= url('/' . $urlBase . '/' . htmlspecialchars($article['slug'])) ?>" class="hover:text-<?= $color ?>-500 transition-colors"><?= htmlspecialchars($article['title']) ?></a>
                        </h2>

                        <!-- Excerpt -->
                        <?php if (!empty($article['excerpt'])): ?>
                            <p class="dark:text-gray-500 text-gray-500 text-sm line-clamp-2 mb-3"><?= htmlspecialchars($article['excerpt']) ?></p>
                        <?php endif; ?>

                        <!-- Meta -->
                        <div class="flex items-center justify-between text-xs dark:text-gray-600 text-gray-400 mt-auto pt-2 border-t dark:border-white/5 border-gray-50">
                            <span><?= htmlspecialchars($article['author_name'] ?? 'DevLync Team') ?></span>
                            <div class="flex items-center gap-2">
                                <?php if (!empty($article['reading_time'])): ?>
                                    <span><?= (int) $article['reading_time'] ?> min</span>
                                <?php endif; ?>
                                <?php if (!empty($article['published_at'])): ?>
                                    <span><?= date('M j, Y', strtotime($article['published_at'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
            <nav class="flex items-center justify-center gap-2 mt-12" aria-label="Pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= url('/' . $urlBase) ?>?page=<?= $page - 1 ?>"
                        class="px-4 py-2 dark:bg-white/5 bg-white dark:border-white/10 border border-gray-200 rounded-xl text-sm font-medium dark:text-gray-400 text-gray-600 hover:dark:bg-white/10 hover:bg-gray-50 transition-colors">← Prev</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="px-4 py-2 rounded-xl text-sm font-bold bg-<?= $color ?>-500 text-white shadow-lg"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= url('/' . $urlBase) ?>?page=<?= $i ?>"
                            class="px-4 py-2 rounded-xl text-sm font-medium dark:bg-white/5 bg-white dark:border-white/10 border border-gray-200 dark:text-gray-400 text-gray-600 hover:dark:bg-white/10 hover:bg-gray-50 transition-colors"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                    <a href="<?= url('/' . $urlBase) ?>?page=<?= $page + 1 ?>"
                        class="px-4 py-2 dark:bg-white/5 bg-white dark:border-white/10 border border-gray-200 rounded-xl text-sm font-medium dark:text-gray-400 text-gray-600 hover:dark:bg-white/10 hover:bg-gray-50 transition-colors">Next →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

    <?php endif; ?>
</div>