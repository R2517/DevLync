<?php
/**
 * Admin Competitor Monitoring View
 * Variables: $data (array with tab, items/feeds, total, pages, page, stats, filters)
 */
$tab = $data['tab'] ?? 'sites';
$stats = $data['stats'] ?? [];
$total = $data['total'] ?? 0;
$pages = $data['pages'] ?? 1;
$page = $data['page'] ?? 1;
$filters = $data['filters'] ?? [];

$statusColors = [
    'pending' => 'gray',
    'discovered' => 'green',
    'no_feed' => 'yellow',
    'error' => 'red',
];
?>

<div x-data="competitorManager()" class="space-y-5">
    <?php include VIEWS_PATH . '/admin/automation/_nav.php'; ?>

    <!-- Stats Bar -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Total Sites</div>
            <div class="mt-2 text-2xl font-bold text-blue-400"><?= number_format($stats['total'] ?? 0) ?></div>
        </div>
        <?php foreach (($stats['by_status'] ?? []) as $s): ?>
            <?php $sc = $statusColors[$s['rss_status']] ?? 'gray'; ?>
            <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
                <div class="text-xs text-gray-400 uppercase tracking-wider"><?= ucfirst($s['rss_status'] ?? '') ?></div>
                <div class="mt-2 text-2xl font-bold text-<?= $sc ?>-400"><?= number_format((int) $s['cnt']) ?></div>
            </div>
        <?php endforeach; ?>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Active Feeds</div>
            <div class="mt-2 text-2xl font-bold text-green-400"><?= number_format($stats['active_feeds'] ?? 0) ?> <span class="text-sm text-gray-500">/ <?= $stats['total_feeds'] ?? 0 ?></span></div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <button @click="importCsv()" :disabled="loading"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">
            <span x-show="!loading">Import CSV (5035 sites)</span>
            <span x-show="loading" x-cloak>Processing...</span>
        </button>
        <div class="flex items-center gap-1">
            <button @click="discoverFeeds()" :disabled="loading"
                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-50">
                <span x-show="!loading" x-cloak>Discover RSS Feeds</span>
                <span x-show="loading" x-cloak>Discovering...</span>
            </button>
            <select x-model="batchSize" class="rounded-lg border border-gray-600 bg-gray-900 px-2 py-2 text-sm text-white">
                <option value="50">50/batch</option>
                <option value="100">100/batch</option>
                <option value="200">200/batch</option>
            </select>
        </div>
        <button @click="resetPending()" :disabled="loading"
            class="rounded-lg bg-yellow-600 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-500 disabled:opacity-50">
            Reset Failed &rarr; Pending
        </button>

        <!-- Tab Toggle -->
        <div class="ml-auto flex rounded-lg border border-gray-700 overflow-hidden">
            <a href="<?= url('/admin/automation/competitors') ?>?tab=sites"
                class="px-4 py-2 text-sm font-medium <?= $tab === 'sites' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' ?>">
                Sites
            </a>
            <a href="<?= url('/admin/automation/competitors') ?>?tab=feeds"
                class="px-4 py-2 text-sm font-medium <?= $tab === 'feeds' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' ?>">
                Discovered Feeds
            </a>
        </div>
    </div>

    <!-- Status Message -->
    <div x-show="message" x-cloak x-transition
        class="rounded-lg border px-4 py-3 text-sm"
        :class="messageType === 'success' ? 'border-green-700 bg-green-900/40 text-green-300' : 'border-red-700 bg-red-900/40 text-red-300'">
        <span x-text="message"></span>
    </div>

    <?php if ($tab === 'sites'): ?>
        <!-- Sites Tab -->
        <!-- Filters -->
        <form method="get" action="<?= url('/admin/automation/competitors') ?>" class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <input type="hidden" name="tab" value="sites">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="text-xs text-gray-400">Search Domain</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="e.g. huggingface.co"
                        class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="text-xs text-gray-400">RSS Status</label>
                    <select name="rss_status" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                        <option value="">All Statuses</option>
                        <?php foreach (['pending', 'discovered', 'no_feed', 'error'] as $st): ?>
                            <option value="<?= $st ?>" <?= ($filters['rss_status'] ?? '') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Filter</button>
                    <a href="<?= url('/admin/automation/competitors') ?>?tab=sites" class="ml-2 rounded-lg bg-gray-700 px-4 py-2 text-sm text-gray-300 hover:bg-gray-600">Clear</a>
                </div>
            </div>
        </form>

        <div class="text-xs text-gray-400"><?= number_format($total) ?> sites found &middot; Page <?= $page ?>/<?= $pages ?></div>

        <!-- Sites Table -->
        <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-900/50 text-gray-400 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3">Domain</th>
                            <th class="px-4 py-3 text-right">Traffic</th>
                            <th class="px-4 py-3 text-right">SE Keywords</th>
                            <th class="px-4 py-3 text-right">Comp. Level</th>
                            <th class="px-4 py-3 text-center">RSS Status</th>
                            <th class="px-4 py-3 text-center">Feeds</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach (($data['items'] ?? []) as $site): ?>
                            <?php $sc = $statusColors[$site['rss_status'] ?? 'pending'] ?? 'gray'; ?>
                            <tr class="hover:bg-gray-700/30">
                                <td class="px-4 py-3">
                                    <a href="https://<?= htmlspecialchars($site['domain']) ?>" target="_blank" rel="noopener" class="text-indigo-400 hover:text-indigo-300">
                                        <?= htmlspecialchars($site['domain']) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-300"><?= number_format((int) $site['traffic']) ?></td>
                                <td class="px-4 py-3 text-right text-gray-300"><?= number_format((int) $site['se_keywords']) ?></td>
                                <td class="px-4 py-3 text-right text-gray-400"><?= $site['competition_level'] ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-<?= $sc ?>-900/50 text-<?= $sc ?>-400">
                                        <?= $site['rss_status'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ((int) ($site['feed_count'] ?? 0) > 0): ?>
                                        <span class="text-green-400 font-medium"><?= $site['active_feeds'] ?>/<?= $site['feed_count'] ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($data['items'])): ?>
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No competitor sites imported yet. Click "Import CSV" to get started.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- Feeds Tab -->
        <!-- Filters -->
        <form method="get" action="<?= url('/admin/automation/competitors') ?>" class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <input type="hidden" name="tab" value="feeds">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="text-xs text-gray-400">Search</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Domain, feed URL..."
                        class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="text-xs text-gray-400">Status</label>
                    <select name="enabled" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                        <option value="">All Feeds</option>
                        <option value="1" <?= ($filters['enabled'] ?? '') === '1' ? 'selected' : '' ?>>Enabled Only</option>
                        <option value="0" <?= ($filters['enabled'] ?? '') === '0' ? 'selected' : '' ?>>Disabled Only</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Filter</button>
                    <a href="<?= url('/admin/automation/competitors') ?>?tab=feeds" class="rounded-lg bg-gray-700 px-4 py-2 text-sm text-gray-300 hover:bg-gray-600">Clear</a>
                </div>
            </div>
        </form>

        <!-- Bulk Actions -->
        <div class="flex items-center gap-3" x-show="selectedFeeds.length > 0" x-cloak>
            <span class="text-sm text-gray-400" x-text="selectedFeeds.length + ' selected'"></span>
            <button @click="bulkEnableFeeds()" class="rounded-lg bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-500">Enable Selected</button>
            <button @click="bulkDisableFeeds()" class="rounded-lg bg-red-600 px-3 py-1.5 text-sm text-white hover:bg-red-500">Disable Selected</button>
        </div>

        <div class="text-xs text-gray-400"><?= number_format($total) ?> feeds found &middot; Page <?= $page ?>/<?= $pages ?></div>

        <!-- Feeds Table -->
        <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-900/50 text-gray-400 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 w-8">
                                <input type="checkbox" @change="toggleAllFeeds($event)" class="rounded border-gray-600">
                            </th>
                            <th class="px-4 py-3">Domain</th>
                            <th class="px-4 py-3">Feed</th>
                            <th class="px-4 py-3 text-right">Traffic</th>
                            <th class="px-4 py-3 text-center">Type</th>
                            <th class="px-4 py-3 text-right">Items</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach (($data['feeds'] ?? []) as $feed): ?>
                            <tr class="hover:bg-gray-700/30">
                                <td class="px-4 py-3">
                                    <input type="checkbox" value="<?= (int) $feed['id'] ?>"
                                        @change="toggleFeed(<?= (int) $feed['id'] ?>)" class="rounded border-gray-600 feed-checkbox">
                                </td>
                                <td class="px-4 py-3">
                                    <a href="https://<?= htmlspecialchars($feed['domain'] ?? '') ?>" target="_blank" rel="noopener" class="text-indigo-400 hover:text-indigo-300 text-xs">
                                        <?= htmlspecialchars($feed['domain'] ?? '') ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-xs text-gray-300 truncate max-w-xs" title="<?= htmlspecialchars($feed['feed_url'] ?? '') ?>">
                                        <?= htmlspecialchars($feed['feed_title'] ?? '') ?>
                                    </div>
                                    <div class="text-[10px] text-gray-500 truncate max-w-xs"><?= htmlspecialchars($feed['feed_url'] ?? '') ?></div>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-400 text-xs"><?= number_format((int) ($feed['traffic'] ?? 0)) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs text-gray-400"><?= $feed['feed_type'] ?? 'rss' ?></span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-300"><?= number_format((int) ($feed['items_found'] ?? 0)) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ((int) ($feed['is_enabled'] ?? 0)): ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-900/50 text-green-400">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-700 text-gray-400">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ((int) ($feed['is_enabled'] ?? 0)): ?>
                                        <button @click="toggleFeedStatus(<?= (int) $feed['id'] ?>, false)"
                                            class="text-xs text-red-400 hover:text-red-300">Disable</button>
                                    <?php else: ?>
                                        <button @click="toggleFeedStatus(<?= (int) $feed['id'] ?>, true)"
                                            class="text-xs text-green-400 hover:text-green-300">Enable</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($data['feeds'])): ?>
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No feeds discovered yet. Import sites first, then click "Discover RSS Feeds".</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
        <div class="flex justify-center gap-1">
            <?php for ($p = max(1, $page - 3); $p <= min($pages, $page + 3); $p++): ?>
                <?php
                $params = array_merge($filters, ['tab' => $tab, 'page' => $p]);
                $qs = http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));
                ?>
                <a href="<?= url('/admin/automation/competitors') ?>?<?= $qs ?>"
                    class="px-3 py-1.5 rounded text-sm <?= $p === $page ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <!-- Top Traffic Competitors -->
    <?php if ($tab === 'sites' && !empty($stats['top_traffic'])): ?>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <h3 class="text-sm font-semibold text-white mb-3">Top 10 by Traffic</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                <?php foreach ($stats['top_traffic'] as $top): ?>
                    <?php $tc = $statusColors[$top['rss_status'] ?? 'pending'] ?? 'gray'; ?>
                    <div class="rounded-lg bg-gray-900 p-2 text-xs">
                        <div class="text-indigo-400 truncate font-medium"><?= htmlspecialchars($top['domain']) ?></div>
                        <div class="text-gray-400 mt-0.5"><?= number_format((int) $top['traffic']) ?> traffic</div>
                        <span class="text-<?= $tc ?>-400"><?= $top['rss_status'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function competitorManager() {
    return {
        loading: false,
        message: '',
        messageType: 'success',
        selectedFeeds: [],
        batchSize: '50',
        apiBase: '<?= url('/api/automation') ?>',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

        showMsg(msg, type = 'success') {
            this.message = msg;
            this.messageType = type;
            setTimeout(() => { this.message = ''; }, 5000);
        },

        async apiCall(action, data = {}) {
            this.loading = true;
            try {
                const res = await fetch(this.apiBase + '?action=' + action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                    body: JSON.stringify({ ...data, csrf_token: this.csrfToken }),
                });
                const json = await res.json();
                return json;
            } catch (e) {
                return { success: false, error: e.message };
            } finally {
                this.loading = false;
            }
        },

        async importCsv() {
            const json = await this.apiCall('competitor_import_csv');
            if (json.success) {
                const d = json.data || {};
                this.showMsg(`Imported: ${d.imported}, Skipped: ${d.skipped}, Errors: ${d.errors}`);
                setTimeout(() => location.reload(), 1500);
            } else {
                this.showMsg(json.error || 'Import failed', 'error');
            }
        },

        async discoverFeeds() {
            this.showMsg(`Running RSS discovery on next ${this.batchSize} pending sites...`, 'success');
            const json = await this.apiCall('competitor_discover_feeds', { batch_size: parseInt(this.batchSize) });
            if (json.success) {
                const d = json.data || {};
                this.showMsg(`Checked: ${d.checked}, Discovered: ${d.discovered}, No feed: ${d.no_feed}, Errors: ${d.errors}`);
                setTimeout(() => location.reload(), 2000);
            } else {
                this.showMsg(json.error || 'Discovery failed', 'error');
            }
        },

        async resetPending() {
            const json = await this.apiCall('competitor_reset_pending');
            if (json.success) {
                this.showMsg(`Reset ${json.reset} sites back to pending`);
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showMsg(json.error || 'Reset failed', 'error');
            }
        },

        async toggleFeedStatus(feedId, enabled) {
            const json = await this.apiCall('competitor_feed_toggle', { feed_id: feedId, enabled: enabled });
            if (json.success) {
                location.reload();
            } else {
                this.showMsg(json.error || 'Toggle failed', 'error');
            }
        },

        toggleFeed(id) {
            const idx = this.selectedFeeds.indexOf(id);
            if (idx > -1) { this.selectedFeeds.splice(idx, 1); }
            else { this.selectedFeeds.push(id); }
        },

        toggleAllFeeds(event) {
            if (event.target.checked) {
                this.selectedFeeds = [...document.querySelectorAll('.feed-checkbox')].map(c => parseInt(c.value));
            } else {
                this.selectedFeeds = [];
            }
            document.querySelectorAll('.feed-checkbox').forEach(c => c.checked = event.target.checked);
        },

        async bulkEnableFeeds() {
            const json = await this.apiCall('competitor_feed_bulk_enable', { ids: this.selectedFeeds });
            if (json.success) {
                this.showMsg(`Enabled ${json.enabled} feeds`);
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showMsg(json.error || 'Bulk enable failed', 'error');
            }
        },

        async bulkDisableFeeds() {
            const json = await this.apiCall('competitor_feed_bulk_disable', { ids: this.selectedFeeds });
            if (json.success) {
                this.showMsg(`Disabled ${json.disabled} feeds`);
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showMsg(json.error || 'Bulk disable failed', 'error');
            }
        },
    };
}
</script>
