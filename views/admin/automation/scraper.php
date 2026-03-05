<?php
/**
 * Admin Scraper Configuration View
 * Variables: $data (array with settings)
 */
$settings = $data['settings'] ?? [];
$keywords = json_decode($settings['scraper_keywords'] ?? '[]', true) ?: [];
$ytQueries = json_decode($settings['scraper_youtube_queries'] ?? '[]', true) ?: [];
$rssFeeds = json_decode($settings['scraper_rss_feeds'] ?? '[]', true) ?: [];
$subreddits = json_decode($settings['scraper_reddit_subreddits'] ?? '[]', true) ?: [];
$ytEnabled = ($settings['scraper_source_youtube_enabled'] ?? '1') === '1';
$redditEnabled = ($settings['scraper_source_reddit_enabled'] ?? '1') === '1';
$rssEnabled = ($settings['scraper_source_rss_enabled'] ?? '1') === '1';
$redditMinScore = (int) ($settings['scraper_reddit_min_score'] ?? 20);
$timeWindow = (int) ($settings['scraper_time_window_hours'] ?? 12);
$ytMaxResults = (int) ($settings['scraper_youtube_max_results'] ?? 10);
$redditMaxResults = (int) ($settings['scraper_reddit_max_results'] ?? 50);
$autoApprove = (int) ($settings['scraper_auto_approve_threshold'] ?? 60);
$keywordStats = json_decode($settings['scraper_keyword_stats'] ?? '{}', true) ?: [];
arsort($keywordStats);
$topKeywords = array_slice($keywordStats, 0, 20, true);
?>

