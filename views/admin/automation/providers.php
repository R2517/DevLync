<?php
$providers = $data['providers'] ?? [];
?>

<div x-data="automationProviders()" x-init="init()" class="space-y-5">
    <?php include VIEWS_PATH . '/admin/automation/_nav.php'; ?>

    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
        <div class="border-b border-gray-700 px-4 py-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white">AI Providers</h2>
            <button @click="refresh" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-500">Refresh</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-900 text-gray-300">
                    <tr>
                        <th class="px-4 py-2 text-left">Provider</th>
                        <th class="px-4 py-2 text-left">Model</th>
                        <th class="px-4 py-2 text-left">Priority</th>
                        <th class="px-4 py-2 text-left">Enabled</th>
                        <th class="px-4 py-2 text-left">Active</th>
                        <th class="px-4 py-2 text-left">Last Error</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <template x-for="provider in providers" :key="provider.id">
                        <tr>
                            <td class="px-4 py-2 text-gray-100">
                                <div class="font-semibold" x-text="provider.display_name"></div>
                                <div class="text-xs text-gray-500" x-text="provider.provider"></div>
                            </td>
                            <td class="px-4 py-2 text-gray-300" x-text="provider.model_id"></td>
                            <td class="px-4 py-2">
                                <input type="number" min="1" max="999" x-model.number="provider.priority"
                                    class="w-20 rounded border border-gray-600 bg-gray-900 px-2 py-1 text-gray-200">
                            </td>
                            <td class="px-4 py-2">
                                <input type="checkbox" x-model="provider.is_enabled" true-value="1" false-value="0">
                            </td>
                            <td class="px-4 py-2">
                                <input type="checkbox" x-model="provider.is_active" true-value="1" false-value="0">
                            </td>
                            <td class="px-4 py-2 max-w-xs">
                                <div class="truncate text-xs text-red-300" x-text="provider.last_error || '-'"></div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <button @click="saveProvider(provider)" class="rounded bg-indigo-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-indigo-500">Save</button>
                                    <button @click="testProvider(provider.id)" class="rounded bg-gray-700 px-2.5 py-1 text-xs font-medium text-gray-200 hover:bg-gray-600">Test</button>
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
function automationProviders() {
    return {
        apiBase: "<?= url('/api/automation.php') ?>",
        providers: <?= json_encode($providers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
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
            const res = await fetch(this.apiBase + '?action=get_providers');
            const json = await res.json();
            if (json.success) {
                this.providers = json.data || [];
            } else {
                this.showToast(json.error || 'Refresh failed', 'error');
            }
        },

        async saveProvider(provider) {
            const payload = {
                id: provider.id,
                priority: Number(provider.priority || 50),
                is_enabled: Number(provider.is_enabled || 0),
                is_active: Number(provider.is_active || 0),
                csrf_token: this.csrfToken,
            };
            const res = await fetch(this.apiBase + '?action=update_provider', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const json = await res.json();
            this.showToast(json.success ? 'Provider saved' : (json.error || 'Save failed'), json.success ? 'success' : 'error');
        },

        async testProvider(id) {
            const res = await fetch(this.apiBase + '?action=test_provider&id=' + encodeURIComponent(id));
            const json = await res.json();
            if (!json.success) {
                this.showToast(json.error || 'Provider test failed', 'error');
                return;
            }
            const success = json.data && json.data.success;
            this.showToast(success ? 'Provider test passed' : (json.data.error || 'Provider test failed'), success ? 'success' : 'error');
        },
    };
}
</script>

