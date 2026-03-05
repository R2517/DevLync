<?php
/**
 * Key Takeaways Component — AI SEO
 * Renders a styled box with bullet-point key takeaways.
 * Variables: $keyTakeaways (array of strings, decoded from JSON)
 */
if (empty($keyTakeaways))
    return;
$takeaways = is_string($keyTakeaways) ? json_decode($keyTakeaways, true) : $keyTakeaways;
if (empty($takeaways))
    return;
?>
<div class="my-8 not-prose">
    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 flex items-center gap-3">
            <span class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </span>
            <h2 class="font-bold text-white text-sm uppercase tracking-wider">Key Takeaways</h2>
        </div>
        <ul class="px-6 py-5 space-y-3">
            <?php foreach ($takeaways as $i => $point): ?>
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5"><?= $i + 1 ?></span>
                    <span class="text-sm text-gray-700 leading-relaxed"><?= htmlspecialchars($point) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
