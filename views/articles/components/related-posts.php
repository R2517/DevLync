<?php
/**
 * Related Posts Component
 * 3-5 related article cards with linked images and titles.
 * Variables: $relatedPosts (array of article rows)
 */
if (empty($relatedPosts))
    return;
$typeMap = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'];
?>
<div class="my-10 not-prose">
    <h2 class="text-xl font-bold text-gray-900 mb-5">Related Articles</h2>
    <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-<?= min(count($relatedPosts), 4) <= 2 ? '2' : '4' ?> gap-5">
        <?php foreach ($relatedPosts as $post): ?>
            <?php $urlBase = $typeMap[$post['content_type']] ?? 'blog'; ?>
            <a href="<?= url('/' . $urlBase . '/' . htmlspecialchars($post['slug'])) ?>"
                class="article-card group bg-white border border-gray-100 rounded-xl overflow-hidden hover:shadow-md block">
                <?php if (!empty($post['featured_image_url'])): ?>
                    <img src="<?= htmlspecialchars($post['featured_image_url']) ?>"
                        alt="<?= htmlspecialchars($post['featured_image_alt'] ?? $post['title']) ?>"
                        class="w-full h-32 object-cover" loading="lazy">
                <?php endif; ?>
                <div class="p-4">
                    <p class="text-xs text-blue-600 font-semibold uppercase mb-1">
                        <?= ucfirst($post['content_type']) ?>
                    </p>
                    <h3
                        class="text-sm font-semibold text-gray-900 group-hover:text-blue-600 line-clamp-2 transition-colors leading-snug">
                        <?= htmlspecialchars($post['title']) ?>
                    </h3>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>