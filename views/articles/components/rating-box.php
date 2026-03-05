<?php
/**
 * Rating Box Component
 * Large circle with numerical score (0-10), color-coded by rating level.
 * Variables: $rating (float), $productName (string)
 */
if (!isset($rating))
    return;
$score = round((float) $rating, 1);

// Color coding by rating
if ($score >= 8.5) {
    $color = 'text-green-600';
    $bgColor = 'bg-green-50';
    $ringColor = 'border-green-500';
    $label = 'Excellent';
} elseif ($score >= 7.0) {
    $color = 'text-blue-600';
    $bgColor = 'bg-blue-50';
    $ringColor = 'border-blue-500';
    $label = 'Very Good';
} elseif ($score >= 5.5) {
    $color = 'text-yellow-600';
    $bgColor = 'bg-yellow-50';
    $ringColor = 'border-yellow-500';
    $label = 'Average';
} else {
    $color = 'text-red-600';
    $bgColor = 'bg-red-50';
    $ringColor = 'border-red-500';
    $label = 'Below Average';
}
?>
<div class="flex flex-col items-center gap-2 <?= $bgColor ?> rounded-2xl p-6">
    <div class="w-24 h-24 rounded-full border-4 <?= $ringColor ?> flex items-center justify-center">
        <span class="text-3xl font-extrabold <?= $color ?>">
            <?= number_format($score, 1) ?>
        </span>
    </div>
    <div class="text-center">
        <div class="text-xs font-semibold uppercase tracking-wider text-gray-400">DevLync Score</div>
        <div class="font-bold <?= $color ?> text-sm">
            <?= $label ?>
        </div>
    </div>
    <?php if (!empty($productName)): ?>
        <div class="text-xs text-gray-500 text-center">
            <?= htmlspecialchars($productName) ?>
        </div>
    <?php endif; ?>
</div>