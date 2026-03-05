<!-- views/admin/supervisor/activity.php -->

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-white">📋 Activity Log</h1>
            <p class="text-sm text-gray-400 mt-1">Complete audit trail of all supervisor actions</p>
        </div>
        <a href="<?= url('/admin/supervisor') ?>" class="text-sm text-gray-400 hover:text-white transition-colors">← Back to
            Dashboard</a>
    </div>

    <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <?php if (empty($data['activities'])): ?>
            <div class="px-6 py-12 text-center text-gray-500">
                <div class="text-4xl mb-3">📋</div>
                <div>No activity logged yet.</div>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-700/50">
                <?php foreach ($data['activities'] as $a): ?>
                    <div class="px-5 py-3 flex items-center gap-4 hover:bg-gray-700/30 transition-colors">
                        <span class="text-xs text-gray-500 w-40 flex-shrink-0">
                            <?= $a['created_at'] ?>
                        </span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] uppercase flex-shrink-0 <?php
                        echo match ($a['triggered_by']) {
                            'auto' => 'bg-green-900/40 text-green-400',
                            'manual' => 'bg-blue-900/40 text-blue-400',
                            'ai' => 'bg-purple-900/40 text-purple-400',
                            default => 'bg-gray-700 text-gray-400'
                        };
                        ?>">
                            <?= htmlspecialchars($a['triggered_by']) ?>
                        </span>
                        <span class="text-sm text-gray-300">
                            <?= htmlspecialchars($a['action_description']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>