<?php
/**
 * Image Placeholder Component
 * Shown when the article image is not yet uploaded.
 * Variables: $slot (string), $altText (string)
 */
?>
<div
    class="w-full bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl flex flex-col items-center justify-center py-12 text-gray-400 border-2 border-dashed border-gray-300 my-4 not-prose">
    <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>
    <p class="text-sm font-medium">Image Coming Soon</p>
    <?php if (!empty($altText)): ?>
        <p class="text-xs mt-1 max-w-xs text-center">
            <?= htmlspecialchars($altText) ?>
        </p>
    <?php endif; ?>
</div>