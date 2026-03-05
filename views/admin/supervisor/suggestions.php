<!-- views/admin/supervisor/suggestions.php -->

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-white">💡 Improvement Suggestions</h1>
            <p class="text-sm text-gray-400 mt-1">AI-powered suggestions prioritized by impact/effort ratio</p>
        </div>
        <a href="<?= url('/admin/supervisor') ?>" class="text-sm text-gray-400 hover:text-white transition-colors">← Back to
            Dashboard</a>
    </div>

    <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <?php if (empty($data['suggestions'])): ?>
            <div class="px-6 py-12 text-center text-gray-500">
                <div class="text-4xl mb-3">🎯</div>
                <div>No suggestions yet. Run a scan from the dashboard to generate suggestions.</div>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-700/50">
                <?php foreach ($data['suggestions'] as $s): ?>
                    <div class="px-5 py-4 hover:bg-gray-700/30 transition-colors">
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-900/50 text-indigo-400 flex-shrink-0">
                                <?= htmlspecialchars($s['category']) ?>
                            </span>
                            <div class="flex-1">
                                <div class="text-sm text-gray-200 font-medium">
                                    <?= htmlspecialchars($s['title']) ?>
                                </div>
                                <?php if ($s['description']): ?>
                                    <div class="text-xs text-gray-400 mt-1">
                                        <?= htmlspecialchars($s['description']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="text-xs text-gray-500 mt-2 flex gap-4">
                                    <span>Impact: <span class="text-indigo-400">
                                            <?= $s['impact_score'] ?>/100
                                        </span></span>
                                    <span>Effort:
                                        <?= $s['effort_score'] ?>/100
                                    </span>
                                    <?php if ($s['estimated_time']): ?>
                                        <span>⏱
                                            <?= htmlspecialchars($s['estimated_time']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span>Priority:
                                        <?= htmlspecialchars($s['priority']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>