<div x-data="scraperConfig()" x-init="init()" class="space-y-5">
    <?php include VIEWS_PATH . '/admin/automation/_nav.php'; ?>

    <!-- Source Toggles -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 p-5">
        <h2 class="text-lg font-semibold text-white mb-4">Source Control</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="flex items-center justify-between rounded-lg border border-gray-700 bg-gray-900 p-4 cursor-pointer">
                <div>
                    <div class="text-sm font-medium text-white">YouTube</div>
                    <div class="text-xs text-gray-400">YouTube Data API v3</div>
                </div>
                <input type="checkbox" x-model="ytEnabled" @change="saveSetting('scraper_source_youtube_enabled', ytEnabled ? '1' : '0')"
                    class="rounded border-gray-600 bg-gray-800 text-indigo-500">
            </label>
            <label class="flex items-center justify-between rounded-lg border border-gray-700 bg-gray-900 p-4 cursor-pointer">
                <div>
                    <div class="text-sm font-medium text-white">Reddit</div>
                    <div class="text-xs text-gray-400">OAuth2 API</div>
                </div>
                <input type="checkbox" x-model="redditEnabled" @change="saveSetting('scraper_source_reddit_enabled', redditEnabled ? '1' : '0')"
                    class="rounded border-gray-600 bg-gray-800 text-indigo-500">
            </label>
            <label class="flex items-center justify-between rounded-lg border border-gray-700 bg-gray-900 p-4 cursor-pointer">
                <div>
                    <div class="text-sm font-medium text-white">RSS Feeds</div>
                    <div class="text-xs text-gray-400">Dev.to, HN, TechCrunch, etc.</div>
                </div>
                <input type="checkbox" x-model="rssEnabled" @change="saveSetting('scraper_source_rss_enabled', rssEnabled ? '1' : '0')"
                    class="rounded border-gray-600 bg-gray-800 text-indigo-500">
            </label>
        </div>
    </div>

    <!-- Filter Keywords -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 p-5">
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-lg font-semibold text-white">Filter Keywords</h2>
            <span class="text-xs bg-blue-900/50 text-blue-400 px-2 py-0.5 rounded-full" x-text="keywords.length + ' keywords'"></span>
        </div>
        <p class="text-xs text-gray-400 mb-4">Scraped content must contain at least one of these keywords to be saved. Used for YouTube, Reddit, and RSS filtering.</p>
        <div class="flex flex-wrap gap-2 mb-3 max-h-48 overflow-y-auto">
            <template x-for="(kw, i) in keywords" :key="i">
                <span class="inline-flex items-center gap-1 bg-blue-900/50 text-blue-300 px-2.5 py-1 rounded-lg text-xs">
                    <span x-text="kw"></span>
                    <button @click="removeTag('keywords', i)" class="text-blue-400 hover:text-red-400 ml-0.5">&times;</button>
                </span>
            </template>
        </div>
        <div class="flex gap-2 mb-3">
            <input type="text" x-model="newKeyword" @keydown.enter.prevent="addTag('keywords')" placeholder="Add keyword..."
                class="flex-1 rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            <button @click="addTag('keywords')" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Add</button>
        </div>
        <div class="flex items-center gap-2 pt-3 border-t border-gray-700">
            <label class="relative cursor-pointer rounded-lg bg-emerald-700 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-600">
                Upload CSV
                <input type="file" accept=".csv,.txt" @change="importCsv($event, 'keywords')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
            </label>
            <button @click="resetToDefaults('keywords')" class="rounded-lg bg-gray-700 px-3 py-2 text-xs font-medium text-gray-300 hover:bg-gray-600">Reset to Defaults</button>
            <button @click="if(confirm('Clear all keywords?')){keywords=[];saveTagArray('keywords')}" class="rounded-lg bg-red-800/60 px-3 py-2 text-xs font-medium text-red-300 hover:bg-red-700/60">Clear All</button>
            <span class="text-[10px] text-gray-500 ml-2">CSV: one keyword per line or comma-separated</span>
        </div>
    </div>

    <!-- Keyword Performance Ranking -->
    <?php if (!empty($topKeywords)): ?>
    <div class="rounded-xl border border-gray-700 bg-gray-800 p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-white">Keyword Performance</h2>
            <span class="text-xs text-gray-400"><?= count($keywordStats) ?> tracked keywords</span>
        </div>
        <p class="text-xs text-gray-400 mb-4">Top performing keywords by match count across all scraper runs. Keywords grow automatically as AI learns from scraped content.</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
            <?php $rank = 0; foreach ($topKeywords as $kw => $hits): $rank++; ?>
                <div class="flex items-center gap-2 rounded-lg border border-gray-700 bg-gray-900 px-3 py-2">
                    <span class="text-xs font-bold <?= $rank <= 3 ? 'text-yellow-400' : 'text-gray-500' ?>">#<?= $rank ?></span>
                    <span class="text-sm text-gray-200 flex-1 truncate"><?= htmlspecialchars((string) $kw) ?></span>
                    <span class="text-xs font-semibold text-emerald-400"><?= number_format($hits) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- YouTube Queries -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 p-5">
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-lg font-semibold text-white">YouTube Search Queries</h2>
            <span class="text-xs bg-red-900/50 text-red-400 px-2 py-0.5 rounded-full" x-text="ytQueries.length + ' queries'"></span>
        </div>
        <p class="text-xs text-gray-400 mb-4">Rotated one per scraper run. Each query fetches up to <span x-text="ytMaxResults"></span> results from last <span x-text="timeWindow"></span> hours.</p>
        <div class="space-y-2 mb-3 max-h-64 overflow-y-auto">
            <template x-for="(q, i) in ytQueries" :key="i">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 w-6" x-text="(i+1) + '.'"></span>
                    <span class="flex-1 text-sm text-gray-200 bg-gray-900 rounded-lg px-3 py-1.5 border border-gray-700" x-text="q"></span>
                    <button @click="removeTag('ytQueries', i)" class="text-red-400 hover:text-red-300 text-xs">Remove</button>
                </div>
            </template>
        </div>
        <div class="flex gap-2 mb-3">
            <input type="text" x-model="newYtQuery" @keydown.enter.prevent="addTag('ytQueries')" placeholder="Add YouTube query..."
                class="flex-1 rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            <button @click="addTag('ytQueries')" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Add</button>
        </div>
        <div class="flex items-center gap-2 pt-3 border-t border-gray-700">
            <label class="relative cursor-pointer rounded-lg bg-emerald-700 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-600">
                Upload CSV
                <input type="file" accept=".csv,.txt" @change="importCsv($event, 'ytQueries')" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
            </label>
            <button @click="resetToDefaults('ytQueries')" class="rounded-lg bg-gray-700 px-3 py-2 text-xs font-medium text-gray-300 hover:bg-gray-600">Reset to Defaults</button>
            <button @click="if(confirm('Clear all queries?')){ytQueries=[];saveTagArray('ytQueries')}" class="rounded-lg bg-red-800/60 px-3 py-2 text-xs font-medium text-red-300 hover:bg-red-700/60">Clear All</button>
            <span class="text-[10px] text-gray-500 ml-2">CSV: one query per line</span>
        </div>
    </div>

    <!-- RSS Feeds -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 p-5">
        <div class="flex items-center justify-between mb-1">
            <h2 class="text-lg font-semibold text-white">RSS Feeds</h2>
            <span class="text-xs bg-orange-900/50 text-orange-400 px-2 py-0.5 rounded-full" x-text="rssFeeds.length + ' feeds'"></span>
        </div>
        <p class="text-xs text-gray-400 mb-4">Add or remove RSS/Atom feed sources. Each feed is checked for new content within the time window.</p>

        <!-- Search & Controls -->
        <div class="flex flex-wrap gap-2 mb-3">
            <input type="text" x-model="rssSearch" placeholder="Search feeds..."
                class="flex-1 min-w-[200px] rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            <button @click="toggleAllFeeds(true)" class="rounded-lg bg-green-800/60 px-3 py-2 text-xs font-medium text-green-300 hover:bg-green-700/60">Enable All</button>
            <button @click="toggleAllFeeds(false)" class="rounded-lg bg-gray-700 px-3 py-2 text-xs font-medium text-gray-300 hover:bg-gray-600">Disable All</button>
            <button @click="if(confirm('Remove all RSS feeds?')){rssFeeds=[];saveRssFeeds()}" class="rounded-lg bg-red-800/60 px-3 py-2 text-xs font-medium text-red-300 hover:bg-red-700/60">Clear All</button>
        </div>

        <!-- Feeds Table -->
        <div class="overflow-x-auto mb-4 max-h-[400px] overflow-y-auto border border-gray-700 rounded-lg">
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-xs sticky top-0 bg-gray-800 z-10">
                    <tr>
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">URL</th>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left w-20">Enabled</th>
                        <th class="px-3 py-2 text-left w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <template x-for="(feed, i) in filteredRssFeeds()" :key="feed._idx">
                        <tr class="text-gray-200 hover:bg-gray-900/50">
                            <td class="px-3 py-2 text-xs" x-text="feed.name"></td>
                            <td class="px-3 py-2 text-xs text-blue-400 max-w-[300px] truncate">
                                <a :href="feed.url" target="_blank" x-text="feed.url" class="hover:text-blue-300"></a>
                            </td>
                            <td class="px-3 py-2 text-xs" x-text="feed.source_type"></td>
                            <td class="px-3 py-2">
                                <input type="checkbox" :checked="feed.is_enabled !== false" @change="rssFeeds[feed._idx].is_enabled = $event.target.checked; saveRssFeeds()"
                                    class="rounded border-gray-600 bg-gray-800 text-indigo-500">
                            </td>
                            <td class="px-3 py-2">
                                <button @click="removeFeed(feed._idx)" class="text-red-400 hover:text-red-300 text-xs">Remove</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Add Single Feed -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <input type="text" x-model="newFeed.name" placeholder="Feed name" class="rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            <input type="url" x-model="newFeed.url" placeholder="https://example.com/feed" class="rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            <input type="text" x-model="newFeed.source_type" placeholder="Source type (e.g. rss)" class="rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            <button @click="addFeed()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Add Feed</button>
        </div>

        <!-- Bulk Import -->
        <div class="border-t border-gray-700 pt-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-white">Bulk Import</h3>
                <button @click="showBulkImport = !showBulkImport" class="text-xs text-indigo-400 hover:text-indigo-300" x-text="showBulkImport ? 'Hide' : 'Show'"></button>
            </div>
            <div x-show="showBulkImport" x-transition class="space-y-3">
                <p class="text-xs text-gray-400">Paste RSS/Atom feed URLs below, one per line. Names will be auto-generated from domain names.</p>
                <textarea x-model="bulkRssUrls" rows="8" placeholder="https://techcrunch.com/feed/&#10;https://dev.to/feed&#10;https://news.ycombinator.com/rss&#10;..."
                    class="w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono"></textarea>
                <div class="flex items-center gap-2">
                    <button @click="importBulkRss()" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">Import URLs</button>
                    <label class="relative cursor-pointer rounded-lg bg-indigo-700 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600">
                        Upload CSV/TXT
                        <input type="file" accept=".csv,.txt" @change="importRssCsv($event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    </label>
                    <span class="text-[10px] text-gray-500">CSV/TXT: one URL per line, or comma-separated name,url per line</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Reddit Config -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 p-5">
        <h2 class="text-lg font-semibold text-white mb-1">Reddit Configuration</h2>
        <p class="text-xs text-gray-400 mb-4">Subreddits to monitor and filtering thresholds.</p>
        <div class="flex flex-wrap gap-2 mb-3">
            <template x-for="(sub, i) in subreddits" :key="i">
                <span class="inline-flex items-center gap-1 bg-orange-900/50 text-orange-300 px-2.5 py-1 rounded-lg text-xs">
                    r/<span x-text="sub"></span>
                    <button @click="removeTag('subreddits', i)" class="text-orange-400 hover:text-red-400 ml-0.5">&times;</button>
                </span>
            </template>
        </div>
        <div class="flex gap-2 mb-4">
            <input type="text" x-model="newSubreddit" @keydown.enter.prevent="addTag('subreddits')" placeholder="Add subreddit..."
                class="flex-1 rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            <button @click="addTag('subreddits')" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Add</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400">Min Score (upvotes)</label>
                <input type="number" x-model.number="redditMinScore" @change="saveSetting('scraper_reddit_min_score', String(redditMinScore))" min="1" max="1000"
                    class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="text-xs text-gray-400">Max Results per Run</label>
                <input type="number" x-model.number="redditMaxResults" @change="saveSetting('scraper_reddit_max_results', String(redditMaxResults))" min="10" max="200"
                    class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            </div>
        </div>
    </div>

    <!-- General Settings -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 p-5">
        <h2 class="text-lg font-semibold text-white mb-4">General Settings</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gray-400">Time Window (hours)</label>
                <p class="text-[10px] text-gray-500 mb-1">Only scrape content published within this many hours</p>
                <input type="number" x-model.number="timeWindow" @change="saveSetting('scraper_time_window_hours', String(timeWindow))" min="1" max="72"
                    class="w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="text-xs text-gray-400">YouTube Max Results</label>
                <p class="text-[10px] text-gray-500 mb-1">Max videos fetched per query per run</p>
                <input type="number" x-model.number="ytMaxResults" @change="saveSetting('scraper_youtube_max_results', String(ytMaxResults))" min="1" max="50"
                    class="w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="text-xs text-gray-400">Auto-Approve Threshold</label>
                <p class="text-[10px] text-gray-500 mb-1">Quality score above this auto-approves roadmap items (0 = disabled)</p>
                <input type="number" x-model.number="autoApprove" @change="saveSetting('scraper_auto_approve_threshold', String(autoApprove))" min="0" max="100"
                    class="w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div x-show="toast.show" x-transition
        class="fixed bottom-6 right-6 rounded-lg border border-gray-600 bg-gray-900 px-4 py-3 text-sm text-gray-100 shadow-xl z-50"
        :class="toast.type === 'error' ? 'border-red-600' : 'border-green-600'">
        <span x-text="toast.message"></span>
    </div>
