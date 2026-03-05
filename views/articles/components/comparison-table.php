<?php
/**
 * Comparison Table Component
 * Full side-by-side comparison with checkmarks, scores, pricing, and CTAs.
 * Variables: $comparisonTable (array or JSON string)
 */
$table = is_string($comparisonTable ?? '') ? json_decode($comparisonTable, true) : ($comparisonTable ?? null);
if (empty($table) || empty($table['products']) || empty($table['features']))
    return;
$products = $table['products'];
$features = $table['features'];
?>
<div class="my-8 not-prose overflow-x-auto">
    <h2 class="font-bold text-gray-900 text-xl mb-4">Side-by-Side Comparison</h2>
    <table class="comparison-table w-full border border-gray-200 rounded-xl overflow-hidden text-sm">
        <thead>
            <tr>
                <th class="text-left w-1/4">Feature</th>
                <?php foreach ($products as $product): ?>
                    <th class="text-center">
                        <div class="font-bold">
                            <?= htmlspecialchars($product['name'] ?? '') ?>
                        </div>
                        <?php if (!empty($product['price'])): ?>
                            <div class="font-normal text-blue-200 text-xs">
                                <?= htmlspecialchars($product['price']) ?>
                            </div>
                        <?php endif; ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($features as $feature): ?>
                <tr>
                    <td class="font-semibold text-gray-700 bg-gray-50">
                        <?= htmlspecialchars($feature['label'] ?? '') ?>
                    </td>
                    <?php foreach ($products as $product): ?>
                        <?php $val = $feature['values'][$product['id']] ?? null; ?>
                        <td class="text-center">
                            <?php if ($val === true || $val === 'yes'): ?>
                                <svg class="w-5 h-5 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            <?php elseif ($val === false || $val === 'no'): ?>
                                <svg class="w-5 h-5 text-red-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            <?php elseif ($val !== null): ?>
                                <span class="text-gray-700">
                                    <?= htmlspecialchars((string) $val) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-300">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <!-- Rating Row -->
            <tr class="bg-blue-50">
                <td class="font-bold text-gray-900">DevLync Rating</td>
                <?php foreach ($products as $product): ?>
                    <td class="text-center font-bold text-blue-700">
                        <?= !empty($product['rating']) ? number_format((float) $product['rating'], 1) . '/10' : '—' ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <!-- CTA Row -->
            <tr>
                <td></td>
                <?php foreach ($products as $product): ?>
                    <td class="text-center py-2">
                        <?php if (!empty($product['affiliate_url'])): ?>
                            <a href="<?= htmlspecialchars($product['affiliate_url']) ?>" rel="nofollow noopener sponsored"
                                target="_blank"
                                class="inline-block bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-blue-700 transition-colors">
                                Try
                                <?= htmlspecialchars($product['name'] ?? '') ?> →
                            </a>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>
</div>