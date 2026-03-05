<?php
/**
 * Key Facts Component — News Articles
 * Red box with bullet-point key facts for news articles.
 * Variables: $keyFacts (array or JSON string)
 */
$facts = is_string($keyFacts ?? '') ? json_decode($keyFacts, true) : ($keyFacts ?? []);
if (empty($facts))
    return;
?>
<div class="my-6 bg-red-50 border border-red-200 rounded-xl p-5 not-prose">
    <h2 class="font-bold text-red-900 text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Key Facts
    </h2>
    <ul class="space-y-2">
        <?php foreach ($facts as $fact): ?>
            <li class="flex items-start gap-2 text-sm text-red-800">
                <span class="flex-shrink-0 w-1.5 h-1.5 bg-red-600 rounded-full mt-1.5"></span>
                <?= htmlspecialchars($fact) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>