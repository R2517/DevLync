<?php
/**
 * Quick Answer Component — AI SEO
 * Renders a prominent box with a 40-60 word direct answer for featured snippets.
 * Variables: $directAnswer (string)
 */
if (empty($directAnswer))
    return;
?>
<div class="my-8 not-prose">
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 flex items-center gap-3">
            <span class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            <h2 class="font-bold text-white text-sm uppercase tracking-wider">Quick Answer</h2>
        </div>
        <div class="px-6 py-5">
            <p class="text-gray-700 text-base leading-relaxed font-medium">
                <?= htmlspecialchars($directAnswer) ?>
            </p>
        </div>
    </div>
</div>
