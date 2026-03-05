<!-- views/admin/supervisor/errors.php -->

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-white">🐛 Error Management</h1>
            <p class="text-sm text-gray-400 mt-1">All logged errors from PHP, MySQL, APIs, and content checks</p>
        </div>
        <a href="<?= url('/admin/supervisor') ?>" class="text-sm text-gray-400 hover:text-white transition-colors">← Back to
            Dashboard</a>
    </div>

    <!-- Filters -->
    <div class="flex gap-3">
        <select
            onchange="location.href='/admin/supervisor/errors?status='+this.value+'&severity=<?= $data['filter_severity'] ?>'"
            class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white">
            <option value="all" <?= $data['filter_status'] === 'all' ? 'selected' : '' ?>>All Status</option>
            <option value="new" <?= $data['filter_status'] === 'new' ? 'selected' : '' ?>>New</option>
            <option value="acknowledged" <?= $data['filter_status'] === 'acknowledged' ? 'selected' : '' ?>>Acknowledged
            </option>
            <option value="investigating" <?= $data['filter_status'] === 'investigating' ? 'selected' : '' ?>>Investigating
            </option>
            <option value="resolved" <?= $data['filter_status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
        </select>
        <select
            onchange="location.href='/admin/supervisor/errors?status=<?= $data['filter_status'] ?>&severity='+this.value"
            class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white">
            <option value="all" <?= $data['filter_severity'] === 'all' ? 'selected' : '' ?>>All Severity</option>
            <option value="critical" <?= $data['filter_severity'] === 'critical' ? 'selected' : '' ?>>Critical</option>
            <option value="warning" <?= $data['filter_severity'] === 'warning' ? 'selected' : '' ?>>Warning</option>
            <option value="info" <?= $data['filter_severity'] === 'info' ? 'selected' : '' ?>>Info</option>
        </select>
    </div>

    <!-- Error List -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <?php if (empty($data['errors'])): ?>
            <div class="px-6 py-12 text-center text-gray-500">
                <div class="text-4xl mb-3">✨</div>
                <div>No errors found with the current filters.</div>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-700/50">
                <?php foreach ($data['errors'] as $error): ?>
                    <div class="px-5 py-4 hover:bg-gray-700/30 transition-colors">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold uppercase flex-shrink-0 <?php
                            echo match ($error['severity']) {
                                'critical' => 'bg-red-900/50 text-red-400',
                                'warning' => 'bg-yellow-900/50 text-yellow-400',
                                'info' => 'bg-blue-900/50 text-blue-400',
                                default => 'bg-gray-700 text-gray-400'
                            };
                            ?>">
                                <?= htmlspecialchars($error['severity']) ?>
                            </span>
                            <div class="flex-1">
                                <div class="text-sm text-gray-200">
                                    <?= htmlspecialchars($error['message']) ?>
                                </div>
                                <div class="text-xs text-gray-500 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                                    <span>Type:
                                        <?= htmlspecialchars($error['error_type']) ?>
                                    </span>
                                    <?php if ($error['file_path']): ?>
                                        <span>File:
                                            <?= htmlspecialchars($error['file_path']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($error['line_number']): ?>
                                        <span>Line:
                                            <?= $error['line_number'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <span>Count:
                                        <?= $error['occurrence_count'] ?>x
                                    </span>
                                    <span>Status:
                                        <?= htmlspecialchars($error['status']) ?>
                                    </span>
                                    <span>Last seen:
                                        <?= $error['last_seen_at'] ?>
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