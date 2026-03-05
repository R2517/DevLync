<?php
/**
 * Verdict Box Component
 * Final review verdict with rating bar and affiliate CTA.
 * Variables: $verdict (string), $rating (float), $affiliateUrl (string), $productName (string)
 */
if (empty($verdict))
    return;
$score = (float) ($rating ?? 0);
$pct = min(100, round(($score / 10) * 100));
?>
<div class="my-8 bg-gradient-to-br from-gray-900 to-gray-800 text-white rounded-2xl p-7 not-prose">
    <div class="flex items-center gap-2 mb-4">
        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
            <path
                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
        <span class="font-bold text-lg">Our Verdict</span>
        <?php if ($score): ?>
            <span class="ml-auto text-2xl font-extrabold text-yellow-400">
                <?= number_format($score, 1) ?>/10
            </span>
        <?php endif; ?>
    </div>
    <?php if ($score): ?>
        <div class="h-2 bg-gray-700 rounded-full mb-5 overflow-hidden">
            <div class="h-full bg-yellow-400 rounded-full transition-all" style="width:<?= $pct ?>%"></div>
        </div>
    <?php endif; ?>
    <p class="text-gray-200 leading-relaxed mb-5">
        <?= htmlspecialchars($verdict) ?>
    </p>
    <?php if (!empty($affiliateUrl) && !empty($productName)): ?>
        <a href="<?= htmlspecialchars($affiliateUrl) ?>" rel="nofollow noopener sponsored" target="_blank"
            class="inline-block bg-blue-600 hover:bg-blue-500 text-white font-bold px-6 py-3 rounded-xl transition-colors">
            Try
            <?= htmlspecialchars($productName) ?> →
        </a>
    <?php endif; ?>
</div>