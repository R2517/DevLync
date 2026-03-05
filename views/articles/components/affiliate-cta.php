<?php
/**
 * Affiliate CTA Component
 * Blue call-to-action button with affiliate link.
 * Variables: $affiliateUrl (string), $ctaText (string), $productName (string)
 */
if (empty($affiliateUrl))
    return;
$ctaLabel = $ctaText ?? ('Try ' . ($productName ?? 'This Tool') . ' →');
?>
<div class="my-6 text-center not-prose">
    <a href="<?= htmlspecialchars($affiliateUrl) ?>" rel="nofollow noopener sponsored" target="_blank"
        class="affiliate-link inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-base px-8 py-3.5 rounded-xl transition-colors shadow-md hover:shadow-lg">
        <?= htmlspecialchars($ctaLabel) ?>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
        </svg>
    </a>
</div>