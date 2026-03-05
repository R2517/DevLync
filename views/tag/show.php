<?php
/**
 * Tag Show View
 * Variables: $tag (array), $result (array), $page (int)
 */
$items = $result['items'] ?? [];
$total = $result['total'] ?? 0;
$pages = $result['pages'] ?? 1;
?>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <h1 class="text-2xl font-extrabold text-gray-900">#
            <?= htmlspecialchars($tag['name']) ?>
        </h1>
        <p class="text-gray-500 text-sm mt-1">
            <?= number_format($total) ?> articles tagged with "
            <?= htmlspecialchars($tag['name']) ?>"
        </p>
    </div>
    <?php if (empty($items)): ?>
        <p class="text-gray-400 text-center py-10">No articles for this tag yet.</p>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($items as $article): ?>
                <?php $urlBase = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'][$article['content_type']] ?? 'blog'; ?>
                <a href="<?= url('/' . $urlBase . '/' . htmlspecialchars($article['slug'])) ?>"
                    class="flex gap-4 bg-white border border-gray-100 rounded-xl p-4 hover:border-blue-200 hover:shadow-sm transition-all group">
                    <div>
                        <span class="text-xs text-blue-600 font-semibold capitalize">
                            <?= $article['content_type'] ?>
                        </span>
                        <h2 class="font-semibold text-gray-900 group-hover:text-blue-600 text-sm mt-0.5">
                            <?= htmlspecialchars($article['title']) ?>
                        </h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <?= $article['published_at'] ? date('M j, Y', strtotime($article['published_at'])) : '' ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
            <nav class="flex justify-center gap-2 mt-8">
                <?php if ($page > 1): ?><a href="<?= url('/tag/' . htmlspecialchars($tag['slug'])) ?>?page=<?= $page - 1 ?>"
                        class="px-4 py-2 bg-white border rounded-lg text-sm text-gray-600 hover:bg-gray-50">← Prev</a>
                <?php endif; ?>
                <?php if ($page < $pages): ?><a href="<?= url('/tag/' . htmlspecialchars($tag['slug'])) ?>?page=<?= $page + 1 ?>"
                        class="px-4 py-2 bg-white border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Next →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>