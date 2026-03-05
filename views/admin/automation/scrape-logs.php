<?php
/**
 * Admin Scrape Logs View
 * Variables: $data (array with logs, sessions, selected_session_id, selected_session_logs, stats, filters)
 */
$logs = $data['logs'] ?? [];
$sessions = $data['sessions'] ?? [];
$selectedSessionId = $data['selected_session_id'] ?? '';
$selectedLogs = $data['selected_session_logs'] ?? [];
$stats = $data['stats'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="space-y-5">
    <?php include VIEWS_PATH . '/admin/automation/_nav.php'; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Total Sessions</div>
            <div class="mt-2 text-2xl font-bold text-blue-400"><?= number_format($stats['total_sessions'] ?? 0) ?></div>
        </div>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Successful</div>
            <div class="mt-2 text-2xl font-bold text-green-400"><?= number_format($stats['total_success'] ?? 0) ?></div>
        </div>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Failed</div>
            <div class="mt-2 text-2xl font-bold text-red-400"><?= number_format($stats['total_failed'] ?? 0) ?></div>
        </div>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Total Items Saved</div>
            <div class="mt-2 text-2xl font-bold text-purple-400"><?= number_format($stats['total_items_saved'] ?? 0) ?></div>
        </div>
    </div>

    <!-- Source Breakdown -->
    <?php if (!empty($stats['by_source'])): ?>
        <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
            <div class="border-b border-gray-700 px-4 py-3">
                <h2 class="text-lg font-semibold text-white">Source Performance</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-900 text-gray-300">
                        <tr>
                            <th class="px-4 py-2 text-left">Source</th>
                            <th class="px-4 py-2 text-left">Sessions</th>
                            <th class="px-4 py-2 text-left">Found</th>
                            <th class="px-4 py-2 text-left">Saved</th>
                            <th class="px-4 py-2 text-left">Skipped</th>
                            <th class="px-4 py-2 text-left">Errors</th>
                            <th class="px-4 py-2 text-left">Success Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($stats['by_source'] as $src): ?>
                            <?php
                            $sessions_count = (int) ($src['sessions'] ?? 0);
                            $success_count = (int) ($src['success_count'] ?? 0);
                            $rate = $sessions_count > 0 ? round(($success_count / $sessions_count) * 100) : 0;
                            ?>
                            <tr class="text-gray-200">
                                <td class="px-4 py-2 font-medium"><?= htmlspecialchars($src['source_type'] ?? '—') ?></td>
                                <td class="px-4 py-2"><?= $sessions_count ?></td>
                                <td class="px-4 py-2"><?= number_format((int) ($src['total_found'] ?? 0)) ?></td>
                                <td class="px-4 py-2 text-green-400"><?= number_format((int) ($src['total_saved'] ?? 0)) ?></td>
                                <td class="px-4 py-2 text-yellow-400"><?= number_format((int) ($src['total_skipped'] ?? 0)) ?></td>
                                <td class="px-4 py-2 text-red-400"><?= (int) ($src['error_count'] ?? 0) ?></td>
                                <td class="px-4 py-2">
                                    <span class="<?= $rate >= 80 ? 'text-green-400' : ($rate >= 50 ? 'text-yellow-400' : 'text-red-400') ?>"><?= $rate ?>%</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sessions + Detail Split -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <!-- Session List -->
        <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
            <div class="border-b border-gray-700 px-4 py-3">
                <h2 class="text-lg font-semibold text-white">Scraper Sessions</h2>
            </div>
            <?php if (empty($sessions)): ?>
                <div class="px-4 py-8 text-sm text-gray-400">No scraper sessions recorded yet.</div>
            <?php else: ?>
                <div class="max-h-[520px] overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-gray-900 text-gray-300">
                            <tr>
                                <th class="px-4 py-2 text-left">Session</th>
                                <th class="px-4 py-2 text-left">Sources</th>
                                <th class="px-4 py-2 text-left">Saved</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php foreach ($sessions as $session): ?>
                                <?php
                                $sid = htmlspecialchars($session['session_id'] ?? '');
                                $errors = (int) ($session['error_count'] ?? 0);
                                $isSelected = $selectedSessionId === ($session['session_id'] ?? '');
                                ?>
                                <tr class="<?= $isSelected ? 'bg-gray-900/60' : '' ?> hover:bg-gray-800/40">
                                    <td class="px-4 py-2">
                                        <a href="<?= url('/admin/automation/scrape-logs') ?>?session_id=<?= urlencode($session['session_id'] ?? '') ?>"
                                            class="text-indigo-400 hover:text-indigo-300 text-xs font-mono">
                                            <?= mb_substr($sid, 0, 12) ?>...
                                        </a>
                                    </td>
                                    <td class="px-4 py-2 text-gray-300"><?= (int) ($session['source_count'] ?? 0) ?></td>
                                    <td class="px-4 py-2 text-green-400"><?= (int) ($session['total_saved'] ?? 0) ?></td>
                                    <td class="px-4 py-2">
                                        <?php if ($errors > 0): ?>
                                            <span class="text-xs bg-red-900 text-red-300 px-2 py-0.5 rounded-full"><?= $errors ?> error(s)</span>
                                        <?php else: ?>
                                            <span class="text-xs bg-green-900 text-green-300 px-2 py-0.5 rounded-full">OK</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-400">
                                        <?= !empty($session['started_at']) ? date('M j, H:i', strtotime($session['started_at'])) : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Session Detail -->
        <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
            <div class="border-b border-gray-700 px-4 py-3">
                <h2 class="text-lg font-semibold text-white">
                    Session Detail
                    <?php if ($selectedSessionId !== ''): ?>
                        <span class="text-sm font-mono text-gray-400 ml-2"><?= htmlspecialchars(mb_substr($selectedSessionId, 0, 16)) ?>...</span>
                    <?php endif; ?>
                </h2>
            </div>
            <?php if ($selectedSessionId === ''): ?>
                <div class="px-4 py-8 text-sm text-gray-400">Select a session on the left to view source-level details.</div>
            <?php elseif (empty($selectedLogs)): ?>
                <div class="px-4 py-8 text-sm text-gray-400">No log entries found for this session.</div>
            <?php else: ?>
                <div class="max-h-[520px] overflow-auto divide-y divide-gray-700">
                    <?php foreach ($selectedLogs as $log): ?>
                        <?php $isError = ($log['status'] ?? '') === 'error'; ?>
                        <div class="px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="rounded px-2 py-0.5 text-[11px] font-semibold
                                        <?= $isError ? 'bg-red-900 text-red-300' : 'bg-green-900 text-green-300' ?>">
                                        <?= htmlspecialchars(strtoupper($log['status'] ?? 'unknown')) ?>
                                    </span>
                                    <span class="text-sm font-semibold text-gray-100"><?= htmlspecialchars($log['source_type'] ?? '—') ?></span>
                                </div>
                                <span class="text-xs text-gray-500"><?= htmlspecialchars($log['created_at'] ?? '') ?></span>
                            </div>
                            <?php if (!empty($log['query'])): ?>
                                <div class="text-xs text-gray-400 mt-1">Query: <span class="text-gray-300"><?= htmlspecialchars($log['query']) ?></span></div>
                            <?php endif; ?>
                            <div class="flex items-center gap-4 mt-2 text-xs">
                                <span class="text-gray-400">Found: <span class="text-blue-400 font-semibold"><?= (int) ($log['items_found'] ?? 0) ?></span></span>
                                <span class="text-gray-400">Saved: <span class="text-green-400 font-semibold"><?= (int) ($log['items_saved'] ?? 0) ?></span></span>
                                <span class="text-gray-400">Skipped: <span class="text-yellow-400 font-semibold"><?= (int) ($log['items_skipped'] ?? 0) ?></span></span>
                                <span class="text-gray-400">Duration: <span class="text-gray-300"><?= (int) ($log['duration_seconds'] ?? 0) ?>s</span></span>
                            </div>
                            <?php if ($isError && !empty($log['error_message'])): ?>
                                <pre class="mt-2 overflow-x-auto rounded bg-red-950 border border-red-900 p-2 text-xs text-red-300"><?= htmlspecialchars($log['error_message']) ?></pre>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Raw Logs -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
        <div class="border-b border-gray-700 px-4 py-3">
            <h2 class="text-lg font-semibold text-white">Recent Scrape Logs <span class="text-sm text-gray-400">(all sources, last 200)</span></h2>
        </div>
        <?php if (empty($logs)): ?>
            <div class="px-4 py-8 text-sm text-gray-400">No scrape logs recorded yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-gray-900 text-gray-300">
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Source</th>
                            <th class="px-4 py-2 text-left">Query</th>
                            <th class="px-4 py-2 text-left">Found</th>
                            <th class="px-4 py-2 text-left">Saved</th>
                            <th class="px-4 py-2 text-left">Skipped</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Duration</th>
                            <th class="px-4 py-2 text-left">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($logs as $log): ?>
                            <tr class="text-gray-200 hover:bg-gray-800/40">
                                <td class="px-4 py-2 text-xs text-gray-500"><?= (int) $log['id'] ?></td>
                                <td class="px-4 py-2 text-xs"><?= htmlspecialchars($log['source_type'] ?? '') ?></td>
                                <td class="px-4 py-2 text-xs text-gray-400 max-w-[200px] truncate"><?= htmlspecialchars(mb_substr($log['query'] ?? '—', 0, 40)) ?></td>
                                <td class="px-4 py-2 text-xs text-blue-400"><?= (int) ($log['items_found'] ?? 0) ?></td>
                                <td class="px-4 py-2 text-xs text-green-400"><?= (int) ($log['items_saved'] ?? 0) ?></td>
                                <td class="px-4 py-2 text-xs text-yellow-400"><?= (int) ($log['items_skipped'] ?? 0) ?></td>
                                <td class="px-4 py-2">
                                    <?php $isErr = ($log['status'] ?? '') === 'error'; ?>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold <?= $isErr ? 'bg-red-900 text-red-300' : 'bg-green-900 text-green-300' ?>">
                                        <?= htmlspecialchars($log['status'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-400"><?= (int) ($log['duration_seconds'] ?? 0) ?>s</td>
                                <td class="px-4 py-2 text-xs text-gray-500"><?= !empty($log['created_at']) ? date('M j, H:i', strtotime($log['created_at'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
