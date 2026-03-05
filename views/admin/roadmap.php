<?php
/**
 * Admin Roadmap View
 * Variables: $result (array), $status (string), $counts (array)
 */
$items = is_array($result) ? ($result['items'] ?? (isset($result[0]) ? $result : [])) : [];
$typeColors = ['blog' => 'blue', 'review' => 'purple', 'comparison' => 'green', 'news' => 'red'];
$priorityLabels = [1 => '🔥 Critical', 2 => '⬆ High', 3 => '— Normal', 4 => '⬇ Low', 5 => '🧊 Minimal'];
$statuses = ['pending', 'needs_review', 'queued', 'generating', 'published', 'failed', 'skipped'];
?>
<div x-data="roadmapActions()" class="space-y-5">
    <!-- Status Tabs & Actions -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            <?php foreach ($statuses as $s): ?>
                <a href="<?= url('/admin/roadmap') ?>?status=<?= $s ?>"
                    class="text-xs px-4 py-2 rounded-lg font-medium transition-colors <?= ($status ?? 'pending') === $s ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white' ?>">
                    <?= ucfirst(str_replace('_', ' ', $s)) ?>
                    <?php if (isset($counts[$s])): ?>
                        <span class="ml-1 opacity-70">(
                            <?= $counts[$s] ?>)
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <a href="<?= url('/admin/roadmap/create') ?>"
            class="text-xs px-4 py-2 rounded-lg font-bold bg-blue-600 text-white hover:bg-blue-500 transition-colors shadow-sm">
            + Add Keyword
        </a>
    </div>

    <!-- Table -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <?php if (empty($items)): ?>
            <div class="text-center py-14 text-gray-500 text-sm">No
                <?= $status ?> items in the roadmap.
            </div>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead class="border-b border-gray-800">
                    <tr class="text-xs text-gray-400 uppercase tracking-wider text-left">
                        <th class="px-4 py-3">Title / Keyword</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Search Volume</th>
                        <th class="px-4 py-3">Difficulty</th>
                        <th class="px-4 py-3">Added</th>
                        <?php if (($status ?? '') === 'needs_review'): ?>
                            <th class="px-4 py-3">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <?php foreach ($items as $item): ?>
                        <?php if (!is_array($item)) continue; ?>
                        <?php $tc = $typeColors[$item['content_type'] ?? ''] ?? 'blue'; ?>
                        <tr class="hover:bg-gray-800/40">
                            <?php if (($status ?? '') === 'needs_review'): ?>
                                <td class="px-4 py-2 w-8">
                                    <input type="checkbox" :checked="selected.includes(<?= (int)$item['id'] ?>)"
                                        @change="toggleSelect(<?= (int)$item['id'] ?>)" class="rounded border-gray-600 bg-gray-800">
                                </td>
                            <?php endif; ?>
                            <td class="px-4 py-3">
                                <p class="text-white font-medium line-clamp-1">
                                    <?= htmlspecialchars($item['title'] ?? '') ?>
                                </p>
                                <?php if (!empty($item['primary_keyword'])): ?>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($item['primary_keyword']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="text-xs bg-<?= $tc ?>-900/50 text-<?= $tc ?>-400 px-2 py-0.5 rounded-full capitalize">
                                    <?= htmlspecialchars($item['content_type'] ?? '—') ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-300 text-xs">
                                <?= $priorityLabels[(int) ($item['priority'] ?? 3)] ?? '—' ?>
                            </td>
                            <td class="px-4 py-3 text-gray-300 text-xs">
                                <?= isset($item['estimated_volume']) && $item['estimated_volume'] ? number_format((int) $item['estimated_volume']) : '—' ?>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <?php $diff = (int) ($item['keyword_difficulty'] ?? 0); ?>
                                <span
                                    class="<?= $diff >= 70 ? 'text-red-400' : ($diff >= 40 ? 'text-yellow-400' : 'text-green-400') ?>">
                                    <?= $diff ?: '—' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                <?= !empty($item['created_at']) ? date('M j', strtotime($item['created_at'])) : '—' ?>
                            </td>
                            <?php if (($status ?? '') === 'needs_review'): ?>
                                <td class="px-4 py-3">
                                    <div class="flex gap-1">
                                        <button @click.stop="singleAction('approve', <?= (int)$item['id'] ?>)"
                                            class="text-xs bg-green-800 text-green-300 px-2 py-1 rounded hover:bg-green-700">Approve</button>
                                        <button @click.stop="singleAction('reject', <?= (int)$item['id'] ?>)"
                                            class="text-xs bg-red-800 text-red-300 px-2 py-1 rounded hover:bg-red-700">Reject</button>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if (($status ?? '') === 'needs_review' && !empty($items)): ?>
        <!-- Bulk Actions -->
        <div x-show="selected.length > 0" x-transition class="flex items-center gap-3 rounded-xl border border-indigo-700 bg-indigo-900/30 px-4 py-3">
            <span class="text-sm text-indigo-300" x-text="selected.length + ' selected'"></span>
            <button @click="bulkAction('approve')" class="rounded-lg bg-green-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-600">Approve All</button>
            <button @click="bulkAction('reject')" class="rounded-lg bg-red-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-600">Reject All</button>
            <button @click="selected = []" class="rounded-lg bg-gray-700 px-3 py-1.5 text-xs font-medium text-gray-300 hover:bg-gray-600">Clear</button>
        </div>
    <?php endif; ?>

    <!-- Toast -->
    <div x-show="toast.show" x-transition
        class="fixed bottom-6 right-6 rounded-lg border border-gray-600 bg-gray-900 px-4 py-3 text-sm text-gray-100 shadow-xl z-50"
        :class="toast.type === 'error' ? 'border-red-600' : 'border-green-600'">
        <span x-text="toast.message"></span>
    </div>
</div>

<script>
function roadmapActions() {
    return {
        apiBase: "<?= url('/api/automation.php') ?>",
        selected: [],
        csrfToken: '',
        toast: { show: false, message: '', type: 'success' },

        async init() {
            try {
                const res = await fetch(this.apiBase + '?action=csrf_token');
                const json = await res.json();
                this.csrfToken = json.csrf_token || '';
            } catch(e) {}
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 2500);
        },

        toggleSelect(id) {
            const idx = this.selected.indexOf(id);
            if (idx === -1) { this.selected.push(id); } else { this.selected.splice(idx, 1); }
        },

        async singleAction(action, id) {
            await this.sendAction(action, [id]);
        },

        async bulkAction(action) {
            await this.sendAction(action, this.selected);
        },

        async sendAction(action, ids) {
            try {
                const res = await fetch(this.apiBase + '?action=roadmap_' + action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                    body: JSON.stringify({ ids, csrf_token: this.csrfToken }),
                });
                const json = await res.json();
                if (json.success) {
                    this.showToast(action === 'approve' ? 'Approved' : 'Rejected');
                    setTimeout(() => location.reload(), 600);
                } else {
                    this.showToast(json.error || 'Failed', 'error');
                }
            } catch (e) {
                this.showToast('Request failed', 'error');
            }
        },
    };
}
</script>