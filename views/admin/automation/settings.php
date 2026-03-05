<?php
$settings = $data['settings'] ?? [];
?>

<div x-data="automationSettings()" x-init="init()" class="space-y-5">
    <?php include VIEWS_PATH . '/admin/automation/_nav.php'; ?>

    <!-- AI Selection -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-white">AI Provider Selection</h2>
            <p class="text-xs text-gray-400 mt-0.5">Choose which AI providers to use for each automation task</p>
        </div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gray-400">Primary AI</label>
                <select x-model="form.automation_primary_ai" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                    <option value="gemini">Gemini</option>
                    <option value="claude">Claude</option>
                    <option value="openai">OpenAI</option>
                    <option value="deepseek">DeepSeek</option>
                    <option value="grok">Grok</option>
                    <option value="openrouter">OpenRouter</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400">Fallback AI</label>
                <select x-model="form.automation_fallback_ai" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                    <option value="gemini">Gemini</option>
                    <option value="claude">Claude</option>
                    <option value="openai">OpenAI</option>
                    <option value="deepseek">DeepSeek</option>
                    <option value="grok">Grok</option>
                    <option value="openrouter">OpenRouter</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400">Social AI</label>
                <select x-model="form.automation_social_ai" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                    <option value="gemini">Gemini</option>
                    <option value="claude">Claude</option>
                    <option value="openai">OpenAI</option>
                    <option value="deepseek">DeepSeek</option>
                    <option value="grok">Grok</option>
                    <option value="openrouter">OpenRouter</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Image Generation Provider -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-white">Image Generation</h2>
            <p class="text-xs text-gray-400 mt-0.5">Configure which AI provider generates featured images for articles</p>
        </div>
        <div class="p-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-xs text-gray-400">Image Provider</label>
                    <select x-model="form.image_provider" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                        <option value="auto">Auto (try all available)</option>
                        <option value="gemini">Gemini Only</option>
                        <option value="dalle">DALL-E 3 Only</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400">Image Style</label>
                    <select x-model="form.image_style" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                        <option value="professional">Professional / Clean</option>
                        <option value="vibrant">Vibrant / Colorful</option>
                        <option value="minimal">Minimal / Flat</option>
                        <option value="photorealistic">Photorealistic</option>
                        <option value="illustration">Illustration / Artistic</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400">Image Quality (DALL-E)</label>
                    <select x-model="form.image_quality" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white">
                        <option value="standard">Standard (~$0.04/img)</option>
                        <option value="hd">HD (~$0.08/img)</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.image_generation_enabled" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                    <div>
                        <span class="text-sm text-white">Enable Image Generation</span>
                        <p class="text-[10px] text-gray-500">Turn off to skip image generation for all articles</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.image_convert_webp" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                    <div>
                        <span class="text-sm text-white">Convert to WebP</span>
                        <p class="text-[10px] text-gray-500">Auto-convert generated images to WebP for faster loading</p>
                    </div>
                </div>
            </div>
            <div class="rounded-lg bg-gray-900 border border-gray-700 p-3">
                <p class="text-xs text-gray-400 mb-2"><strong class="text-gray-300">How it works:</strong></p>
                <ul class="text-[11px] text-gray-500 space-y-1 list-disc list-inside">
                    <li><strong class="text-gray-400">Gemini</strong> — Uses gemini-2.0-flash-exp for image generation (free tier available, uses your Gemini API key)</li>
                    <li><strong class="text-gray-400">DALL-E 3</strong> — OpenAI image generation ($0.04-$0.08/image, uses your OpenAI API key)</li>
                    <li><strong class="text-gray-400">Auto mode</strong> — Tries Gemini first, falls back to DALL-E if Gemini fails</li>
                    <li>Images are saved as WebP to <code class="text-gray-400">/uploads/images/</code> and linked to articles automatically</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- AI Provider API Keys -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden" x-data="{ open: true }">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div>
                <h2 class="text-lg font-semibold text-white">AI Provider API Keys</h2>
                <p class="text-xs text-gray-400 mt-0.5">Add API keys for each AI provider you want to use</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="open" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400">Gemini API Key</label>
                <input type="password" x-model="form.gemini_api_key" placeholder="AIza..."
                    class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Claude API Key</label>
                <input type="password" x-model="form.claude_api_key" placeholder="sk-ant-..."
                    class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">OpenAI API Key</label>
                <input type="password" x-model="form.openai_api_key" placeholder="sk-..."
                    class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">DeepSeek API Key</label>
                <input type="password" x-model="form.deepseek_api_key" placeholder="sk-..."
                    class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Grok API Key</label>
                <input type="password" x-model="form.grok_api_key" placeholder="xai-..."
                    class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">OpenRouter API Key</label>
                <input type="password" x-model="form.openrouter_api_key" placeholder="sk-or-..."
                    class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
        </div>
    </div>

    <!-- Social: Twitter/X -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden" x-data="{ open: false }">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div>
                <h2 class="text-base font-semibold text-white">Twitter / X</h2>
                <p class="text-xs text-gray-400 mt-0.5">OAuth 2.0 credentials for posting to Twitter</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="open" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400">Bearer Token</label>
                <input type="password" x-model="form.twitter_bearer_token" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">API Key</label>
                <input type="password" x-model="form.twitter_api_key" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">API Secret</label>
                <input type="password" x-model="form.twitter_api_secret" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Access Token</label>
                <input type="password" x-model="form.twitter_access_token" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Access Token Secret</label>
                <input type="password" x-model="form.twitter_access_secret" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
        </div>
    </div>

    <!-- Social: LinkedIn -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden" x-data="{ open: false }">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div>
                <h2 class="text-base font-semibold text-white">LinkedIn</h2>
                <p class="text-xs text-gray-400 mt-0.5">OAuth 2.0 access token for LinkedIn sharing</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="open" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400">Access Token</label>
                <input type="password" x-model="form.linkedin_access_token" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Person ID (URN)</label>
                <input type="text" x-model="form.linkedin_person_id" placeholder="urn:li:person:..." class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
        </div>
    </div>

    <!-- Social: Facebook -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden" x-data="{ open: false }">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div>
                <h2 class="text-base font-semibold text-white">Facebook Page</h2>
                <p class="text-xs text-gray-400 mt-0.5">Page ID and long-lived Page Access Token</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="open" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400">Page ID</label>
                <input type="text" x-model="form.facebook_page_id" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Page Access Token</label>
                <input type="password" x-model="form.facebook_page_token" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
        </div>
    </div>

    <!-- Social: Instagram -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden" x-data="{ open: false }">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div>
                <h2 class="text-base font-semibold text-white">Instagram Business</h2>
                <p class="text-xs text-gray-400 mt-0.5">Instagram Business Account ID and Access Token (via Facebook Graph API)</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="open" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400">Business Account ID</label>
                <input type="text" x-model="form.instagram_business_id" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Access Token</label>
                <input type="password" x-model="form.instagram_access_token" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
        </div>
    </div>

    <!-- Social: Pinterest -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden" x-data="{ open: false }">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div>
                <h2 class="text-base font-semibold text-white">Pinterest</h2>
                <p class="text-xs text-gray-400 mt-0.5">Pinterest API v5 access token and target board</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="open" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400">Access Token</label>
                <input type="password" x-model="form.pinterest_access_token" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Board ID</label>
                <input type="text" x-model="form.pinterest_board_id" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
        </div>
    </div>

    <!-- Social: YouTube -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden" x-data="{ open: false }">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div>
                <h2 class="text-base font-semibold text-white">YouTube Community</h2>
                <p class="text-xs text-gray-400 mt-0.5">YouTube Data API key and channel credentials</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="open" x-transition class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gray-400">YouTube API Key</label>
                <input type="password" x-model="form.youtube_api_key" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Channel ID</label>
                <input type="text" x-model="form.youtube_channel_id" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">OAuth Token</label>
                <input type="password" x-model="form.youtube_oauth_token" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
        </div>
    </div>

    <!-- Social: Threads -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden" x-data="{ open: false }">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div>
                <h2 class="text-base font-semibold text-white">Threads (Meta)</h2>
                <p class="text-xs text-gray-400 mt-0.5">Meta Threads API credentials</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="open" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400">Access Token</label>
                <input type="password" x-model="form.threads_access_token" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">User ID</label>
                <input type="text" x-model="form.threads_user_id" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
        </div>
    </div>

    <!-- Social: Bluesky -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden" x-data="{ open: false }">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div>
                <h2 class="text-base font-semibold text-white">Bluesky</h2>
                <p class="text-xs text-gray-400 mt-0.5">AT Protocol handle and app password</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="open" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400">Handle</label>
                <input type="text" x-model="form.bluesky_handle" placeholder="you.bsky.social" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">App Password</label>
                <input type="password" x-model="form.bluesky_app_password" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
        </div>
    </div>

    <!-- Social: Reddit -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden" x-data="{ open: false }">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div>
                <h2 class="text-base font-semibold text-white">Reddit</h2>
                <p class="text-xs text-gray-400 mt-0.5">Reddit API app credentials (for scraping)</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="open" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400">Client ID</label>
                <input type="text" x-model="form.reddit_client_id" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Client Secret</label>
                <input type="password" x-model="form.reddit_client_secret" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Username</label>
                <input type="text" x-model="form.reddit_username" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Password</label>
                <input type="password" x-model="form.reddit_password" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
        </div>
    </div>

    <!-- Telegram & Indexing -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden" x-data="{ open: true }">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div>
                <h2 class="text-lg font-semibold text-white">Telegram & Indexing</h2>
                <p class="text-xs text-gray-400 mt-0.5">Telegram notifications, Google Indexing API, IndexNow</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="open" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-400">Telegram Bot Token</label>
                <input type="password" x-model="form.telegram_bot_token" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div>
                <label class="text-xs text-gray-400">Telegram Chat ID</label>
                <input type="text" x-model="form.telegram_chat_id" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-gray-400">Google Indexing Service Key (JSON)</label>
                <textarea x-model="form.google_indexing_service_key" rows="3"
                    class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono"></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-gray-400">IndexNow Key</label>
                <input type="text" x-model="form.indexnow_key" class="mt-1 w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white font-mono">
            </div>
        </div>
    </div>

    <!-- Save Button (sticky) -->
    <div class="sticky bottom-0 bg-gray-900/80 backdrop-blur-sm border border-gray-700 rounded-xl px-4 py-3 flex items-center justify-between">
        <div class="text-xs text-gray-400">All API keys are stored encrypted in the database</div>
        <button @click="saveAll()" :disabled="saving"
            class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-60 flex items-center gap-2">
            <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <span x-show="!saving">Save All Settings</span>
            <span x-show="saving">Saving...</span>
        </button>
    </div>

    <div x-show="toast.show" x-transition
        class="fixed bottom-6 right-6 rounded-lg border border-gray-600 bg-gray-900 px-4 py-3 text-sm text-gray-100 shadow-xl z-50"
        :class="toast.type === 'error' ? 'border-red-600' : 'border-green-600'">
        <span x-text="toast.message"></span>
    </div>
