<!-- views/admin/supervisor/dashboard.php -->

<div x-data="supervisorDashboard()" x-init="init()" class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                🧠 AI Supervisor
            </h1>
            <p class="text-gray-400 text-sm mt-1">Website monitoring, error detection & AI-powered improvements</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Auto/Manual Toggle -->
            <div class="flex items-center gap-2 bg-gray-800 rounded-lg px-4 py-2">
                <span class="text-sm text-gray-400">Mode:</span>
                <button @click="toggleAutoMode()" :class="autoMode ? 'bg-green-600' : 'bg-gray-600'"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                    <span :class="autoMode ? 'translate-x-6' : 'translate-x-1'"
                        class="inline-block h-4 w-4 rounded-full bg-white transition-transform"></span>
                </button>
                <span x-text="autoMode ? 'Auto' : 'Manual'" class="text-sm font-medium"
                    :class="autoMode ? 'text-green-400' : 'text-gray-400'"></span>
            </div>

            <!-- Run Full Audit Button -->
            <button @click="runFullAudit()" :disabled="scanning"
                class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-800 disabled:cursor-wait text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <svg x-show="!scanning" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg x-show="scanning" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span x-text="scanning ? 'Running Full Audit...' : 'Run Full Audit'"></span>
            </button>

            <!-- Settings Gear -->
            <a href="<?= url('/admin/supervisor/settings') ?>"
                class="p-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-gray-400 hover:text-white transition-colors"
                title="Supervisor Settings">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Website Score + Quick Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Website Score (large) -->
        <div class="col-span-2 md:col-span-1 bg-gray-800 rounded-xl p-5 border border-gray-700 text-center">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Website Score</div>
            <div class="text-4xl font-bold" :class="{
                    'text-green-400': websiteScore >= 80,
                    'text-yellow-400': websiteScore >= 60 && websiteScore < 80,
                    'text-orange-400': websiteScore >= 40 && websiteScore < 60,
                    'text-red-400': websiteScore < 40
                 }" x-text="websiteScore + '/100'">
            </div>
            <div class="mt-2 h-2 bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-1000" :class="{
                        'bg-green-500': websiteScore >= 80,
                        'bg-yellow-500': websiteScore >= 60 && websiteScore < 80,
                        'bg-orange-500': websiteScore >= 40 && websiteScore < 60,
                        'bg-red-500': websiteScore < 40
                     }" :style="'width: ' + websiteScore + '%'">
                </div>
            </div>
        </div>

        <!-- System Status Cards -->
        <template x-for="card in statusCards" :key="card.label">
            <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-gray-400 uppercase tracking-wider" x-text="card.label"></span>
                    <span class="w-3 h-3 rounded-full" :class="card.color"></span>
                </div>
                <div class="text-xl font-bold text-white" x-text="card.value"></div>
                <div class="text-xs text-gray-500 mt-1" x-text="card.detail"></div>
            </div>
        </template>
    </div>

    <!-- Active Incidents Banner -->
    <template x-if="incidents.length > 0">
        <div class="bg-red-900/30 border border-red-700 rounded-xl p-4">
            <div class="flex items-center gap-2 text-red-400 font-semibold mb-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" />
                </svg>
                Active Incidents
            </div>
            <template x-for="incident in incidents" :key="incident.id">
                <div class="text-sm text-red-300" x-text="'⚠️ ' + incident.title + ' — ' + incident.description"></div>
            </template>
        </div>
    </template>

    <!-- Live Status Board -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                Live Status Board
            </h2>
            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500"></span>
                    Working</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                    Slow</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span> Down</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-gray-500"></span>
                    Unchecked</span>
            </div>
        </div>

        <!-- Status Groups -->
        <template x-for="group in pageGroups" :key="group.type">
            <div class="border-b border-gray-700 last:border-b-0">
                <div class="px-5 py-2 bg-gray-900/50 text-xs font-semibold text-gray-400 uppercase tracking-wider"
                    x-text="group.label"></div>
                <div class="divide-y divide-gray-700/50">
                    <template x-for="page in group.pages" :key="page.id">
                        <div
                            class="px-5 py-2.5 flex items-center justify-between hover:bg-gray-700/30 transition-colors">
                            <div class="flex items-center gap-3">
                                <!-- Status dot -->
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :class="{
                                        'bg-green-500': page.is_functional == 1 && page.last_response_time_ms < 1500,
                                        'bg-yellow-500': page.is_functional == 1 && page.last_response_time_ms >= 1500 && page.last_response_time_ms < 2500,
                                        'bg-red-500': page.is_functional == 0 && page.last_check_at,
                                        'bg-gray-500': !page.last_check_at
                                    }"></span>
                                <span class="text-sm text-gray-200" x-text="page.page_name"></span>
                                <span class="text-xs text-gray-500" x-text="page.url_pattern"></span>
                            </div>
                            <div class="flex items-center gap-4">
                                <!-- Response time -->
                                <span class="text-xs font-mono" :class="{
                                        'text-green-400': page.last_response_time_ms > 0 && page.last_response_time_ms < 1000,
                                        'text-yellow-400': page.last_response_time_ms >= 1000 && page.last_response_time_ms < 2000,
                                        'text-red-400': page.last_response_time_ms >= 2000,
                                        'text-gray-500': !page.last_response_time_ms
                                    }"
                                    x-text="page.last_response_time_ms ? page.last_response_time_ms + 'ms' : '—'"></span>
                                <!-- Status code -->
                                <span class="text-xs px-1.5 py-0.5 rounded font-mono" :class="{
                                        'bg-green-900/40 text-green-400': page.last_status_code >= 200 && page.last_status_code < 300,
                                        'bg-yellow-900/40 text-yellow-400': page.last_status_code >= 300 && page.last_status_code < 400,
                                        'bg-red-900/40 text-red-400': page.last_status_code >= 400,
                                        'bg-gray-700 text-gray-500': !page.last_status_code
                                    }" x-text="page.last_status_code || '—'"></span>
                                <!-- Last checked -->
                                <span class="text-xs text-gray-500 w-20 text-right"
                                    x-text="page.last_check_at ? timeAgo(page.last_check_at) : 'Never'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Two Column: Errors + Suggestions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Errors -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white">Recent Errors</h2>
                <a href="<?= url('/admin/supervisor/errors') ?>" class="text-xs text-indigo-400 hover:text-indigo-300">View All →</a>
            </div>
            <div class="divide-y divide-gray-700/50 max-h-96 overflow-y-auto">
                <template x-if="errors.length === 0">
                    <div class="px-5 py-8 text-center text-gray-500">
                        <div class="text-3xl mb-2">✨</div>
                        <div class="text-sm">No new errors! Looking good.</div>
                    </div>
                </template>
                <template x-for="error in errors" :key="error.id">
                    <div class="px-5 py-3 hover:bg-gray-700/30 transition-colors">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase flex-shrink-0"
                                :class="{
                                    'bg-red-900/50 text-red-400': error.severity === 'critical',
                                    'bg-yellow-900/50 text-yellow-400': error.severity === 'warning',
                                    'bg-blue-900/50 text-blue-400': error.severity === 'info',
                                    'bg-gray-700 text-gray-400': error.severity === 'optimization'
                                }" x-text="error.severity"></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-gray-200 truncate" x-text="error.message"></div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <span x-text="error.error_type"></span>
                                    <span x-show="error.file_path"> · <span x-text="error.file_path"></span></span>
                                    <span x-show="error.occurrence_count > 1"> · <span
                                            x-text="error.occurrence_count + 'x'"></span></span>
                                </div>
                            </div>
                            <div class="flex gap-1 flex-shrink-0">
                                <button @click="aiAnalyzeError(error)" :disabled="aiAnalyzing"
                                    class="p-1 text-gray-500 hover:text-purple-400 transition-colors"
                                    title="AI Analyze">
                                    <span class="text-sm">🤖</span>
                                </button>
                                <button @click="updateErrorStatus(error.id, 'acknowledged')"
                                    class="p-1 text-gray-500 hover:text-blue-400 transition-colors" title="Acknowledge">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button @click="updateErrorStatus(error.id, 'resolved')"
                                    class="p-1 text-gray-500 hover:text-green-400 transition-colors" title="Resolve">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- AI Suggestions -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white">AI Suggestions</h2>
                <a href="<?= url('/admin/supervisor/suggestions') ?>" class="text-xs text-indigo-400 hover:text-indigo-300">View All
                    →</a>
            </div>
            <div class="divide-y divide-gray-700/50 max-h-96 overflow-y-auto">
                <template x-if="suggestions.length === 0">
                    <div class="px-5 py-8 text-center text-gray-500">
                        <div class="text-3xl mb-2">🎯</div>
                        <div class="text-sm">Run a scan to get AI-powered suggestions.</div>
                    </div>
                </template>
                <template x-for="suggestion in suggestions" :key="suggestion.id">
                    <div class="px-5 py-3 hover:bg-gray-700/30 transition-colors">
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-900/50 text-indigo-400 flex-shrink-0"
                                x-text="suggestion.category"></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-gray-200" x-text="suggestion.title"></div>
                                <div class="text-xs text-gray-500 mt-1 flex items-center gap-3">
                                    <span>Impact: <span class="text-indigo-400"
                                            x-text="suggestion.impact_score + '/100'"></span></span>
                                    <span>Effort: <span x-text="suggestion.effort_score + '/100'"></span></span>
                                    <span x-show="suggestion.estimated_time"
                                        x-text="'⏱ ' + suggestion.estimated_time"></span>
                                </div>
                            </div>
                            <div class="flex gap-1 flex-shrink-0">
                                <button @click="updateSuggestionStatus(suggestion.id, 'approved')"
                                    class="p-1 text-gray-500 hover:text-green-400 transition-colors" title="Approve">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button @click="updateSuggestionStatus(suggestion.id, 'rejected')"
                                    class="p-1 text-gray-500 hover:text-red-400 transition-colors" title="Reject">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Deep Scan Actions -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-5">
        <h2 class="text-lg font-semibold text-white mb-4">Deep Scan Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <button @click="runScan('health')" :disabled="scanning"
                class="flex flex-col items-center gap-2 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 rounded-lg p-4 text-sm transition-colors">
                <span class="text-2xl">🏥</span>
                <span class="text-gray-200">Health Check</span>
                <span class="text-[10px] text-green-400">Active</span>
            </button>
            <button @click="runScan('seo')" :disabled="scanning"
                class="flex flex-col items-center gap-2 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 rounded-lg p-4 text-sm transition-colors">
                <span class="text-2xl">🔎</span>
                <span class="text-gray-200">SEO Audit</span>
                <span class="text-[10px] text-green-400">Active</span>
            </button>
            <button @click="runScan('performance')" :disabled="scanning"
                class="flex flex-col items-center gap-2 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 rounded-lg p-4 text-sm transition-colors">
                <span class="text-2xl">⚡</span>
                <span class="text-gray-200">Performance</span>
                <span class="text-[10px] text-green-400">Active</span>
            </button>
            <button @click="runScan('links')" :disabled="scanning"
                class="flex flex-col items-center gap-2 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 rounded-lg p-4 text-sm transition-colors">
                <span class="text-2xl">🔗</span>
                <span class="text-gray-200">Link Checker</span>
                <span class="text-[10px] text-green-400">Active</span>
            </button>
            <button @click="runScan('images')" :disabled="scanning"
                class="flex flex-col items-center gap-2 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 rounded-lg p-4 text-sm transition-colors">
                <span class="text-2xl">🖼️</span>
                <span class="text-gray-200">Image Audit</span>
                <span class="text-[10px] text-green-400">Active</span>
            </button>
            <button @click="runScan('security')" :disabled="scanning"
                class="flex flex-col items-center gap-2 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 rounded-lg p-4 text-sm transition-colors">
                <span class="text-2xl">🛡️</span>
                <span class="text-gray-200">Security</span>
                <span class="text-[10px] text-green-400">Active</span>
            </button>
            <button @click="runScan('content_quality')" :disabled="scanning"
                class="flex flex-col items-center gap-2 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 rounded-lg p-4 text-sm transition-colors">
                <span class="text-2xl">📝</span>
                <span class="text-gray-200">Content</span>
                <span class="text-[10px] text-green-400">Active</span>
            </button>
        </div>
    </div>

    <!-- Recent Activity Log -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white">Activity Log</h2>
            <a href="<?= url('/admin/supervisor/activity') ?>" class="text-xs text-indigo-400 hover:text-indigo-300">View All →</a>
        </div>
        <div class="divide-y divide-gray-700/50 max-h-64 overflow-y-auto">
            <template x-if="activityLog.length === 0">
                <div class="px-5 py-6 text-center text-gray-500 text-sm">No activity yet. Run a scan to get started.
                </div>
            </template>
            <template x-for="activity in activityLog" :key="activity.id">
                <div class="px-5 py-2.5 flex items-center gap-3 text-sm">
                    <span class="text-xs text-gray-500 w-20 flex-shrink-0" x-text="timeAgo(activity.created_at)"></span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] uppercase flex-shrink-0" :class="{
                            'bg-green-900/40 text-green-400': activity.triggered_by === 'auto',
                            'bg-blue-900/40 text-blue-400': activity.triggered_by === 'manual',
                            'bg-purple-900/40 text-purple-400': activity.triggered_by === 'ai',
                            'bg-gray-700 text-gray-400': activity.triggered_by === 'scheduled' || activity.triggered_by === 'cron'
                        }" x-text="activity.triggered_by"></span>
                    <span class="text-gray-300 truncate" x-text="activity.action_description"></span>
                </div>
            </template>
        </div>
    </div>

    <!-- Scan Results Modal -->
    <div id="scanResultsModal" x-show="showScanResults" x-cloak style="display:none" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
        @click.self="showScanResults = false" @keydown.escape.window="showScanResults = false">
        <div class="bg-gray-800 rounded-2xl max-w-4xl w-full max-h-[85vh] overflow-y-auto border border-gray-700">
            <div
                class="px-6 py-4 border-b border-gray-700 flex items-center justify-between sticky top-0 bg-gray-800 z-10">
                <h3 class="text-lg font-semibold text-white" x-text="scanResultsTitle">Scan Results</h3>
                <div class="flex items-center gap-2">
                    <button @click="copyReport()"
                        class="flex items-center gap-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        title="Copy report to clipboard">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        Copy Report
                    </button>
                    <button id="closeScanModal" @click="showScanResults = false" class="text-gray-400 hover:text-white text-xl">&times;</button>
                </div>
            </div>
            <div class="p-6">
                <!-- Overall Stats -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-3 bg-green-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-green-400" x-text="scanResults.passed || 0"></div>
                        <div class="text-xs text-green-400/70">Passed</div>
                    </div>
                    <div class="text-center p-3 bg-yellow-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-400" x-text="scanResults.warnings || 0"></div>
                        <div class="text-xs text-yellow-400/70">Warnings</div>
                    </div>
                    <div class="text-center p-3 bg-red-900/20 rounded-lg">
                        <div class="text-2xl font-bold text-red-400" x-text="scanResults.failed || 0"></div>
                        <div class="text-xs text-red-400/70">Failed</div>
                    </div>
                </div>

                <!-- Full Audit: overall score + duration -->
                <template x-if="scanResults.overall_score !== undefined">
                    <div class="mb-6 p-4 bg-gray-900/50 rounded-xl border border-gray-700 flex items-center justify-between">
                        <div>
                            <div class="text-xs text-gray-400 uppercase tracking-wider">Overall Score</div>
                            <div class="text-3xl font-bold mt-1" :class="{
                                'text-green-400': scanResults.overall_score >= 80,
                                'text-yellow-400': scanResults.overall_score >= 50 && scanResults.overall_score < 80,
                                'text-red-400': scanResults.overall_score < 50
                            }" x-text="scanResults.overall_score + '/100'"></div>
                        </div>
                        <div class="text-right text-sm text-gray-400" x-show="scanResults.duration_seconds">
                            Completed in <span class="text-white font-mono" x-text="scanResults.duration_seconds + 's'"></span>
                        </div>
                    </div>
                </template>

                <!-- Full Audit: Section per scan type -->
                <template x-if="isFullAudit()">
                    <div class="space-y-4">
                        <template x-for="section in getAuditSections()" :key="section.key">
                            <div class="border border-gray-700 rounded-xl overflow-hidden">
                                <div class="px-4 py-3 bg-gray-900/60 flex items-center justify-between cursor-pointer"
                                    @click="section.open = !section.open">
                                    <div class="flex items-center gap-3">
                                        <span x-text="section.icon" class="text-lg"></span>
                                        <span class="font-semibold text-white text-sm" x-text="section.label"></span>
                                        <span class="text-xs px-2 py-0.5 rounded-full font-mono"
                                            :class="section.data.failed > 0 ? 'bg-red-900/40 text-red-400' : 'bg-green-900/40 text-green-400'"
                                            x-text="section.data.passed + '/' + section.data.total + ' passed'"></span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="section.open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                                <div x-show="section.open" x-transition class="divide-y divide-gray-700/50">
                                    <template x-for="(result, idx) in (section.data.results || [])" :key="idx">
                                        <div class="px-4 py-2" :class="getResultBg(result)">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs" x-text="getResultIcon(result)"></span>
                                                    <span class="text-sm text-gray-200"
                                                        x-text="result.page_name || result.title || result.url || result.check || ''"></span>
                                                </div>
                                                <div class="flex items-center gap-3 text-xs">
                                                    <template x-if="result.seo_score !== undefined">
                                                        <span class="font-mono font-bold"
                                                            :class="result.seo_score >= 80 ? 'text-green-400' : result.seo_score >= 50 ? 'text-yellow-400' : 'text-red-400'"
                                                            x-text="result.seo_score + '/100'"></span>
                                                    </template>
                                                    <template x-if="result.performance_score !== undefined">
                                                        <span class="font-mono font-bold"
                                                            :class="result.performance_score >= 80 ? 'text-green-400' : result.performance_score >= 50 ? 'text-yellow-400' : 'text-red-400'"
                                                            x-text="result.performance_score + '/100'"></span>
                                                    </template>
                                                    <template x-if="result.response_time_ms !== undefined">
                                                        <span class="font-mono"
                                                            :class="result.response_time_ms < 1000 ? 'text-green-400' : result.response_time_ms < 2000 ? 'text-yellow-400' : 'text-red-400'"
                                                            x-text="result.response_time_ms + 'ms'"></span>
                                                    </template>
                                                    <template x-if="result.status_code !== undefined">
                                                        <span class="font-mono"
                                                            :class="result.status_code < 400 ? 'text-green-400' : 'text-red-400'"
                                                            x-text="result.status_code"></span>
                                                    </template>
                                                    <template x-if="result.quality_score !== undefined">
                                                        <span class="font-mono font-bold"
                                                            :class="result.quality_score >= 70 ? 'text-green-400' : result.quality_score >= 40 ? 'text-yellow-400' : 'text-red-400'"
                                                            x-text="result.quality_score + '/100'"></span>
                                                    </template>
                                                </div>
                                            </div>
                                            <template x-if="result.issues && result.issues.length > 0">
                                                <div class="mt-1 pl-5">
                                                    <template x-for="issue in result.issues" :key="issue">
                                                        <div class="text-xs text-gray-400" x-text="'• ' + issue"></div>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="result.detail">
                                                <div class="mt-1 pl-5 text-xs text-gray-400" x-text="result.detail"></div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Single Scan: flat list -->
                <template x-if="!isFullAudit()">
                    <div class="space-y-2">
                        <template x-for="(result, idx) in (scanResults.results || [])" :key="idx">
                            <div class="py-2 px-3 rounded-lg" :class="getResultBg(result)">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span x-text="getResultIcon(result)"></span>
                                        <span class="text-sm text-gray-200"
                                            x-text="result.page_name || result.title || result.url || result.check || ''"></span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs">
                                        <template x-if="result.seo_score !== undefined">
                                            <span class="font-mono font-bold"
                                                :class="result.seo_score >= 80 ? 'text-green-400' : result.seo_score >= 50 ? 'text-yellow-400' : 'text-red-400'"
                                                x-text="result.seo_score + '/100'"></span>
                                        </template>
                                        <template x-if="result.performance_score !== undefined">
                                            <span class="font-mono font-bold"
                                                :class="result.performance_score >= 80 ? 'text-green-400' : result.performance_score >= 50 ? 'text-yellow-400' : 'text-red-400'"
                                                x-text="result.performance_score + '/100'"></span>
                                        </template>
                                        <template x-if="result.response_time_ms !== undefined">
                                            <span class="font-mono"
                                                :class="result.response_time_ms < 1000 ? 'text-green-400' : result.response_time_ms < 2000 ? 'text-yellow-400' : 'text-red-400'"
                                                x-text="result.response_time_ms + 'ms'"></span>
                                        </template>
                                        <template x-if="result.response_ms !== undefined">
                                            <span class="font-mono"
                                                :class="result.response_ms < 500 ? 'text-green-400' : 'text-yellow-400'"
                                                x-text="result.response_ms + 'ms'"></span>
                                        </template>
                                        <template x-if="result.status_code !== undefined">
                                            <span class="font-mono"
                                                :class="result.status_code < 400 ? 'text-green-400' : 'text-red-400'"
                                                x-text="result.status_code"></span>
                                        </template>
                                        <template x-if="result.quality_score !== undefined">
                                            <span class="font-mono font-bold"
                                                :class="result.quality_score >= 70 ? 'text-green-400' : result.quality_score >= 40 ? 'text-yellow-400' : 'text-red-400'"
                                                x-text="result.quality_score + '/100'"></span>
                                        </template>
                                    </div>
                                </div>
                                <template x-if="result.issues && result.issues.length > 0">
                                    <div class="mt-1 pl-6">
                                        <template x-for="issue in result.issues" :key="issue">
                                            <div class="text-xs text-gray-400" x-text="'• ' + issue"></div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="result.detail">
                                    <div class="mt-1 pl-6 text-xs text-gray-400" x-text="result.detail"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Copy Report Notification -->
                <div x-show="reportCopied" x-transition class="mt-4 p-3 bg-green-900/30 border border-green-700/50 rounded-lg text-center text-sm text-green-400">
                    Report copied to clipboard!
                </div>

                <!-- AI Summary Button + Response -->
                <div class="mt-4 border-t border-gray-700 pt-4">
                    <button @click="aiAnalyzeScan()" :disabled="aiAnalyzing"
                        class="w-full flex items-center justify-center gap-2 bg-purple-600/20 hover:bg-purple-600/30 border border-purple-500/30 disabled:opacity-50 text-purple-300 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        <span x-show="!aiAnalyzing">🧠</span>
                        <svg x-show="aiAnalyzing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span x-text="aiAnalyzing ? 'Analyzing with AI...' : '🧠 Get AI Summary'"></span>
                    </button>
                    <template x-if="aiResponse">
                        <div class="mt-3 bg-purple-900/20 border border-purple-700/30 rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-purple-400 text-sm font-semibold">🤖 AI Analysis</span>
                                <span x-show="aiProvider"
                                    class="text-[10px] px-1.5 py-0.5 rounded bg-purple-800/50 text-purple-300"
                                    x-text="aiProvider"></span>
                            </div>
                            <div class="text-sm text-gray-300 whitespace-pre-wrap leading-relaxed"
                                x-html="formatAiResponse(aiResponse)"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Analysis Modal (for Errors) -->
    <div id="aiModal" x-show="showAiModal" x-cloak style="display:none" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[60] p-4"
        @click.self="showAiModal = false" @keydown.escape.window="showAiModal = false">
        <div class="bg-gray-800 rounded-2xl max-w-2xl w-full max-h-[70vh] overflow-y-auto border border-gray-700">
            <div
                class="px-6 py-4 border-b border-gray-700 flex items-center justify-between sticky top-0 bg-gray-800 z-10">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    AI Error Analysis
                    <span x-show="aiProvider" class="text-xs px-2 py-0.5 rounded bg-purple-800/50 text-purple-300"
                        x-text="aiProvider"></span>
                </h3>
                <button id="closeAiModal" @click="showAiModal = false" class="text-gray-400 hover:text-white text-xl">&times;</button>
            </div>
            <div class="p-6">
                <template x-if="aiAnalyzing">
                    <div class="text-center py-8">
                        <svg class="w-8 h-8 animate-spin mx-auto text-purple-400 mb-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <div class="text-gray-400 text-sm">AI is analyzing the error...</div>
                    </div>
                </template>
                <template x-if="!aiAnalyzing && aiResponse">
                    <div class="text-sm text-gray-300 whitespace-pre-wrap leading-relaxed"
                        x-html="formatAiResponse(aiResponse)"></div>
                </template>
                <template x-if="!aiAnalyzing && aiError">
                    <div class="text-red-400 text-sm" x-text="aiError"></div>
                </template>
            </div>
        </div>
    </div>
