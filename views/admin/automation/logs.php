<?php
$runs = $data['runs'] ?? [];
$logs = $data['selected_logs'] ?? [];
$filters = $data['filters'] ?? ['module' => '', 'status' => ''];
$selectedRunId = (int) ($data['selected_run_id'] ?? 0);
?>

<div class="space-y-5">
    <?php include VIEWS_PATH . '/admin/automation/_nav.php'; ?>

    <form method="get" action="<?= url('/admin/automation/logs') ?>" class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="text-xs text-gray-400">Module</label>
                <input type="text" name="module" value="<?= htmlspecialchars($filters['module'] ?? '') ?>"
                    class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="text-xs text-gray-400">Status</label>
                <select name="status" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                    <?php $statuses = ['', 'running', 'completed', 'failed', 'skipped']; ?>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= $status ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                            <?= $status === '' ? 'All' : ucfirst($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2 flex items-end gap-2">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Apply Filters</button>
                <a href="<?= url('/admin/automation/logs') ?>" class="rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-gray-200 hover:bg-gray-600">Reset</a>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
            <div class="border-b border-gray-700 px-4 py-3">
                <h2 class="text-lg font-semibold text-white">Execution History</h2>
            </div>
            <div class="max-h-[560px] overflow-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-gray-900 text-gray-300">
                        <tr>
                            <th class="px-4 py-2 text-left">Run</th>
                            <th class="px-4 py-2 text-left">Module</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($runs as $run): ?>
                            <tr class="<?= $selectedRunId === (int) $run['id'] ? 'bg-gray-900/60' : '' ?>">
                                <td class="px-4 py-2">
                                    <a class="text-indigo-400 hover:text-indigo-300"
                                        href="<?= url('/admin/automation/logs?run_id=' . (int) $run['id']) ?>">
                                        #<?= (int) $run['id'] ?>
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-gray-200"><?= htmlspecialchars((string) $run['module']) ?></td>
                                <td class="px-4 py-2">
                                    <span class="rounded px-2 py-0.5 text-xs font-semibold
                                        <?= $run['status'] === 'completed' ? 'bg-green-900 text-green-300' : '' ?>
                                        <?= $run['status'] === 'failed' ? 'bg-red-900 text-red-300' : '' ?>
                                        <?= $run['status'] === 'running' ? 'bg-blue-900 text-blue-300' : '' ?>
                                        <?= $run['status'] === 'skipped' ? 'bg-yellow-900 text-yellow-300' : '' ?>">
                                        <?= htmlspecialchars((string) $run['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-gray-300"><?= htmlspecialchars((string) $run['started_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
            <div class="border-b border-gray-700 px-4 py-3">
                <h2 class="text-lg font-semibold text-white">Run Logs <?= $selectedRunId > 0 ? '(#' . $selectedRunId . ')' : '' ?></h2>
            </div>
            <div class="max-h-[560px] overflow-auto divide-y divide-gray-700">
                <?php if (!$selectedRunId): ?>
                    <div class="px-4 py-8 text-sm text-gray-400">Select a run on the left to inspect step-by-step logs.</div>
                <?php elseif (!$logs): ?>
                    <div class="px-4 py-8 text-sm text-gray-400">No log lines found for this run.</div>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="rounded px-2 py-0.5 text-[11px] font-semibold
                                    <?= $log['log_level'] === 'critical' ? 'bg-red-900 text-red-300' : '' ?>
                                    <?= $log['log_level'] === 'error' ? 'bg-red-900 text-red-300' : '' ?>
                                    <?= $log['log_level'] === 'warning' ? 'bg-yellow-900 text-yellow-300' : '' ?>
                                    <?= $log['log_level'] === 'info' ? 'bg-blue-900 text-blue-300' : '' ?>
                                    <?= $log['log_level'] === 'debug' ? 'bg-gray-700 text-gray-300' : '' ?>">
                                    <?= strtoupper(htmlspecialchars((string) $log['log_level'])) ?>
                                </span>
                                <span class="text-xs text-gray-500"><?= htmlspecialchars((string) $log['created_at']) ?></span>
                                <span class="text-xs text-gray-400"><?= htmlspecialchars((string) $log['step']) ?></span>
                            </div>
                            <div class="mt-1 text-sm text-gray-200"><?= htmlspecialchars((string) $log['message']) ?></div>
                            <?php if (!empty($log['context_data'])): ?>
                                <pre class="mt-2 overflow-x-auto rounded bg-gray-900 p-2 text-xs text-gray-300"><?= htmlspecialchars((string) $log['context_data']) ?></pre>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

