<?php
/**
 * Admin Knowledge Base View
 * Variables: $data (array with items, total, pages, page, stats, filters)
 */
$items = $data['items'] ?? [];
$total = $data['total'] ?? 0;
$pages = $data['pages'] ?? 1;
$page = $data['page'] ?? 1;
$stats = $data['stats'] ?? [];
$trends = $data['trends'] ?? [];
$filters = $data['filters'] ?? [];
$sourceIcons = [
    'youtube' => ['icon' => '&#9654;', 'color' => 'red'],
    'reddit' => ['icon' => '&#9679;', 'color' => 'orange'],
    'rss' => ['icon' => '&#9733;', 'color' => 'yellow'],
    'devto' => ['icon' => '&#9998;', 'color' => 'blue'],
    'hackernews' => ['icon' => 'Y', 'color' => 'orange'],
    'producthunt' => ['icon' => 'P', 'color' => 'orange'],
    'manual' => ['icon' => '&#9998;', 'color' => 'gray'],
];
$sentimentColors = [
    'positive' => 'green',
    'negative' => 'red',
    'neutral' => 'gray',
    'mixed' => 'yellow',
];
?>

<div x-data="knowledgeBase()" class="space-y-5">
    <?php include VIEWS_PATH . '/admin/automation/_nav.php'; ?>

    <!-- Stats Bar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Total Items</div>
            <div class="mt-2 text-2xl font-bold text-blue-400"><?= number_format($stats['total'] ?? 0) ?></div>
        </div>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Unreviewed</div>
            <div class="mt-2 text-2xl font-bold text-yellow-400"><?= number_format($stats['unreviewed'] ?? 0) ?></div>
        </div>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">By Source</div>
            <div class="mt-2 flex flex-wrap gap-1">
                <?php foreach (($stats['by_source'] ?? []) as $src): ?>
                    <?php $sc = $sourceIcons[$src['source_type']] ?? ['icon' => '?', 'color' => 'gray']; ?>
                    <span class="text-xs bg-<?= $sc['color'] ?>-900/50 text-<?= $sc['color'] ?>-400 px-2 py-0.5 rounded-full">
                        <?= htmlspecialchars($src['source_type']) ?>: <?= $src['cnt'] ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Sentiment</div>
            <div class="mt-2 flex flex-wrap gap-1">
                <?php foreach (($stats['by_sentiment'] ?? []) as $s): ?>
                    <?php $sc = $sentimentColors[$s['sentiment']] ?? 'gray'; ?>
                    <span class="text-xs bg-<?= $sc ?>-900/50 text-<?= $sc ?>-400 px-2 py-0.5 rounded-full">
                        <?= htmlspecialchars($s['sentiment'] ?? '?') ?>: <?= $s['cnt'] ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Trending Topics (last 48h) -->
    <?php if (!empty($trends)): ?>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <h2 class="text-sm font-semibold text-white mb-3">Trending Topics <span class="text-xs text-gray-400">(last 48h)</span></h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($trends as $trend): ?>
                    <?php $srcCount = count($trend['sources'] ?? []); ?>
                    <a href="<?= url('/admin/automation/knowledge') ?>?q=<?= urlencode($trend['topic']) ?>"
                        class="inline-flex items-center gap-1.5 bg-indigo-900/40 border border-indigo-700/50 text-indigo-300 px-3 py-1.5 rounded-lg text-xs hover:bg-indigo-800/50 transition-colors">
                        <span class="font-medium"><?= htmlspecialchars($trend['topic']) ?></span>
                        <span class="bg-indigo-700/60 text-indigo-200 px-1.5 py-0.5 rounded text-[10px] font-bold"><?= $trend['count'] ?></span>
                        <?php if ($srcCount > 1): ?>
                            <span class="text-[10px] text-indigo-400"><?= $srcCount ?> sources</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="get" action="<?= url('/admin/automation/knowledge') ?>" class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <label class="text-xs text-gray-400">Search</label>
                <input type="text" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Title, summary, source..."
                    class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="text-xs text-gray-400">Source Type</label>
                <select name="source_type" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                    <option value="">All Sources</option>
                    <?php foreach (['youtube', 'reddit', 'rss', 'devto', 'hackernews', 'producthunt', 'manual'] as $st): ?>
                        <option value="<?= $st ?>" <?= ($filters['source_type'] ?? '') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400">Sentiment</label>
                <select name="sentiment" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                    <option value="">All</option>
                    <?php foreach (['positive', 'negative', 'neutral', 'mixed'] as $sv): ?>
                        <option value="<?= $sv ?>" <?= ($filters['sentiment'] ?? '') === $sv ? 'selected' : '' ?>><?= ucfirst($sv) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400">Reviewed</label>
                <select name="reviewed" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                    <option value="">All</option>
                    <option value="0" <?= ($filters['reviewed'] ?? '') === '0' ? 'selected' : '' ?>>Unreviewed</option>
                    <option value="1" <?= ($filters['reviewed'] ?? '') === '1' ? 'selected' : '' ?>>Reviewed</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Filter</button>
                <a href="<?= url('/admin/automation/knowledge') ?>" class="rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-gray-200 hover:bg-gray-600">Reset</a>
            </div>
        </div>
    </form>

    <!-- Bulk Actions -->
    <div x-show="selected.length > 0" x-transition class="flex items-center gap-3 rounded-xl border border-indigo-700 bg-indigo-900/30 px-4 py-3">
        <span class="text-sm text-indigo-300" x-text="selected.length + ' selected'"></span>
        <button @click="bulkAction('review')" class="rounded-lg bg-green-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-600">Mark Reviewed</button>
        <button @click="bulkAction('delete')" class="rounded-lg bg-red-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-600">Delete</button>
        <button @click="selected = []" class="rounded-lg bg-gray-700 px-3 py-1.5 text-xs font-medium text-gray-300 hover:bg-gray-600">Clear</button>
    </div>

    <!-- Table -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-700 px-4 py-3">
            <h2 class="text-lg font-semibold text-white">Knowledge Items <span class="text-sm text-gray-400">(<?= number_format($total) ?> total)</span></h2>
        </div>
        <?php if (empty($items)): ?>
            <div class="text-center py-14 text-gray-500 text-sm">No knowledge items found.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-900 text-gray-300">
                        <tr>
                            <th class="px-3 py-2 text-left w-8">
                                <input type="checkbox" @change="toggleAll($event)" class="rounded border-gray-600 bg-gray-800">
                            </th>
                            <th class="px-3 py-2 text-left">Title</th>
                            <th class="px-3 py-2 text-left w-24">Source</th>
                            <th class="px-3 py-2 text-left w-20">Sentiment</th>
                            <th class="px-3 py-2 text-left w-16">Quality</th>
                            <th class="px-3 py-2 text-left w-20">Status</th>
                            <th class="px-3 py-2 text-left w-28">Date</th>
                            <th class="px-3 py-2 text-left w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $id = (int) $item['id'];
                            $sc = $sourceIcons[$item['source_type'] ?? ''] ?? ['icon' => '?', 'color' => 'gray'];
                            $sentColor = $sentimentColors[$item['sentiment'] ?? ''] ?? 'gray';
                            $qs = (int) ($item['quality_score'] ?? 50);
                            $qsColor = $qs >= 70 ? 'green' : ($qs >= 40 ? 'yellow' : 'red');
                            $topics = json_decode((string) ($item['topics'] ?? '[]'), true) ?: [];
                            ?>
                            <tr class="hover:bg-gray-800/40 cursor-pointer"
                                @click="if(!$event.target.closest('input,button,a')){toggleExpand(<?= $id ?>)}">
                                <td class="px-3 py-2">
                                    <input type="checkbox" :checked="selected.includes(<?= $id ?>)"
                                        @change="toggleSelect(<?= $id ?>)" class="rounded border-gray-600 bg-gray-800">
                                </td>
                                <td class="px-3 py-2">
                                    <p class="text-white font-medium line-clamp-1"><?= htmlspecialchars($item['title'] ?? '') ?></p>
                                    <p class="text-xs text-gray-500 line-clamp-1 mt-0.5"><?= htmlspecialchars(mb_substr($item['summary'] ?? '', 0, 120)) ?></p>
                                    <?php if ($topics): ?>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <?php foreach (array_slice($topics, 0, 3) as $topic): ?>
                                                <?php $topicStr = is_array($topic) ? (string) ($topic[0] ?? json_encode($topic)) : (string) $topic; ?>
                                                <span class="text-[10px] bg-gray-700 text-gray-300 px-1.5 py-0.5 rounded"><?= htmlspecialchars($topicStr) ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($topics) > 3): ?>
                                                <span class="text-[10px] text-gray-500">+<?= count($topics) - 3 ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs bg-<?= $sc['color'] ?>-900/50 text-<?= $sc['color'] ?>-400 px-2 py-0.5 rounded-full">
                                        <?= htmlspecialchars($item['source_type'] ?? '—') ?>
                                    </span>
                                    <?php if (!empty($item['source_name'])): ?>
                                        <p class="text-[10px] text-gray-500 mt-0.5"><?= htmlspecialchars($item['source_name']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs text-<?= $sentColor ?>-400"><?= htmlspecialchars($item['sentiment'] ?? '—') ?></span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs font-semibold text-<?= $qsColor ?>-400"><?= $qs ?></span>
                                </td>
                                <td class="px-3 py-2">
                                    <?php if ((int) ($item['is_reviewed'] ?? 0)): ?>
                                        <span class="text-xs bg-green-900/50 text-green-400 px-2 py-0.5 rounded-full">Reviewed</span>
                                    <?php else: ?>
                                        <span class="text-xs bg-yellow-900/50 text-yellow-400 px-2 py-0.5 rounded-full">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-400">
                                    <?= !empty($item['created_at']) ? date('M j, H:i', strtotime($item['created_at'])) : '—' ?>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex gap-1">
                                        <?php if (!empty($item['source_url'])): ?>
                                            <a href="<?= htmlspecialchars($item['source_url']) ?>" target="_blank" rel="noopener"
                                                class="text-xs text-blue-400 hover:text-blue-300" title="Open source">&#8599;</a>
                                        <?php endif; ?>
                                        <?php if (!(int) ($item['is_reviewed'] ?? 0)): ?>
                                            <button @click.stop="singleAction('review', <?= $id ?>)"
                                                class="text-xs text-green-400 hover:text-green-300" title="Mark reviewed">&#10003;</button>
                                        <?php endif; ?>
                                        <button @click.stop="singleAction('delete', <?= $id ?>)"
                                            class="text-xs text-red-400 hover:text-red-300" title="Delete">&#10005;</button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Expanded Detail Row -->
                            <tr x-show="expanded === <?= $id ?>" x-transition class="bg-gray-900/60">
                                <td colspan="8" class="px-4 py-4">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="text-xs font-semibold text-gray-400 uppercase mb-2">Full Content</h4>
                                            <div class="text-sm text-gray-200 max-h-48 overflow-y-auto rounded bg-gray-800 p-3 whitespace-pre-wrap"><?= htmlspecialchars(mb_substr($item['content'] ?? '', 0, 2000)) ?></div>
                                        </div>
                                        <div class="space-y-3">
                                            <?php if (!empty($item['source_url'])): ?>
                                                <div>
                                                    <span class="text-xs text-gray-400">Source URL:</span>
                                                    <a href="<?= htmlspecialchars($item['source_url']) ?>" target="_blank" rel="noopener"
                                                        class="text-xs text-blue-400 hover:underline ml-1"><?= htmlspecialchars(mb_substr($item['source_url'], 0, 80)) ?></a>
                                                </div>
                                            <?php endif; ?>
                                            <?php $kws = json_decode((string) ($item['keywords'] ?? '[]'), true) ?: []; ?>
                                            <?php if ($kws): ?>
                                                <div>
                                                    <span class="text-xs text-gray-400">Keywords:</span>
                                                    <div class="flex flex-wrap gap-1 mt-1">
                                                        <?php foreach ($kws as $kw): ?>
                                                            <?php $kwStr = is_array($kw) ? (string) ($kw[0] ?? json_encode($kw)) : (string) $kw; ?>
                                                            <span class="text-[10px] bg-blue-900/50 text-blue-300 px-1.5 py-0.5 rounded"><?= htmlspecialchars($kwStr) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php $ents = json_decode((string) ($item['entities'] ?? '[]'), true) ?: []; ?>
                                            <?php if ($ents): ?>
                                                <div>
                                                    <span class="text-xs text-gray-400">Entities:</span>
                                                    <div class="flex flex-wrap gap-1 mt-1">
                                                        <?php foreach ($ents as $ent): ?>
                                                            <?php $entStr = is_array($ent) ? (string) ($ent['name'] ?? $ent[0] ?? json_encode($ent)) : (string) $ent; ?>
                                                            <span class="text-[10px] bg-purple-900/50 text-purple-300 px-1.5 py-0.5 rounded"><?= htmlspecialchars($entStr) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($topics): ?>
                                                <div>
                                                    <span class="text-xs text-gray-400">Topics:</span>
                                                    <div class="flex flex-wrap gap-1 mt-1">
                                                        <?php foreach ($topics as $tp): ?>
                                                            <?php $tpStr = is_array($tp) ? (string) ($tp[0] ?? json_encode($tp)) : (string) $tp; ?>
                                                            <span class="text-[10px] bg-emerald-900/50 text-emerald-300 px-1.5 py-0.5 rounded"><?= htmlspecialchars($tpStr) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex items-center gap-4 text-xs text-gray-400">
                                                <span>Quality: <span class="font-semibold text-<?= $qsColor ?>-400"><?= $qs ?>/100</span></span>
                                                <span>Sentiment: <span class="text-<?= $sentColor ?>-400"><?= htmlspecialchars($item['sentiment'] ?? '—') ?></span></span>
                                                <span>Source ID: <?= htmlspecialchars($item['source_id'] ?? '—') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
        <div class="flex items-center justify-center gap-2">
            <?php
            $queryParams = $filters;
            unset($queryParams['page']);
            $baseUrl = url('/admin/automation/knowledge') . '?' . http_build_query(array_filter($queryParams));
            ?>
            <?php if ($page > 1): ?>
                <a href="<?= $baseUrl ?>&page=<?= $page - 1 ?>" class="rounded-lg bg-gray-800 px-3 py-1.5 text-sm text-gray-300 hover:bg-gray-700">&laquo; Prev</a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($pages, $page + 2);
            ?>
            <?php for ($p = $start; $p <= $end; $p++): ?>
                <a href="<?= $baseUrl ?>&page=<?= $p ?>"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium <?= $p === $page ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
                <a href="<?= $baseUrl ?>&page=<?= $page + 1 ?>" class="rounded-lg bg-gray-800 px-3 py-1.5 text-sm text-gray-300 hover:bg-gray-700">Next &raquo;</a>
            <?php endif; ?>
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
function knowledgeBase() {
    return {
        apiBase: "<?= url('/api/automation.php') ?>",
        selected: [],
        expanded: null,
        csrfToken: '',
        toast: { show: false, message: '', type: 'success' },

        async init() {
            const res = await fetch(this.apiBase + '?action=csrf_token');
            const json = await res.json();
            this.csrfToken = json.csrf_token || '';
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 2800);
        },

        toggleAll(event) {
            if (event.target.checked) {
                this.selected = <?= json_encode(array_map(fn($i) => (int) $i['id'], $items)) ?>;
            } else {
                this.selected = [];
            }
        },

        toggleSelect(id) {
            const idx = this.selected.indexOf(id);
            if (idx === -1) { this.selected.push(id); } else { this.selected.splice(idx, 1); }
        },

        toggleExpand(id) {
            this.expanded = this.expanded === id ? null : id;
        },

        async singleAction(action, id) {
            if (action === 'delete' && !confirm('Delete this knowledge item?')) return;
            await this.sendAction(action, [id]);
        },

        async bulkAction(action) {
            if (action === 'delete' && !confirm('Delete ' + this.selected.length + ' items?')) return;
            await this.sendAction(action, this.selected);
        },

        async sendAction(action, ids) {
            try {
                const res = await fetch(this.apiBase + '?action=knowledge_' + action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                    body: JSON.stringify({ ids, csrf_token: this.csrfToken }),
                });
                const json = await res.json();
                if (json.success) {
                    this.showToast((action === 'delete' ? 'Deleted' : 'Reviewed') + ' ' + ids.length + ' item(s)');
                    setTimeout(() => location.reload(), 800);
                } else {
                    this.showToast(json.error || 'Action failed', 'error');
                }
            } catch (e) {
                this.showToast('Request failed', 'error');
            }
        },
    };
}
</script>
