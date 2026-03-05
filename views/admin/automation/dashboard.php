<?php
$dashboard = $data ?? ['stats' => [], 'schedules' => [], 'recent_runs' => []];
$sourceHealth = $data['source_health'] ?? [];
$knowledgeStats = $data['knowledge_stats'] ?? [];
$roadmapCounts = $data['roadmap_counts'] ?? [];
?>

<div x-data="automationDashboard()" x-init="init()" class="space-y-5">
    <?php include VIEWS_PATH . '/admin/automation/_nav.php'; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Running</div>
            <div class="mt-2 text-2xl font-bold text-blue-400" x-text="stats.running || 0"></div>
        </div>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Completed</div>
            <div class="mt-2 text-2xl font-bold text-green-400" x-text="stats.completed || 0"></div>
        </div>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Failed</div>
            <div class="mt-2 text-2xl font-bold text-red-400" x-text="stats.failed || 0"></div>
        </div>
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Skipped</div>
            <div class="mt-2 text-2xl font-bold text-yellow-400" x-text="stats.skipped || 0"></div>
        </div>
    </div>

    <!-- Source Health + Pipeline Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Source Health -->
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <h3 class="text-sm font-semibold text-white mb-3">Scraper Source Health</h3>
            <?php if (!empty($sourceHealth['by_source'])): ?>
                <div class="space-y-2">
                    <?php foreach ($sourceHealth['by_source'] as $src): ?>
                        <?php
                        $sessions = (int) ($src['sessions'] ?? 0);
                        $successCount = (int) ($src['success_count'] ?? 0);
                        $rate = $sessions > 0 ? round(($successCount / $sessions) * 100) : 0;
                        $rateColor = $rate >= 80 ? 'green' : ($rate >= 50 ? 'yellow' : 'red');
                        ?>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-300 font-medium"><?= htmlspecialchars($src['source_type'] ?? '') ?></span>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-400"><?= number_format((int) ($src['total_saved'] ?? 0)) ?> saved</span>
                                <span class="text-<?= $rateColor ?>-400 font-semibold"><?= $rate ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-xs text-gray-500">No scraper data yet.</p>
            <?php endif; ?>
            <a href="<?= url('/admin/automation/scrape-logs') ?>" class="mt-3 block text-xs text-indigo-400 hover:text-indigo-300">View scrape logs &rarr;</a>
        </div>

        <!-- Knowledge Base Summary -->
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <h3 class="text-sm font-semibold text-white mb-3">Knowledge Base</h3>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-400">Total Items</span>
                    <span class="text-blue-400 font-semibold"><?= number_format($knowledgeStats['total'] ?? 0) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Unreviewed</span>
                    <span class="text-yellow-400 font-semibold"><?= number_format($knowledgeStats['unreviewed'] ?? 0) ?></span>
                </div>
                <?php foreach (($knowledgeStats['by_source'] ?? []) as $ks): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-500"><?= htmlspecialchars($ks['source_type'] ?? '') ?></span>
                        <span class="text-gray-300"><?= $ks['cnt'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="<?= url('/admin/automation/knowledge') ?>" class="mt-3 block text-xs text-indigo-400 hover:text-indigo-300">View knowledge base &rarr;</a>
        </div>

        <!-- Roadmap Pipeline -->
        <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
            <h3 class="text-sm font-semibold text-white mb-3">Roadmap Pipeline</h3>
            <div class="space-y-2 text-xs">
                <?php
                $statusColors = [
                    'needs_review' => 'yellow', 'pending' => 'blue', 'queued' => 'indigo',
                    'generating' => 'purple', 'published' => 'green', 'failed' => 'red', 'skipped' => 'gray',
                ];
                foreach ($statusColors as $s => $c): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-400"><?= ucfirst(str_replace('_', ' ', $s)) ?></span>
                        <span class="text-<?= $c ?>-400 font-semibold"><?= (int) ($roadmapCounts[$s] ?? 0) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="<?= url('/admin/roadmap') ?>?status=needs_review" class="mt-3 block text-xs text-indigo-400 hover:text-indigo-300">Review roadmap &rarr;</a>
        </div>
    </div>

    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-700 px-4 py-3">
            <h2 class="text-lg font-semibold text-white">Module Schedules</h2>
            <button @click="refreshDashboard()" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-500">
                Refresh
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
            <template x-for="(schedule, si) in schedules" :key="schedule.id">
                <div class="rounded-lg border border-gray-700 bg-gray-900 p-4"
                    :class="schedule.is_enabled == 1 ? '' : 'opacity-60'">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-100" x-text="schedule.display_name"></div>
                            <div class="text-xs text-gray-400 mt-0.5" x-text="schedule.module"></div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer"
                                :checked="schedule.is_enabled == 1"
                                @change="toggleSchedule(si, $event.target.checked)">
                            <div class="w-9 h-5 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500"></div>
                        </label>
                    </div>
                    <div class="mt-3 space-y-2 text-xs text-gray-400">
                        <div>
                            <label class="text-gray-500 block mb-1">Schedule (Cron)</label>
                            <div class="flex gap-1.5">
                                <input type="text" :value="schedule.cron_expression"
                                    @change="updateCron(si, $event.target.value)"
                                    class="flex-1 rounded border border-gray-600 bg-gray-800 px-2 py-1.5 text-xs text-white font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <button @click="showCronHelp = showCronHelp === si ? -1 : si"
                                    class="text-gray-500 hover:text-indigo-400 px-1" title="Cron presets">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                            </div>
                            <div x-show="showCronHelp === si" x-transition class="mt-1.5 grid grid-cols-2 gap-1">
                                <button @click="updateCron(si, '*/30 * * * *')" class="text-left rounded bg-gray-800 px-2 py-1 text-[10px] text-gray-300 hover:bg-gray-700">Every 30 min</button>
                                <button @click="updateCron(si, '0 */1 * * *')" class="text-left rounded bg-gray-800 px-2 py-1 text-[10px] text-gray-300 hover:bg-gray-700">Every 1 hour</button>
                                <button @click="updateCron(si, '0 */2 * * *')" class="text-left rounded bg-gray-800 px-2 py-1 text-[10px] text-gray-300 hover:bg-gray-700">Every 2 hours</button>
                                <button @click="updateCron(si, '0 */4 * * *')" class="text-left rounded bg-gray-800 px-2 py-1 text-[10px] text-gray-300 hover:bg-gray-700">Every 4 hours</button>
                                <button @click="updateCron(si, '0 */6 * * *')" class="text-left rounded bg-gray-800 px-2 py-1 text-[10px] text-gray-300 hover:bg-gray-700">Every 6 hours</button>
                                <button @click="updateCron(si, '0 */12 * * *')" class="text-left rounded bg-gray-800 px-2 py-1 text-[10px] text-gray-300 hover:bg-gray-700">Every 12 hours</button>
                                <button @click="updateCron(si, '0 0 * * *')" class="text-left rounded bg-gray-800 px-2 py-1 text-[10px] text-gray-300 hover:bg-gray-700">Once daily (midnight)</button>
                                <button @click="updateCron(si, '0 9 * * *')" class="text-left rounded bg-gray-800 px-2 py-1 text-[10px] text-gray-300 hover:bg-gray-700">Once daily (9 AM)</button>
                                <button @click="updateCron(si, '0 9,21 * * *')" class="text-left rounded bg-gray-800 px-2 py-1 text-[10px] text-gray-300 hover:bg-gray-700">Twice daily (9/21)</button>
                                <button @click="updateCron(si, '0 0 * * 1')" class="text-left rounded bg-gray-800 px-2 py-1 text-[10px] text-gray-300 hover:bg-gray-700">Weekly (Monday)</button>
                            </div>
                        </div>
                        <div class="flex justify-between"><span class="text-gray-500">Next Run:</span> <span x-text="schedule.next_run_at || 'Not scheduled'" class="text-gray-300"></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Last Run:</span> <span x-text="schedule.last_run_at || 'Never'" class="text-gray-300"></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Success Rate:</span> <span :class="parseFloat(schedule.success_rate) >= 80 ? 'text-green-400' : parseFloat(schedule.success_rate) >= 50 ? 'text-yellow-400' : 'text-red-400'" x-text="schedule.success_rate + '%'"></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Total Runs:</span> <span x-text="schedule.total_runs || 0" class="text-gray-300"></span></div>
                    </div>
                    <div class="mt-4">
                        <button
                            @click="runModule(schedule.module)"
                            :disabled="loadingModule === schedule.module"
                            class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="loadingModule !== schedule.module">Run Now</span>
                            <span x-show="loadingModule === schedule.module">Running...</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
        <div class="border-b border-gray-700 px-4 py-3">
            <h2 class="text-lg font-semibold text-white">Recent Execution History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-900 text-gray-300">
                    <tr>
                        <th class="px-4 py-2 text-left">ID</th>
                        <th class="px-4 py-2 text-left">Module</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Started</th>
                        <th class="px-4 py-2 text-left">Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <template x-for="run in recentRuns" :key="run.id">
                        <tr class="text-gray-200">
                            <td class="px-4 py-2" x-text="run.id"></td>
                            <td class="px-4 py-2" x-text="run.module"></td>
                            <td class="px-4 py-2">
                                <span class="rounded px-2 py-0.5 text-xs font-semibold"
                                    :class="{
                                        'bg-green-900 text-green-300': run.status === 'completed',
                                        'bg-red-900 text-red-300': run.status === 'failed',
                                        'bg-yellow-900 text-yellow-300': run.status === 'skipped',
                                        'bg-blue-900 text-blue-300': run.status === 'running'
                                    }"
                                    x-text="run.status"></span>
                            </td>
                            <td class="px-4 py-2" x-text="run.started_at"></td>
                            <td class="px-4 py-2" x-text="run.duration_seconds !== null ? run.duration_seconds + 's' : '-'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="toast.show" x-transition
        class="fixed bottom-6 right-6 rounded-lg border border-gray-600 bg-gray-900 px-4 py-3 text-sm text-gray-100 shadow-xl"
        :class="toast.type === 'error' ? 'border-red-600' : 'border-green-600'">
        <span x-text="toast.message"></span>
    </div>