</div>

<script>
function scraperConfig() {
    return {
        apiBase: "<?= url('/api/automation.php') ?>",
        csrfToken: '',
        toast: { show: false, message: '', type: 'success' },

        // Source toggles
        ytEnabled: <?= $ytEnabled ? 'true' : 'false' ?>,
        redditEnabled: <?= $redditEnabled ? 'true' : 'false' ?>,
        rssEnabled: <?= $rssEnabled ? 'true' : 'false' ?>,

        // Tag arrays
        keywords: <?= json_encode($keywords) ?>,
        ytQueries: <?= json_encode($ytQueries) ?>,
        subreddits: <?= json_encode($subreddits) ?>,
        rssFeeds: <?= json_encode($rssFeeds) ?>,

        // Input fields
        newKeyword: '',
        newYtQuery: '',
        newSubreddit: '',
        newFeed: { name: '', url: '', source_type: 'rss' },
        rssSearch: '',
        showBulkImport: false,
        bulkRssUrls: '',

        // Numeric settings
        redditMinScore: <?= $redditMinScore ?>,
        redditMaxResults: <?= $redditMaxResults ?>,
        timeWindow: <?= $timeWindow ?>,
        ytMaxResults: <?= $ytMaxResults ?>,
        autoApprove: <?= $autoApprove ?>,

        async init() {
            const res = await fetch(this.apiBase + '?action=csrf_token');
            const json = await res.json();
            this.csrfToken = json.csrf_token || '';
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 2500);
        },

        async saveSetting(key, value) {
            try {
                const res = await fetch(this.apiBase + '?action=update_setting', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrfToken },
                    body: JSON.stringify({ key, value, csrf_token: this.csrfToken }),
                });
                const json = await res.json();
                if (json.success) {
                    this.showToast('Saved');
                } else {
                    this.showToast(json.error || 'Save failed', 'error');
                }
            } catch (e) {
                this.showToast('Request failed', 'error');
            }
        },

        addTag(type) {
            let val = '';
            if (type === 'keywords') { val = this.newKeyword.trim().toLowerCase(); this.newKeyword = ''; }
            else if (type === 'ytQueries') { val = this.newYtQuery.trim(); this.newYtQuery = ''; }
            else if (type === 'subreddits') { val = this.newSubreddit.trim(); this.newSubreddit = ''; }
            if (!val) return;

            if (this[type].includes(val)) { this.showToast('Already exists', 'error'); return; }
            this[type].push(val);
            this.saveTagArray(type);
        },

        removeTag(type, index) {
            this[type].splice(index, 1);
            this.saveTagArray(type);
        },

        saveTagArray(type) {
            const keyMap = { keywords: 'scraper_keywords', ytQueries: 'scraper_youtube_queries', subreddits: 'scraper_reddit_subreddits' };
            this.saveSetting(keyMap[type], JSON.stringify(this[type]));
        },

        importCsv(event, type) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                const text = e.target.result;
                let items = [];
                if (text.includes('\n')) {
                    items = text.split('\n');
                } else {
                    items = text.split(',');
                }
                items = items.map(s => s.trim().toLowerCase()).filter(s => s.length > 0 && s.length < 200);
                const existing = new Set(this[type].map(k => k.toLowerCase()));
                let added = 0;
                items.forEach(item => {
                    if (!existing.has(item)) {
                        this[type].push(item);
                        existing.add(item);
                        added++;
                    }
                });
                this.saveTagArray(type);
                this.showToast(`Imported ${added} new items (${items.length - added} duplicates skipped)`);
                event.target.value = '';
            };
            reader.readAsText(file);
        },

        resetToDefaults(type) {
            if (!confirm('Reset to defaults? This will replace your current list.')) return;
            fetch(this.apiBase + '?action=get_defaults&type=' + type)
                .then(r => r.json())
                .then(json => {
                    if (json.success && json.data) {
                        this[type] = json.data;
                        this.saveTagArray(type);
                        this.showToast('Reset to defaults (' + json.data.length + ' items)');
                    } else {
                        this.showToast('Failed to load defaults', 'error');
                    }
                })
                .catch(() => this.showToast('Request failed', 'error'));
        },

        addFeed() {
            if (!this.newFeed.name || !this.newFeed.url) { this.showToast('Name and URL required', 'error'); return; }
            const exists = this.rssFeeds.some(f => f.url.replace(/\/$/, '') === this.newFeed.url.replace(/\/$/, ''));
            if (exists) { this.showToast('Feed URL already exists', 'error'); return; }
            this.rssFeeds.push({ name: this.newFeed.name, url: this.newFeed.url, source_type: this.newFeed.source_type || 'rss', is_enabled: true });
            this.newFeed = { name: '', url: '', source_type: 'rss' };
            this.saveRssFeeds();
        },

        removeFeed(index) {
            this.rssFeeds.splice(index, 1);
            this.saveRssFeeds();
        },

        saveRssFeeds() {
            this.saveSetting('scraper_rss_feeds', JSON.stringify(this.rssFeeds));
        },

        filteredRssFeeds() {
            const q = (this.rssSearch || '').toLowerCase().trim();
            return this.rssFeeds.map((f, i) => ({ ...f, _idx: i })).filter(f => {
                if (!q) return true;
                return (f.name || '').toLowerCase().includes(q) || (f.url || '').toLowerCase().includes(q) || (f.source_type || '').toLowerCase().includes(q);
            });
        },

        toggleAllFeeds(enabled) {
            this.rssFeeds.forEach(f => f.is_enabled = enabled);
            this.saveRssFeeds();
            this.showToast(enabled ? 'All feeds enabled' : 'All feeds disabled');
        },

        nameFromUrl(url) {
            try {
                const host = new URL(url).hostname.replace(/^www\./, '');
                const parts = host.split('.');
                let name = parts[0];
                if (name.length <= 2 && parts.length > 1) name = parts.slice(0, -1).join('.');
                return name.charAt(0).toUpperCase() + name.slice(1);
            } catch { return url.substring(0, 30); }
        },

        categoryFromUrl(url) {
            const u = url.toLowerCase();
            if (u.includes('security') || u.includes('krebs') || u.includes('schneier') || u.includes('threat') || u.includes('malware') || u.includes('bleeping') || u.includes('cyber') || u.includes('darkreading') || u.includes('hackread') || u.includes('sentinel') || u.includes('fireeye') || u.includes('kaspersky') || u.includes('sophos') || u.includes('welivesecurity') || u.includes('cisecurity') || u.includes('helpnetsecurity') || u.includes('mssp') || u.includes('tripwire') || u.includes('checkpoint') || u.includes('unit42') || u.includes('cloudflare') || u.includes('talosintelligence')) return 'security';
            if (u.includes('ai') || u.includes('ml') || u.includes('deepmind') || u.includes('openai') || u.includes('huggingface') || u.includes('tensorflow') || u.includes('pytorch') || u.includes('nvidia') || u.includes('machine-learning') || u.includes('kdnuggets') || u.includes('datasciencecentral') || u.includes('analyticsvidhya') || u.includes('unite.ai') || u.includes('synced') || u.includes('marktechpost') || u.includes('gradient')) return 'ai';
            if (u.includes('dev.to') || u.includes('github') || u.includes('gitlab') || u.includes('stackoverflow') || u.includes('freecodecamp') || u.includes('hackernoon') || u.includes('engineering') || u.includes('logrocket') || u.includes('baeldung') || u.includes('overreacted') || u.includes('kentcdodds') || u.includes('sitepoint') || u.includes('smashing') || u.includes('css-tricks') || u.includes('codinghorror') || u.includes('dzone') || u.includes('jetbrains') || u.includes('developers.google') || u.includes('developer.apple') || u.includes('devblogs.microsoft') || u.includes('medium.com/feed/tag')) return 'dev';
            if (u.includes('crypto') || u.includes('coin') || u.includes('bitcoin') || u.includes('decrypt') || u.includes('block') || u.includes('web3') || u.includes('fintech') || u.includes('finextra') || u.includes('payments')) return 'crypto-fintech';
            if (u.includes('startup') || u.includes('venture') || u.includes('sifted') || u.includes('betakit') || u.includes('alleywatch') || u.includes('angel') || u.includes('ycombinator') || u.includes('saastr') || u.includes('bothsides') || u.includes('tomtunguz') || u.includes('andrewchen') || u.includes('ben-evans') || u.includes('stratechery') || u.includes('avc.com')) return 'startup';
            return 'rss';
        },

        importBulkRss() {
            const text = (this.bulkRssUrls || '').trim();
            if (!text) { this.showToast('Paste some URLs first', 'error'); return; }
            const lines = text.split(/[\r\n]+/).map(s => s.trim()).filter(s => s && s.startsWith('http'));
            if (!lines.length) { this.showToast('No valid URLs found', 'error'); return; }
            const existingUrls = new Set(this.rssFeeds.map(f => f.url.replace(/\/$/, '').toLowerCase()));
            let added = 0, skipped = 0;
            lines.forEach(url => {
                const normalized = url.replace(/\/$/, '').toLowerCase();
                if (existingUrls.has(normalized)) { skipped++; return; }
                existingUrls.add(normalized);
                this.rssFeeds.push({ name: this.nameFromUrl(url), url: url, source_type: this.categoryFromUrl(url), is_enabled: true });
                added++;
            });
            this.saveRssFeeds();
            this.bulkRssUrls = '';
            this.showToast(`Imported ${added} feeds (${skipped} duplicates skipped)`);
        },

        importRssCsv(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                const text = e.target.result;
                const lines = text.split(/[\r\n]+/).map(s => s.trim()).filter(s => s.length > 0);
                const existingUrls = new Set(this.rssFeeds.map(f => f.url.replace(/\/$/, '').toLowerCase()));
                let added = 0;
                lines.forEach(line => {
                    let name = '', url = '';
                    if (line.includes(',')) {
                        const parts = line.split(',');
                        if (parts[1] && parts[1].trim().startsWith('http')) {
                            name = parts[0].trim();
                            url = parts[1].trim();
                        } else if (parts[0].trim().startsWith('http')) {
                            url = parts[0].trim();
                        }
                    } else if (line.startsWith('http')) {
                        url = line;
                    }
                    if (!url) return;
                    const normalized = url.replace(/\/$/, '').toLowerCase();
                    if (existingUrls.has(normalized)) return;
                    existingUrls.add(normalized);
                    if (!name) name = this.nameFromUrl(url);
                    this.rssFeeds.push({ name, url, source_type: this.categoryFromUrl(url), is_enabled: true });
                    added++;
                });
                this.saveRssFeeds();
                this.showToast(`Imported ${added} feeds from file`);
                event.target.value = '';
            };
            reader.readAsText(file);
        },
    };
}
</script>
