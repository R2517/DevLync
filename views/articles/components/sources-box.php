<?php
/**
 * Sources Box Component — EEAT
 * Displays references list with external links.
 * Variables: $sources (array of {name, url} or JSON string)
 */
$sourcesList = is_string($sources ?? '') ? json_decode($sources, true) : ($sources ?? []);
if (empty($sourcesList))
    return;
?>
<div class="my-8 bg-gray-50 border border-gray-100 rounded-xl p-5 not-prose">
    <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        Sources & References
    </h3>
    <ol class="space-y-1.5">
        <?php foreach ($sourcesList as $i => $source): ?>
            <li class="flex items-start gap-2 text-sm">
                <span class="text-gray-400 font-mono text-xs mt-0.5 flex-shrink-0">
                    <?= $i + 1 ?>.
                </span>
                <?php if (!empty($source['url'])): ?>
                    <a href="<?= htmlspecialchars($source['url']) ?>" rel="nofollow noopener noreferrer" target="_blank"
                        class="text-blue-600 hover:text-blue-800 hover:underline break-all">
                        <?= htmlspecialchars($source['name'] ?? $source['url']) ?>
                    </a>
                <?php else: ?>
                    <span class="text-gray-600">
                        <?= htmlspecialchars($source['name'] ?? '') ?>
                    </span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</div>