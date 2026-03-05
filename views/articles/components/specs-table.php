<?php
/**
 * Specs Table Component
 * Renders product specifications as a clean key-value table.
 * Variables: $specifications (array of {label, value} or JSON string)
 */
$specsRaw = $specifications ?? null;
$specs = is_string($specsRaw) ? json_decode($specsRaw, true) : ($specsRaw ?? []);
$specs = is_array($specs) ? array_values($specs) : [];
if (empty($specs))
    return;
?>
<div class="my-6 not-prose">
    <h3 class="font-bold text-gray-900 mb-3 text-base">Specifications</h3>
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <tbody>
                <?php foreach ($specs as $i => $spec): ?>
                    <tr class="<?= $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">
                        <td class="py-2.5 px-4 font-semibold text-gray-700 w-2/5 border-b border-gray-100">
                            <?= htmlspecialchars($spec['label'] ?? '') ?>
                        </td>
                        <td class="py-2.5 px-4 text-gray-600 border-b border-gray-100">
                            <?= htmlspecialchars($spec['value'] ?? '') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>