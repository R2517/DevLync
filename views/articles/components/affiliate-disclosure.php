<?php
/**
 * Affiliate Disclosure Component — FTC Compliance
 * Yellow warning box for FTC-required affiliate link disclosure.
 * Variables: $ftcDisclosure (string, optional)
 */
$text = !empty($ftcDisclosure)
    ? $ftcDisclosure
    : 'This article may contain affiliate links. If you purchase through these links, DevLync may earn a small commission at no extra cost to you. This does not influence our editorial decisions or ratings.';
?>
<div class="my-6 bg-yellow-50 border border-yellow-200 rounded-xl p-4 not-prose" role="note"
    aria-label="Affiliate Disclosure">
    <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
            <p class="font-semibold text-yellow-800 text-xs uppercase tracking-wide mb-0.5">Affiliate Disclosure</p>
            <p class="text-yellow-700 text-sm leading-relaxed">
                <?= htmlspecialchars($text) ?>
            </p>
            <a href="<?= url('/affiliate-disclosure') ?>"
                class="text-yellow-600 hover:text-yellow-800 text-xs underline mt-1 inline-block">Learn more about our
                editorial policy →</a>
        </div>
    </div>
</div>