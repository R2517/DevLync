<?php
/**
 * Admin Settings View
 * Variables: $all (array key => value), $flashMessage (string|null)
 */
$settings = $all; // $all is already ['key' => 'value'] from Setting::getAll()
$val = fn(string $key, string $default = '') => htmlspecialchars($settings[$key] ?? $default);
?>
<div class="max-w-2xl space-y-6">

    <?php if (!empty($flashMessage)): ?>
        <div id="flash-msg" class="bg-green-900/40 border border-green-700 text-green-300 rounded-xl p-3 text-sm">
            ✅
            <?= htmlspecialchars($flashMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/admin/settings') ?>" class="space-y-5">

        <!-- Site Identity -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h2 class="font-semibold text-white text-sm mb-4">🌐 Site Identity</h2>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Site Name</label>
                    <input type="text" name="site_name" value="<?= $val('site_name', 'DevLync') ?>"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- n8n Integration -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h2 class="font-semibold text-white text-sm mb-4">⚙️ API & n8n Integration</h2>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">API Key (X-API-Key for n8n)</label>
                    <input type="text" name="api_key" value="<?= $val('api_key') ?>"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500"
                        placeholder="Generate a secure random string">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">n8n Webhook Base URL</label>
                    <input type="url" name="n8n_webhook_url" value="<?= $val('n8n_webhook_url') ?>"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                        placeholder="https://your-n8n.example.com">
                </div>
            </div>
        </div>

        <!-- AI API Keys -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h2 class="font-semibold text-white text-sm mb-4">🤖 AI API Keys</h2>
            <p class="text-xs text-gray-500 mb-3">These are stored in the database. For production, prefer environment
                variables.</p>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Google Gemini API Key</label>
                    <input type="password" name="gemini_api_key" value="<?= $val('gemini_api_key') ?>"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">fal.ai API Key (Image Generation)</label>
                    <input type="password" name="fal_api_key" value="<?= $val('fal_api_key') ?>"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">YouTube Data API Key</label>
                    <input type="password" name="youtube_api_key" value="<?= $val('youtube_api_key') ?>"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Telegram Alerts -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h2 class="font-semibold text-white text-sm mb-4">📲 Telegram Alerts</h2>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Bot Token</label>
                    <input type="password" name="telegram_bot_token" value="<?= $val('telegram_bot_token') ?>"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Chat ID</label>
                    <input type="text" name="telegram_chat_id" value="<?= $val('telegram_chat_id') ?>"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <button type="submit" id="save-settings-btn"
            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl transition-colors">
            Save Settings
        </button>
    </form>
</div>