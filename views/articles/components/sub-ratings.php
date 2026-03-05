<?php
/**
 * Sub-Ratings Component
 * Displays 5 category rating bars for reviews.
 * Variables: $article (array with rating_features, rating_pricing, etc.)
 */
$categories = [
    'Features' => $article['rating_features'] ?? null,
    'Pricing' => $article['rating_pricing'] ?? null,
    'Performance' => $article['rating_performance'] ?? null,
    'Support' => $article['rating_support'] ?? null,
    'Ease of Use' => $article['rating_ease_of_use'] ?? null,
];
$hasRatings = array_filter($categories, fn($v) => $v !== null);
if (empty($hasRatings))
    return;
?>
<div class="space-y-3 my-4">
    <?php foreach ($categories as $label => $score): ?>
        <?php if ($score === null)
            continue; ?>
        <?php
        $pct = min(100, round(((float) $score / 10) * 100));
        if ($pct >= 80)
            $barColor = 'bg-green-500';
        elseif ($pct >= 60)
            $barColor = 'bg-blue-500';
        elseif ($pct >= 40)
            $barColor = 'bg-yellow-500';
        else
            $barColor = 'bg-red-500';
        ?>
        <div>
            <div class="flex items-center justify-between mb-1">
                <span class="text-sm font-medium text-gray-700">
                    <?= $label ?>
                </span>
                <span class="text-sm font-bold text-gray-900">
                    <?= number_format((float) $score, 1) ?>/10
                </span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="rating-bar-fill <?= $barColor ?>" style="width: <?= $pct ?>%"></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>