<!-- views/admin/supervisor/settings.php -->

<?php if ($data['saved'] ?? false): ?>
    <div class="bg-green-900/30 border border-green-700 text-green-400 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd" />
        </svg>
        Settings saved successfully!
    </div>
<?php endif; ?>

<form method="POST" action="<?= url('/admin/supervisor/settings') ?>" class="space-y-8">

    <!-- Back to Admin -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="<?= url('/admin/supervisor') ?>"
                class="text-gray-400 hover:text-white transition-colors flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Supervisor
            </a>
            <span class="text-gray-600">|</span>
            <a href="<?= url('/admin') ?>" class="text-gray-400 hover:text-white transition-colors flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Back to Admin
            </a>
        </div>
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors">
            Save Settings
        </button>
    </div>

    <!-- ============================== -->
    <!-- AI API KEYS — 3 PROVIDER CARDS -->
    <!-- ============================== -->
    <div>
        <h2 class="text-xl font-bold text-white mb-1 flex items-center gap-2">🤖 AI Providers</h2>
        <p class="text-sm text-gray-400 mb-4">Configure one or multiple AI providers. You can activate any combination.
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- ChatGPT (OpenAI) -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-green-600/10 rounded-bl-full"></div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">💬</span>
                        <div>
                            <h3 class="font-semibold text-white">ChatGPT</h3>
                            <p class="text-xs text-gray-500">OpenAI</p>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="sv_chatgpt_active" value="false">
                        <input type="checkbox" name="sv_chatgpt_active" value="true"
                            <?= ($data['settings']['chatgpt_active'] ?? '') === 'true' ? 'checked' : '' ?>
                            class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-green-500 focus:ring-green-500">
                        <span class="text-xs text-gray-400">Active</span>
                    </label>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">API Key</label>
                        <input type="password" name="sv_chatgpt_api_key"
                            value="<?= htmlspecialchars($data['settings']['chatgpt_api_key'] ?? '') ?>"
                            placeholder="sk-..."
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Model</label>
                        <select name="sv_chatgpt_model"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white focus:ring-2 focus:ring-green-500">
                            <?php
                            $chatgptModels = ['gpt-4o' => 'GPT-4o (Recommended)', 'gpt-4o-mini' => 'GPT-4o Mini (Fast)', 'gpt-4-turbo' => 'GPT-4 Turbo'];
                            foreach ($chatgptModels as $val => $label):
                                ?>
                                <option value="<?= $val ?>" <?= ($data['settings']['chatgpt_model'] ?? '') === $val ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="text-[10px] text-gray-500">Get key from <a href="https://platform.openai.com/api-keys"
                            target="_blank" class="text-green-400 hover:underline">platform.openai.com</a></p>
                </div>
            </div>

            <!-- Gemini (Google) -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-blue-600/10 rounded-bl-full"></div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">✨</span>
                        <div>
                            <h3 class="font-semibold text-white">Gemini</h3>
                            <p class="text-xs text-gray-500">Google AI</p>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="sv_gemini_active" value="false">
                        <input type="checkbox" name="sv_gemini_active" value="true"
                            <?= ($data['settings']['gemini_active'] ?? '') === 'true' ? 'checked' : '' ?>
                            class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-blue-500 focus:ring-blue-500">
                        <span class="text-xs text-gray-400">Active</span>
                    </label>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">API Key</label>
                        <input type="password" name="sv_gemini_api_key"
                            value="<?= htmlspecialchars($data['settings']['gemini_api_key'] ?? '') ?>"
                            placeholder="AIza..."
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Model</label>
                        <select name="sv_gemini_model"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white focus:ring-2 focus:ring-blue-500">
                            <?php
                            $geminiModels = ['gemini-2.5-flash' => 'Gemini 2.5 Flash (Recommended)', 'gemini-2.5-pro' => 'Gemini 2.5 Pro', 'gemini-2.0-flash' => 'Gemini 2.0 Flash'];
                            foreach ($geminiModels as $val => $label):
                                ?>
                                <option value="<?= $val ?>" <?= ($data['settings']['gemini_model'] ?? '') === $val ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="text-[10px] text-gray-500">Get key from <a href="https://aistudio.google.com/apikey"
                            target="_blank" class="text-blue-400 hover:underline">aistudio.google.com</a></p>
                </div>
            </div>

            <!-- Claude (Anthropic) -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-orange-600/10 rounded-bl-full"></div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">🧠</span>
                        <div>
                            <h3 class="font-semibold text-white">Claude</h3>
                            <p class="text-xs text-gray-500">Anthropic</p>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="sv_claude_active" value="false">
                        <input type="checkbox" name="sv_claude_active" value="true"
                            <?= ($data['settings']['claude_active'] ?? '') === 'true' ? 'checked' : '' ?>
                            class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-orange-500 focus:ring-orange-500">
                        <span class="text-xs text-gray-400">Active</span>
                    </label>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">API Key</label>
                        <input type="password" name="sv_claude_api_key"
                            value="<?= htmlspecialchars($data['settings']['claude_api_key'] ?? '') ?>"
                            placeholder="sk-ant-..."
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Model</label>
                        <select name="sv_claude_model"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white focus:ring-2 focus:ring-orange-500">
                            <?php
                            $claudeModels = ['claude-sonnet-4-5-20250929' => 'Claude Sonnet 4.5 (Recommended)', 'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (Fast)', 'claude-opus-4-6' => 'Claude Opus 4.6 (Intelligent)'];
                            foreach ($claudeModels as $val => $label):
                                ?>
                                <option value="<?= $val ?>" <?= ($data['settings']['claude_model'] ?? '') === $val ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="text-[10px] text-gray-500">Get key from <a
                            href="https://console.anthropic.com/settings/keys" target="_blank"
                            class="text-orange-400 hover:underline">console.anthropic.com</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- AI General Settings -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-5">
        <h3 class="font-semibold text-white mb-4">⚙️ AI General Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Max Daily AI Calls</label>
                <input type="number" name="sv_ai_max_daily_calls"
                    value="<?= htmlspecialchars($data['settings']['ai_max_daily_calls'] ?? '50') ?>"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white">
            </div>
            <div class="flex items-center gap-3 pt-5">
                <input type="hidden" name="sv_ai_analysis_enabled" value="false">
                <input type="checkbox" name="sv_ai_analysis_enabled" value="true"
                    <?= ($data['settings']['ai_analysis_enabled'] ?? '') === 'true' ? 'checked' : '' ?>
                    class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-indigo-500 focus:ring-indigo-500">
                <label class="text-sm text-gray-300">Enable AI-powered analysis</label>
            </div>
        </div>
    </div>

    <!-- Monitoring Settings -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-5">
        <h3 class="font-semibold text-white mb-4">📡 Monitoring Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Scan Frequency (minutes)</label>
                <input type="number" name="sv_scan_frequency_minutes"
                    value="<?= htmlspecialchars($data['settings']['scan_frequency_minutes'] ?? '30') ?>"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Warning Threshold (ms)</label>
                <input type="number" name="sv_response_time_warning_ms"
                    value="<?= htmlspecialchars($data['settings']['response_time_warning_ms'] ?? '1500') ?>"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Critical Threshold (ms)</label>
                <input type="number" name="sv_response_time_critical_ms"
                    value="<?= htmlspecialchars($data['settings']['response_time_critical_ms'] ?? '2500') ?>"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white">
            </div>
        </div>
        <div class="flex items-center gap-3 mt-4">
            <input type="hidden" name="sv_auto_mode" value="false">
            <input type="checkbox" name="sv_auto_mode" value="true" <?= ($data['settings']['auto_mode'] ?? '') === 'true' ? 'checked' : '' ?>
                class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-indigo-500 focus:ring-indigo-500">
            <label class="text-sm text-gray-300">Enable Auto Mode (automatic background monitoring)</label>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-5">
        <h3 class="font-semibold text-white mb-4">🔔 Notification Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Telegram Bot Token</label>
                <input type="password" name="sv_telegram_bot_token"
                    value="<?= htmlspecialchars($data['settings']['telegram_bot_token'] ?? '') ?>"
                    placeholder="123456:ABC-DEF..."
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Telegram Chat ID</label>
                <input type="text" name="sv_telegram_chat_id"
                    value="<?= htmlspecialchars($data['settings']['telegram_chat_id'] ?? '') ?>"
                    placeholder="-100123456789"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500">
            </div>
        </div>
        <div class="flex items-center gap-3 mt-4">
            <input type="hidden" name="sv_telegram_alerts_enabled" value="false">
            <input type="checkbox" name="sv_telegram_alerts_enabled" value="true"
                <?= ($data['settings']['telegram_alerts_enabled'] ?? '') === 'true' ? 'checked' : '' ?>
                class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-indigo-500 focus:ring-indigo-500">
            <label class="text-sm text-gray-300">Enable Telegram Alerts</label>
        </div>
    </div>

    <!-- Save -->
    <div class="flex justify-end">
        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-lg text-sm font-medium transition-colors">
            💾 Save All Settings
        </button>
    </div>
</form>