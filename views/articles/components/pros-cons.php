<?php
/**
 * Pros & Cons Component
 * Beautiful two-column pros (green) and cons (red) boxes.
 * Variables: $pros (array), $cons (array)
 */
$prosArr = is_string($pros ?? '') ? json_decode($pros, true) : ($pros ?? []);
$consArr = is_string($cons ?? '') ? json_decode($cons, true) : ($cons ?? []);
if (empty($prosArr) && empty($consArr))
    return;
?>
<div class="my-8 not-prose">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Pros -->
        <div class="rounded-2xl overflow-hidden shadow-sm border border-emerald-200">
            <div class="px-5 py-3 bg-gradient-to-r from-emerald-500 to-green-500 flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
                <h3 class="font-bold text-white text-sm uppercase tracking-wider">Pros</h3>
            </div>
            <div class="bg-gradient-to-br from-emerald-50 to-green-50 p-5">
                <ul class="space-y-3">
                    <?php foreach ($prosArr as $pro): ?>
                        <li class="flex items-start gap-3 text-sm text-gray-700">
                            <span class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            <span class="leading-relaxed"><?= htmlspecialchars($pro) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <!-- Cons -->
        <div class="rounded-2xl overflow-hidden shadow-sm border border-red-200">
            <div class="px-5 py-3 bg-gradient-to-r from-red-500 to-rose-500 flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </span>
                <h3 class="font-bold text-white text-sm uppercase tracking-wider">Cons</h3>
            </div>
            <div class="bg-gradient-to-br from-red-50 to-rose-50 p-5">
                <ul class="space-y-3">
                    <?php foreach ($consArr as $con): ?>
                        <li class="flex items-start gap-3 text-sm text-gray-700">
                            <span class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </span>
                            <span class="leading-relaxed"><?= htmlspecialchars($con) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
