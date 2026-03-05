<?php
/**
 * Author Profile View (EEAT)
 * Variables: $author (array), $result (array with items/total/pages), $page (int)
 */
$items = $result['items'] ?? [];
$total = $result['total'] ?? 0;
$pages = $result['pages'] ?? 1;
?>
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Author Profile Card -->
    <div class="bg-white border border-gray-100 rounded-2xl p-8 mb-10 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-6 items-start">
            <?php if (!empty($author['avatar_url'])): ?>
                <img src="<?= htmlspecialchars($author['avatar_url']) ?>" alt="<?= htmlspecialchars($author['name']) ?>"
                    class="w-24 h-24 rounded-full object-cover border-4 border-gray-100 flex-shrink-0">
            <?php else: ?>
                <div
                    class="w-24 h-24 rounded-full bg-blue-600 flex items-center justify-center text-white text-3xl font-bold flex-shrink-0">
                    <?= strtoupper(substr($author['name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div>
                <div class="text-xs text-blue-600 font-semibold uppercase tracking-wide mb-1">Author</div>
                <h1 class="text-2xl font-extrabold text-gray-900 mb-1">
                    <?= htmlspecialchars($author['name']) ?>
                </h1>
                <?php if (!empty($author['expertise'])): ?>
                    <p class="text-sm text-gray-500 mb-2">Expert in:
                        <?= htmlspecialchars($author['expertise']) ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($author['bio'])): ?>
                    <p class="text-gray-600 leading-relaxed text-sm max-w-2xl">
                        <?= htmlspecialchars($author['bio']) ?>
                    </p>
                <?php endif; ?>
                <div class="flex flex-wrap gap-3 mt-3">
                    <?php if (!empty($author['twitter'])): ?>
                        <a href="https://twitter.com/<?= htmlspecialchars(ltrim($author['twitter'], '@')) ?>"
                            rel="noopener noreferrer" target="_blank"
                            class="text-xs text-gray-500 hover:text-blue-500 font-medium">🐦 Twitter</a>
                    <?php endif; ?>
                    <?php if (!empty($author['linkedin'])): ?>
                        <a href="<?= htmlspecialchars($author['linkedin']) ?>" rel="noopener noreferrer" target="_blank"
                            class="text-xs text-gray-500 hover:text-blue-700 font-medium">💼 LinkedIn</a>
                    <?php endif; ?>
                    <?php if (!empty($author['github'])): ?>
                        <a href="<?= htmlspecialchars($author['github']) ?>" rel="noopener noreferrer" target="_blank"
                            class="text-xs text-gray-500 hover:text-gray-900 font-medium">🐙 GitHub</a>
                    <?php endif; ?>
                </div>
                <div class="mt-3 text-xs text-gray-400">
                    <?= (int) $author['articles_count'] ?> articles published
                </div>
            </div>
        </div>
    </div>

    <!-- Author Articles -->
    <h2 class="text-xl font-bold text-gray-900 mb-5">Articles by
        <?= htmlspecialchars($author['name']) ?>
    </h2>
    <?php if (empty($items)): ?>
        <p class="text-gray-400 text-center py-10">No articles found.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($items as $article): ?>
                <?php $urlBase = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'][$article['content_type']] ?? 'blog'; ?>
                <a href="<?= url('/' . $urlBase . '/' . htmlspecialchars($article['slug'])) ?>"
                    class="flex gap-4 bg-white border border-gray-100 rounded-xl p-4 hover:shadow-sm hover:border-blue-200 transition-all group">
                    <?php if (!empty($article['featured_image_url'])): ?>
                        <img src="<?= htmlspecialchars($article['featured_image_url']) ?>" alt=""
                            class="w-20 h-20 object-cover rounded-lg flex-shrink-0">
                    <?php endif; ?>
                    <div class="min-w-0">
                        <span class="text-xs text-blue-600 font-semibold">
                            <?= ucfirst($article['content_type']) ?>
                        </span>
                        <h3
                            class="font-semibold text-gray-900 group-hover:text-blue-600 line-clamp-2 text-sm leading-snug mt-0.5">
                            <?= htmlspecialchars($article['title']) ?>
                        </h3>
                        <p class="text-xs text-gray-400 mt-1">
                            <?= $article['published_at'] ? date('M j, Y', strtotime($article['published_at'])) : '' ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
            <nav class="flex justify-center gap-2 mt-8">
                <?php if ($page > 1): ?>
                    <a href="<?= url('/author/' . htmlspecialchars($author['slug'])) ?>?page=<?= $page - 1 ?>"
                        class="px-4 py-2 bg-white border rounded-lg text-sm text-gray-600 hover:bg-gray-50">← Prev</a>
                <?php endif; ?>
                <?php if ($page < $pages): ?>
                    <a href="<?= url('/author/' . htmlspecialchars($author['slug'])) ?>?page=<?= $page + 1 ?>"
                        class="px-4 py-2 bg-white border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Next →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>