</div>

<!-- Alpine.js Component -->
<script>
    const basePath = '<?= defined("BASE_PATH") ? BASE_PATH : "" ?>';
    function supervisorDashboard() {
        return {
            // State
            autoMode: <?= json_encode(($data['settings']['auto_mode'] ?? 'false') === 'true') ?>,
            scanning: false,
            showScanResults: false,
            scanResults: {},
            scanResultsTitle: 'Scan Results',
            currentScanType: '',
            websiteScore: <?= json_encode($data['website_score'] ?? 0) ?>,

            // AI state
            aiAnalyzing: false,
            aiResponse: '',
            aiError: '',
            aiProvider: '',
            showAiModal: false,
            reportCopied: false,

            // Data from PHP
            errors: <?= json_encode($data['recent_errors'] ?? []) ?>,
            suggestions: <?= json_encode($data['suggestions'] ?? []) ?>,
            activityLog: <?= json_encode($data['activity_log'] ?? []) ?>,
            incidents: <?= json_encode($data['active_incidents'] ?? []) ?>,
            pageStatuses: <?= json_encode($data['page_statuses'] ?? []) ?>,

            // Scan type -> API action mapping
            scanActions: {
                'health': { action: 'run_health_scan', title: 'Health Check Results' },
                'seo': { action: 'run_seo_scan', title: 'SEO Audit Results' },
                'performance': { action: 'run_performance_scan', title: 'Performance Results' },
                'links': { action: 'run_link_check', title: 'Link Check Results' },
                'images': { action: 'run_image_audit', title: 'Image Audit Results' },
                'security': { action: 'run_security_scan', title: 'Security Scan Results' },
                'content_quality': { action: 'run_content_quality', title: 'Content Quality Results' },
                'full_audit': { action: 'run_full_audit', title: 'Full Audit Results' },
            },

            // Computed
            get statusCards() {
                const stats = <?= json_encode($data['stats'] ?? []) ?>;
                return [
                    {
                        label: 'Pages',
                        value: (stats.pages_working || 0) + '/' + (stats.pages_total || 0),
                        detail: 'Working / Total',
                        color: (stats.pages_broken || 0) > 0 ? 'bg-red-500' : 'bg-green-500'
                    },
                    {
                        label: 'Errors',
                        value: stats.new_errors || 0,
                        detail: (stats.critical_errors || 0) + ' critical',
                        color: (stats.critical_errors || 0) > 0 ? 'bg-red-500' : (stats.new_errors || 0) > 0 ? 'bg-yellow-500' : 'bg-green-500'
                    },
                    {
                        label: 'Suggestions',
                        value: stats.pending_suggestions || 0,
                        detail: 'Pending review',
                        color: (stats.pending_suggestions || 0) > 5 ? 'bg-yellow-500' : 'bg-green-500'
                    },
                    {
                        label: 'Avg Response',
                        value: stats.avg_response_time_1h ? Math.round(stats.avg_response_time_1h) + 'ms' : '—',
                        detail: 'Last 1 hour',
                        color: (stats.avg_response_time_1h || 0) > 2000 ? 'bg-red-500' : (stats.avg_response_time_1h || 0) > 1000 ? 'bg-yellow-500' : 'bg-green-500'
                    }
                ];
            },

            get pageGroups() {
                const groups = {
                    'public': { type: 'public', label: 'Public Pages', pages: [] },
                    'admin': { type: 'admin', label: 'Admin Panel', pages: [] },
                    'api': { type: 'api', label: 'API Endpoints', pages: [] },
                    'sitemap': { type: 'sitemap', label: 'Sitemap & Feed', pages: [] },
                    'feed': { type: 'feed', label: 'Feed', pages: [] }
                };

                this.pageStatuses.forEach(page => {
                    const type = page.page_type;
                    if (groups[type]) {
                        groups[type].pages.push(page);
                    } else if (groups['public']) {
                        groups['public'].pages.push(page);
                    }
                });

                if (groups['feed'].pages.length > 0) {
                    groups['sitemap'].pages.push(...groups['feed'].pages);
                    groups['sitemap'].label = 'Sitemap & Feed';
                }

                return Object.values(groups).filter(g => g.pages.length > 0 && g.type !== 'feed');
            },

            // Methods
            init() {
                if (this.autoMode) {
                    setInterval(() => this.refreshDashboard(), 60000);
                }
            },

            async runScan(type) {
                const scanConfig = this.scanActions[type];
                if (!scanConfig) {
                    alert('This scan type will be available in a later phase.');
                    return;
                }

                this.scanning = true;
                this.scanResultsTitle = scanConfig.title;
                this.currentScanType = type;
                this.aiResponse = '';
                this.aiError = '';
                try {
                    const res = await fetch(basePath + '/api/supervisor.php?action=' + scanConfig.action);
                    const data = await res.json();
                    if (data.success) {
                        this.scanResults = data.data;
                        this.showScanResults = true;
                        await this.refreshDashboard();
                    } else {
                        alert('Scan failed: ' + (data.error || 'Unknown error'));
                    }
                } catch (e) {
                    console.error('Scan failed:', e);
                    alert('Scan failed. Check console for details.');
                }
                this.scanning = false;
            },

            async runFullAudit() {
                this.scanning = true;
                this.scanResultsTitle = 'Full Audit Results';
                this.currentScanType = 'full_audit';
                this.aiResponse = '';
                this.aiError = '';
                try {
                    const res = await fetch(basePath + '/api/supervisor.php?action=run_full_audit');
                    const data = await res.json();
                    if (data.success) {
                        this.scanResults = data.data;
                        this.showScanResults = true;
                        await this.refreshDashboard();
                    } else {
                        alert('Scan failed: ' + (data.error || 'Unknown error'));
                    }
                } catch (e) {
                    console.error('Scan failed:', e);
                    alert('Scan failed. Check console for details.');
                }
                this.scanning = false;
            },

            async copyReport() {
                const r = this.scanResults;
                const now = new Date().toLocaleString();
                let text = `========================================\n`;
                text += `  DEVLYNC SUPERVISOR - ${this.scanResultsTitle}\n`;
                text += `  ${now}\n`;
                text += `========================================\n\n`;

                if (r.overall_score !== undefined) {
                    text += `OVERALL SCORE: ${r.overall_score}/100\n`;
                    text += `Duration: ${r.duration_seconds}s\n`;
                }
                text += `Passed: ${r.passed || 0}  |  Warnings: ${r.warnings || 0}  |  Failed: ${r.failed || 0}\n\n`;

                if (r.summary) text += `Summary: ${r.summary}\n\n`;

                // Full audit sections
                if (this.isFullAudit() && r.results) {
                    const labels = {health:'HEALTH',seo:'SEO',performance:'PERFORMANCE',links:'LINKS',images:'IMAGES'};
                    for (const [key, section] of Object.entries(r.results)) {
                        text += `--- ${(labels[key]||key).toUpperCase()} (${section.passed}/${section.total} passed) ---\n`;
                        for (const item of (section.results || [])) {
                            const name = item.page_name || item.title || item.url || item.check || '';
                            const icon = (item.is_healthy === false || item.is_broken || (item.status === 'fail')) ? 'FAIL' : (item.issues?.length ? 'WARN' : 'OK');
                            text += `  [${icon}] ${name}`;
                            if (item.seo_score !== undefined) text += ` (SEO: ${item.seo_score}/100)`;
                            if (item.performance_score !== undefined) text += ` (Perf: ${item.performance_score}/100)`;
                            if (item.response_time_ms !== undefined) text += ` (${item.response_time_ms}ms)`;
                            if (item.status_code !== undefined) text += ` [HTTP ${item.status_code}]`;
                            if (item.quality_score !== undefined) text += ` (Quality: ${item.quality_score}/100)`;
                            text += `\n`;
                            if (item.issues?.length) {
                                item.issues.forEach(i => text += `      - ${i}\n`);
                            }
                            if (item.detail) text += `      ${item.detail}\n`;
                        }
                        text += `\n`;
                    }
                } else if (Array.isArray(r.results)) {
                    for (const item of r.results) {
                        const name = item.page_name || item.title || item.url || item.check || '';
                        const icon = (item.is_healthy === false || item.is_broken) ? 'FAIL' : (item.issues?.length ? 'WARN' : 'OK');
                        text += `[${icon}] ${name}`;
                        if (item.seo_score !== undefined) text += ` (SEO: ${item.seo_score}/100)`;
                        if (item.performance_score !== undefined) text += ` (Perf: ${item.performance_score}/100)`;
                        if (item.response_time_ms !== undefined) text += ` (${item.response_time_ms}ms)`;
                        if (item.status_code !== undefined) text += ` [HTTP ${item.status_code}]`;
                        text += `\n`;
                        if (item.issues?.length) {
                            item.issues.forEach(i => text += `    - ${i}\n`);
                        }
                    }
                }

                text += `\n========================================\n`;
                try {
                    await navigator.clipboard.writeText(text);
                    this.reportCopied = true;
                    setTimeout(() => this.reportCopied = false, 3000);
                } catch(e) {
                    // Fallback: select+copy via textarea
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    this.reportCopied = true;
                    setTimeout(() => this.reportCopied = false, 3000);
                }
            },

            isFullAudit() {
                return this.currentScanType === 'full_audit' && this.scanResults.results && !Array.isArray(this.scanResults.results);
            },

            getAuditSections() {
                if (!this.isFullAudit()) return [];
                const meta = {
                    health: { icon: '🏥', label: 'Health Check' },
                    seo: { icon: '🔎', label: 'SEO Audit' },
                    performance: { icon: '⚡', label: 'Performance' },
                    links: { icon: '🔗', label: 'Link Checker' },
                    images: { icon: '🖼️', label: 'Image Audit' },
                };
                return Object.entries(this.scanResults.results).map(([key, data]) => ({
                    key,
                    icon: meta[key]?.icon || '📋',
                    label: meta[key]?.label || key.charAt(0).toUpperCase() + key.slice(1),
                    data,
                    open: data.failed > 0,
                }));
            },

            getResultIcon(result) {
                const PASS = '\u2705', WARN = '\u26A0\uFE0F', FAIL = '\u274C';
                if (result.status === 'fail') return FAIL;
                if (result.status === 'warn') return WARN;
                if (result.status === 'pass') return PASS;
                if (result.seo_score !== undefined) return result.seo_score >= 80 ? PASS : result.seo_score >= 50 ? WARN : FAIL;
                if (result.performance_score !== undefined) return result.performance_score >= 80 ? PASS : result.performance_score >= 50 ? WARN : FAIL;
                if (result.quality_score !== undefined) return result.quality_score >= 70 ? PASS : result.quality_score >= 40 ? WARN : FAIL;
                if (result.is_broken !== undefined) return result.is_broken ? FAIL : PASS;
                if (result.is_healthy !== undefined) return result.is_healthy ? (result.response_time_ms < 1500 ? PASS : WARN) : FAIL;
                if (result.issues && result.issues.length > 0) return WARN;
                return PASS;
            },

            getResultBg(result) {
                if (result.status === 'fail') return 'bg-red-900/20';
                if (result.status === 'warn') return 'bg-yellow-900/10';
                if (result.is_broken) return 'bg-red-900/20';
                if (result.is_healthy === false && result.is_healthy !== undefined) return 'bg-red-900/20';
                if (result.seo_score !== undefined && result.seo_score < 50) return 'bg-red-900/20';
                if (result.performance_score !== undefined && result.performance_score < 50) return 'bg-red-900/20';
                if (result.quality_score !== undefined && result.quality_score < 40) return 'bg-red-900/20';
                if (result.issues && result.issues.length > 0) return 'bg-yellow-900/10';
                return 'bg-gray-700/30';
            },

            async refreshDashboard() {
                try {
                    const res = await fetch(basePath + '/api/supervisor.php?action=dashboard_data');
                    const data = await res.json();
                    if (data.success) {
                        this.pageStatuses = data.data.page_statuses;
                        this.errors = data.data.recent_errors;
                        this.suggestions = data.data.suggestions;
                        this.activityLog = data.data.activity_log;
                        this.incidents = data.data.active_incidents || [];
                        this.websiteScore = data.data.website_score;
                    }
                } catch (e) {
                    console.error('Refresh failed:', e);
                }
            },

            async toggleAutoMode() {
                this.autoMode = !this.autoMode;
                await fetch(basePath + '/api/supervisor.php?action=update_setting', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `key=auto_mode&value=${this.autoMode ? 'true' : 'false'}`
                });
            },

            async updateErrorStatus(id, status) {
                await fetch(basePath + '/api/supervisor.php?action=update_error', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}&status=${status}`
                });
                this.errors = this.errors.filter(e => e.id !== id);
            },

            async updateSuggestionStatus(id, status) {
                await fetch(basePath + '/api/supervisor.php?action=update_suggestion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}&status=${status}`
                });
                this.suggestions = this.suggestions.filter(s => s.id !== id);
            },

            // ── AI Analysis Methods ──

            async aiAnalyzeError(error) {
                this.aiAnalyzing = true;
                this.aiResponse = '';
                this.aiError = '';
                this.showAiModal = true;

                try {
                    const res = await fetch(basePath + '/api/supervisor.php?action=ai_analyze_error', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `error_id=${error.id}`
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.aiResponse = data.response;
                        this.aiProvider = (data.provider || '') + (data.model ? ' / ' + data.model : '');
                    } else {
                        this.aiError = data.error || 'AI analysis failed';
                    }
                } catch (e) {
                    this.aiError = 'Failed to connect to AI service.';
                    console.error('AI analyze error:', e);
                }
                this.aiAnalyzing = false;
            },

            async aiAnalyzeScan() {
                this.aiAnalyzing = true;
                this.aiResponse = '';
                this.aiError = '';

                try {
                    const res = await fetch(basePath + '/api/supervisor.php?action=ai_analyze_scan', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            scan_type: this.currentScanType,
                            scan_data: this.scanResults
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.aiResponse = data.response;
                        this.aiProvider = (data.provider || '') + (data.model ? ' / ' + data.model : '');
                    } else {
                        this.aiResponse = '';
                        this.aiError = data.error || 'AI analysis failed';
                        alert('AI: ' + (data.error || 'Failed'));
                    }
                } catch (e) {
                    this.aiError = 'Failed to connect to AI service.';
                    console.error('AI scan analyze error:', e);
                }
                this.aiAnalyzing = false;
            },

            formatAiResponse(text) {
                if (!text) return '';
                // Simple markdown-to-HTML for AI responses
                return text
                    .replace(/\*\*(.+?)\*\*/g, '<strong class="text-white">$1</strong>')
                    .replace(/^### (.+)$/gm, '<h4 class="text-purple-300 font-semibold mt-3 mb-1">$1</h4>')
                    .replace(/^## (.+)$/gm, '<h3 class="text-purple-200 font-bold mt-4 mb-1">$1</h3>')
                    .replace(/^# (.+)$/gm, '<h2 class="text-white font-bold mt-4 mb-2">$1</h2>')
                    .replace(/^- (.+)$/gm, '<div class="pl-3">• $1</div>')
                    .replace(/^(\d+)\. (.+)$/gm, '<div class="pl-3">$1. $2</div>')
                    .replace(/`([^`]+)`/g, '<code class="bg-gray-700 px-1 rounded text-purple-300">$1</code>')
                    .replace(/\n/g, '<br>');
            },

            timeAgo(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                const now = new Date();
                const seconds = Math.floor((now - date) / 1000);
                if (seconds < 60) return 'Just now';
                if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
                if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
                return Math.floor(seconds / 86400) + 'd ago';
            }
        }
    }
</script>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<script>
// Vanilla JS fallback: force-hide modals and wire close buttons even if Alpine fails
(function() {
    function hideModal(id) {
        var el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }
    function wireClose(btnId, modalId) {
        var btn = document.getElementById(btnId);
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                hideModal(modalId);
            });
        }
    }
    // Hide on DOM ready
    hideModal('scanResultsModal');
    hideModal('aiModal');
    wireClose('closeScanModal', 'scanResultsModal');
    wireClose('closeAiModal', 'aiModal');

    // Also hide on pageshow (catches bfcache restore)
    window.addEventListener('pageshow', function() {
        hideModal('scanResultsModal');
        hideModal('aiModal');
    });

    // Escape key closes both modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideModal('aiModal');
            hideModal('scanResultsModal');
        }
    });

    // Click on backdrop closes modal
    ['scanResultsModal', 'aiModal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('click', function(e) {
                if (e.target === el) el.style.display = 'none';
            });
        }
    });
})();
</script>