</div>

<script>
function automationDashboard() {
    return {
        apiBase: "<?= url('/api/automation.php') ?>",
        stats: <?= json_encode($dashboard['stats'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        schedules: <?= json_encode($dashboard['schedules'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        recentRuns: <?= json_encode($dashboard['recent_runs'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        csrfToken: '',
        loadingModule: '',
        showCronHelp: -1,
        toast: { show: false, message: '', type: 'success' },

        async init() {
            await this.loadCsrf();
        },

        async loadCsrf() {
            const res = await fetch(this.apiBase + '?action=csrf_token');
            const json = await res.json();
            this.csrfToken = json.csrf_token || '';
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 2800);
        },

        async refreshDashboard() {
            const res = await fetch(this.apiBase + '?action=dashboard_data');
            const json = await res.json();
            if (!json.success) {
                this.showToast(json.error || 'Failed to refresh', 'error');
                return;
            }
            this.stats = json.data.stats || {};
            this.schedules = json.data.schedules || [];
            this.recentRuns = json.data.recent_runs || [];
        },

        async toggleSchedule(index, enabled) {
            const schedule = this.schedules[index];
            if (!schedule) return;
            try {
                const res = await fetch(this.apiBase + '?action=update_schedule', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                    body: JSON.stringify({ module: schedule.module, is_enabled: enabled ? 1 : 0, csrf_token: this.csrfToken }),
                });
                const json = await res.json();
                if (json.success) {
                    this.schedules[index].is_enabled = enabled ? 1 : 0;
                    this.showToast(schedule.display_name + (enabled ? ' enabled' : ' disabled'));
                } else {
                    this.showToast(json.error || 'Failed to update', 'error');
                }
            } catch (e) {
                this.showToast('Request failed', 'error');
            }
        },

        async updateCron(index, cronExpr) {
            const schedule = this.schedules[index];
            if (!schedule) return;
            cronExpr = cronExpr.trim();
            if (!cronExpr) { this.showToast('Cron expression cannot be empty', 'error'); return; }
            try {
                const res = await fetch(this.apiBase + '?action=update_schedule', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                    body: JSON.stringify({ module: schedule.module, cron_expression: cronExpr, csrf_token: this.csrfToken }),
                });
                const json = await res.json();
                if (json.success) {
                    this.schedules[index].cron_expression = cronExpr;
                    this.showCronHelp = -1;
                    this.showToast('Schedule updated: ' + cronExpr);
                    await this.refreshDashboard();
                } else {
                    this.showToast(json.error || 'Failed to update', 'error');
                }
            } catch (e) {
                this.showToast('Request failed', 'error');
            }
        },

        async runModule(module) {
            this.loadingModule = module;
            try {
                const res = await fetch(this.apiBase + '?action=run_module', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': this.csrfToken,
                    },
                    body: JSON.stringify({ module, csrf_token: this.csrfToken }),
                });
                const json = await res.json();
                if (!json.success) {
                    this.showToast(json.error || 'Run failed', 'error');
                } else {
                    this.showToast('Module triggered: ' + module, 'success');
                    await this.refreshDashboard();
                }
            } catch (error) {
                this.showToast('Request failed', 'error');
            } finally {
                this.loadingModule = '';
            }
        },
    };
}
</script>
