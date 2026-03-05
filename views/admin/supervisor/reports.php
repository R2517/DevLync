<!-- views/admin/supervisor/reports.php -->

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-white">📊 Supervisor Reports</h1>
            <p class="text-sm text-gray-400 mt-1">Generated audit and weekly performance reports</p>
        </div>
        <a href="<?= url('/admin/supervisor') ?>" class="text-sm text-gray-400 hover:text-white transition-colors">← Back to
            Dashboard</a>
    </div>

    <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <?php if (empty($data['reports'])): ?>
            <div class="px-6 py-12 text-center text-gray-500">
                <div class="text-4xl mb-3">📋</div>
                <div>No reports generated yet. Run a Full Audit from the dashboard to create one.</div>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-700/50">
                <?php foreach ($data['reports'] as $r): ?>
                    <div class="px-5 py-4 hover:bg-gray-700/30 transition-colors flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-200 font-medium">
                                <?= htmlspecialchars($r['title'] ?? 'Report') ?>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <?= htmlspecialchars($r['report_type']) ?> ·
                                <?= $r['generated_at'] ?>
                                ·
                                <?= $r['issues_found'] ?> issues found
                            </div>
                            <?php if ($r['summary']): ?>
                                <div class="text-xs text-gray-400 mt-1 max-w-xl truncate">
                                    <?= htmlspecialchars($r['summary']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span
                                class="text-lg font-bold <?= ($r['overall_score'] ?? 0) >= 80 ? 'text-green-400' : (($r['overall_score'] ?? 0) >= 50 ? 'text-yellow-400' : 'text-red-400') ?>">
                                <?= $r['overall_score'] ?? 0 ?>/100
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>