</div>

<script>
function automationSettings() {
    return {
        apiBase: "<?= url('/api/automation.php') ?>",
        saving: false,
        csrfToken: '',
        form: <?= json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        toast: { show: false, message: '', type: 'success' },

        async init() {
            await this.loadCsrf();
            this.applyDefaults();
        },

        applyDefaults() {
            // Convert string booleans from server to actual booleans for checkboxes
            const boolKeys = ['image_generation_enabled', 'image_convert_webp'];
            for (const k of boolKeys) {
                if (typeof this.form[k] === 'string') {
                    this.form[k] = this.form[k] === 'true' || this.form[k] === '1';
                }
            }
            const defaults = {
                automation_primary_ai: 'gemini',
                automation_fallback_ai: 'claude',
                automation_social_ai: 'gemini',
                gemini_api_key: '', claude_api_key: '', openai_api_key: '',
                deepseek_api_key: '', grok_api_key: '', openrouter_api_key: '',
                image_provider: 'auto', image_style: 'professional', image_quality: 'standard',
                image_generation_enabled: true, image_convert_webp: true,
                twitter_bearer_token: '', twitter_api_key: '', twitter_api_secret: '',
                twitter_access_token: '', twitter_access_secret: '',
                linkedin_access_token: '', linkedin_person_id: '',
                facebook_page_id: '', facebook_page_token: '',
                instagram_business_id: '', instagram_access_token: '',
                pinterest_access_token: '', pinterest_board_id: '',
                youtube_api_key: '', youtube_channel_id: '', youtube_oauth_token: '',
                threads_access_token: '', threads_user_id: '',
                bluesky_handle: '', bluesky_app_password: '',
                reddit_client_id: '', reddit_client_secret: '', reddit_username: '', reddit_password: '',
                telegram_bot_token: '', telegram_chat_id: '',
                google_indexing_service_key: '', indexnow_key: '',
            };
            for (const [key, val] of Object.entries(defaults)) {
                if (this.form[key] === undefined || this.form[key] === null) {
                    this.form[key] = val;
                }
            }
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

        async updateSetting(key, value) {
            const res = await fetch(this.apiBase + '?action=update_setting', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken,
                },
                body: JSON.stringify({ key, value, csrf_token: this.csrfToken }),
            });
            return res.json();
        },

        async saveAll() {
            this.saving = true;
            try {
                for (const [key, value] of Object.entries(this.form)) {
                    const strVal = (typeof value === 'boolean') ? (value ? 'true' : 'false') : (value ?? '');
                    const json = await this.updateSetting(key, strVal);
                    if (!json.success) {
                        this.showToast(json.error || ('Failed to save: ' + key), 'error');
                        this.saving = false;
                        return;
                    }
                }
                this.showToast('Settings saved', 'success');
            } catch (error) {
                this.showToast('Save failed', 'error');
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>

