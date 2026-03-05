<?php
$platforms = $data['platforms'] ?? [];
?>

<div x-data="automationSocial()" x-init="init()" class="space-y-5">
    <?php include VIEWS_PATH . '/admin/automation/_nav.php'; ?>

    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
        <div class="border-b border-gray-700 px-4 py-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white">Social Platforms</h2>
            <button @click="refresh" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-500">Refresh</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-900 text-gray-300">
                    <tr>
                        <th class="px-4 py-2 text-left">Platform</th>
                        <th class="px-4 py-2 text-left">Enabled</th>
                        <th class="px-4 py-2 text-left">Rate / Hour</th>
                        <th class="px-4 py-2 text-left">Rate / Day</th>
                        <th class="px-4 py-2 text-left">Posts Today</th>
                        <th class="px-4 py-2 text-left">Total Posts</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <template x-for="platform in platforms" :key="platform.platform">
                        <tr>
                            <td class="px-4 py-2 text-gray-100">
                                <div class="font-semibold" x-text="platform.display_name"></div>
                                <div class="text-xs text-gray-500" x-text="platform.platform"></div>
                            </td>
                            <td class="px-4 py-2">
                                <input type="checkbox" x-model="platform.is_enabled" true-value="1" false-value="0">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" min="1" x-model.number="platform.rate_limit_per_hour"
                                    class="w-20 rounded border border-gray-600 bg-gray-900 px-2 py-1 text-gray-200">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" min="1" x-model.number="platform.rate_limit_per_day"
                                    class="w-20 rounded border border-gray-600 bg-gray-900 px-2 py-1 text-gray-200">
                            </td>
                            <td class="px-4 py-2 text-gray-300" x-text="platform.posts_today"></td>
                            <td class="px-4 py-2 text-gray-300" x-text="platform.total_posts"></td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <button @click="savePlatform(platform)" class="rounded bg-indigo-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-indigo-500">Save</button>
                                    <button @click="testPlatform(platform.platform)" class="rounded bg-gray-700 px-2.5 py-1 text-xs font-medium text-gray-200 hover:bg-gray-600">Test</button>
                                </div>
                            </td>
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
function automationSocial() {
    return {
        apiBase: "<?= url('/api/automation.php') ?>",
        platforms: <?= json_encode($platforms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        csrfToken: '',
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
            setTimeout(() => { this.toast.show = false; }, 2600);
        },

        async refresh() {
            const res = await fetch(this.apiBase + '?action=get_platforms');
            const json = await res.json();
            if (json.success) {
                this.platforms = json.data || [];
            } else {
                this.showToast(json.error || 'Refresh failed', 'error');
            }
        },

        async savePlatform(platform) {
            const payload = {
                platform: platform.platform,
                is_enabled: Number(platform.is_enabled || 0),
                rate_limit_per_hour: Number(platform.rate_limit_per_hour || 10),
                rate_limit_per_day: Number(platform.rate_limit_per_day || 50),
                csrf_token: this.csrfToken,
            };
            const res = await fetch(this.apiBase + '?action=update_platform', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const json = await res.json();
            this.showToast(json.success ? 'Platform saved' : (json.error || 'Save failed'), json.success ? 'success' : 'error');
        },

        async testPlatform(platform) {
            const res = await fetch(this.apiBase + '?action=test_platform&platform=' + encodeURIComponent(platform));
            const json = await res.json();
            if (!json.success) {
                this.showToast(json.error || 'Platform test failed', 'error');
                return;
            }
            const success = json.data && json.data.success;
            this.showToast(success ? 'Platform config is valid' : (json.data.error || 'Platform test failed'), success ? 'success' : 'error');
        },
    };
}
</script>

