<?php
/**
 * Author Box Component — EEAT
 * Displays author bio, expertise, avatar, and social links.
 * Variables: $article (array with author_name, author_bio, author_avatar, social_twitter, etc.)
 */
if (empty($article['author_name']))
    return;
?>
<div class="my-8 bg-gray-50 border border-gray-200 rounded-2xl p-6 not-prose">
    <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold mb-3">Written By</p>
    <div class="flex items-start gap-4">
        <?php if (!empty($article['author_avatar'])): ?>
            <img src="<?= htmlspecialchars($article['author_avatar']) ?>"
                alt="<?= htmlspecialchars($article['author_name']) ?>"
                class="w-16 h-16 rounded-full object-cover flex-shrink-0 border-2 border-gray-200">
        <?php else: ?>
            <div
                class="w-16 h-16 rounded-full bg-blue-600 flex items-center justify-center flex-shrink-0 text-white text-xl font-bold">
                <?= strtoupper(substr($article['author_name'], 0, 1)) ?>
            </div>
        <?php endif; ?>
        <div class="min-w-0 flex-1">
            <a href="<?= url('/author/' . htmlspecialchars($article['author_slug'] ?? 'devlync-team')) ?>"
                class="font-bold text-gray-900 hover:text-blue-600 transition-colors text-base">
                <?= htmlspecialchars($article['author_name']) ?>
            </a>
            <?php if (!empty($article['author_bio'])): ?>
                <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                    <?= htmlspecialchars($article['author_bio']) ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($article['expertise'])): ?>
                <p class="text-xs text-gray-400 mt-1">Expertise:
                    <?= htmlspecialchars($article['expertise']) ?>
                </p>
            <?php endif; ?>
            <div class="flex items-center gap-3 mt-2">
                <?php if (!empty($article['social_twitter'])): ?>
                    <a href="https://twitter.com/<?= htmlspecialchars(ltrim($article['social_twitter'], '@')) ?>"
                        rel="noopener noreferrer" target="_blank"
                        class="text-gray-400 hover:text-blue-500 transition-colors text-xs">Twitter</a>
                <?php endif; ?>
                <?php if (!empty($article['social_linkedin'])): ?>
                    <a href="<?= htmlspecialchars($article['social_linkedin']) ?>" rel="noopener noreferrer" target="_blank"
                        class="text-gray-400 hover:text-blue-700 transition-colors text-xs">LinkedIn</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>