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
            <div class="w-16 h-16 rounded-full flex-shrink-0 relative overflow-hidden bg-gradient-to-br from-indigo-900 via-purple-900 to-black p-0.5 group">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-indigo-500 animate-[spin_4s_linear_infinite] opacity-50 group-hover:opacity-100 transition-opacity"></div>
                <div class="absolute inset-0.5 bg-gray-900 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-white relative z-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.54 15H17a2 2 0 0 0-2 2v4.54"/>
                        <path d="M7 3.34V5a3 3 0 0 0 3 3v0a2 2 0 0 1 2 2v0c0 1.1.9 2 2 2v0a2 2 0 0 0 2-2v0c0-1.1.9-2 2-2h1.66"/>
                        <path d="m11 7.33-1.63 2.11a2.03 2.03 0 0 0 0 2.47L11 14"/>
                        <path d="M2 12c0 5.52 4.48 10 10 10s10-4.48 10-10S17.52 2 12 2 2 6.48 2 12Z"/>
                    </svg>
                    <!-- Glowing dots animation -->
                    <div class="absolute w-1 h-1 bg-blue-400 rounded-full top-3 left-4 animate-pulse"></div>
                    <div class="absolute w-1 h-1 bg-purple-400 rounded-full bottom-4 right-4 animate-[pulse_1.5s_ease-in-out_infinite]"></div>
                </div>
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