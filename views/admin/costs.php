<?php
/**
 * Admin Costs View
 * Variables: $breakdown (array), $today (float), $month (float)
 */
?>
<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <p class="text-xs text-gray-400 mb-1">Today</p>
            <p class="text-2xl font-bold text-white">$
                <?= number_format((float) $today, 4) ?>
            </p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <p class="text-xs text-gray-400 mb-1">This Month</p>
            <p class="text-2xl font-bold text-white">$
                <?= number_format((float) $month, 4) ?>
            </p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <p class="text-xs text-gray-400 mb-1">Total Records</p>
            <p class="text-2xl font-bold text-white">
                <?= number_format(count($breakdown)) ?>
            </p>
        </div>
    </div>

    <!-- Daily Breakdown Table -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-800">
            <h2 class="font-semibold text-white text-sm">Daily AI Spending (Last 30 Days)</h2>
        </div>
        <?php if (empty($breakdown)): ?>
            <div class="text-center py-12 text-gray-500 text-sm">No cost records yet. Data will appear once n8n starts
                generating articles.</div>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead class="border-b border-gray-800">
                    <tr class="text-xs text-gray-400 uppercase tracking-wider text-left">
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3">Model</th>
                        <th class="px-4 py-3">Input Tokens</th>
                        <th class="px-4 py-3">Output Tokens</th>
                        <th class="px-4 py-3">Cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <?php foreach ($breakdown as $row): ?>
                        <tr class="hover:bg-gray-800/40">
                            <td class="px-4 py-3 text-gray-300 text-xs">
                                <?= htmlspecialchars($row['date'] ?? $row['created_at'] ?? '—') ?>
                            </td>
                            <td class="px-4 py-3 text-gray-300 text-xs capitalize">
                                <?= htmlspecialchars($row['provider'] ?? '—') ?>
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-xs">
                                <?= htmlspecialchars($row['model'] ?? '—') ?>
                            </td>
                            <td class="px-4 py-3 text-gray-300 text-xs">
                                <?= number_format((int) ($row['total_input_tokens'] ?? $row['input_tokens'] ?? 0)) ?>
                            </td>
                            <td class="px-4 py-3 text-gray-300 text-xs">
                                <?= number_format((int) ($row['total_output_tokens'] ?? $row['output_tokens'] ?? 0)) ?>
                            </td>
                            <td class="px-4 py-3 text-green-400 text-xs font-medium">
                                $
                                <?= number_format((float) ($row['total_cost'] ?? $row['cost'] ?? 0), 4) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>