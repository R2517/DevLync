<?php
declare(strict_types=1);

/**
 * AiAnalyzer — Multi-Provider AI Service for Supervisor
 * Supports: ChatGPT (OpenAI), Gemini (Google), Claude (Anthropic)
 *
 * Reads provider configs from supervisor_settings table.
 * Automatically picks the first active provider.
 */
class AiAnalyzer
{
    private Database $db;
    private array $settings;
    private ?string $activeProvider = null;
    private ?string $apiKey = null;
    private ?string $model = null;

    // Provider API endpoints
    private const ENDPOINTS = [
        'chatgpt' => 'https://api.openai.com/v1/chat/completions',
        'gemini' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
        'claude' => 'https://api.anthropic.com/v1/messages',
    ];

    // Provider priority order
    private const PROVIDER_ORDER = ['gemini', 'chatgpt', 'claude'];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadSettings();
        $this->resolveActiveProvider();
    }

    // ============ CONFIGURATION ============

    private function loadSettings(): void
    {
        $rows = $this->db->query("SELECT setting_key, setting_value FROM supervisor_settings");
        $this->settings = [];
        foreach ($rows as $row) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    private function resolveActiveProvider(): void
    {
        // Check if AI analysis is enabled
        if (($this->settings['ai_analysis_enabled'] ?? 'false') !== 'true') {
            return;
        }

        // Find first active provider with API key
        foreach (self::PROVIDER_ORDER as $provider) {
            $isActive = ($this->settings[$provider . '_active'] ?? 'false') === 'true';
            $hasKey = !empty($this->settings[$provider . '_api_key'] ?? '');

            if ($isActive && $hasKey) {
                $this->activeProvider = $provider;
                $this->apiKey = $this->settings[$provider . '_api_key'];
                $this->model = $this->settings[$provider . '_model'] ?? $this->getDefaultModel($provider);

                // Auto-fix deprecated models
                $deprecatedModels = [
                    'gemini-2.0-flash' => 'gemini-2.5-flash',
                    'gemini-2.0-flash-001' => 'gemini-2.5-flash',
                    'gemini-2.0-pro' => 'gemini-2.5-pro',
                    'gemini-1.5-flash' => 'gemini-2.5-flash',
                ];
                if (isset($deprecatedModels[$this->model])) {
                    $this->model = $deprecatedModels[$this->model];
                }

                return;
            }
        }
    }

    private function getDefaultModel(string $provider): string
    {
        return match ($provider) {
            'chatgpt' => 'gpt-4o-mini',
            'gemini' => 'gemini-2.0-flash',
            'claude' => 'claude-sonnet-4-5-20250929',
            default => '',
        };
    }

    /**
     * Check if AI is available (at least one provider configured).
     */
    public function isAvailable(): bool
    {
        return $this->activeProvider !== null;
    }

    /**
     * Get the currently active provider name.
     */
    public function getActiveProvider(): ?string
    {
        return $this->activeProvider;
    }

    /**
     * Test all configured providers and return status.
     */
    public function testConnections(): array
    {
        $results = [];

        foreach (self::PROVIDER_ORDER as $provider) {
            $isActive = ($this->settings[$provider . '_active'] ?? 'false') === 'true';
            $hasKey = !empty($this->settings[$provider . '_api_key'] ?? '');
            $model = $this->settings[$provider . '_model'] ?? $this->getDefaultModel($provider);

            $result = [
                'provider' => $provider,
                'active' => $isActive,
                'has_key' => $hasKey,
                'model' => $model,
                'status' => 'not_configured',
                'message' => '',
            ];

            if ($isActive && $hasKey) {
                // Try a minimal API call
                $testResult = $this->callProvider(
                    $provider,
                    $this->settings[$provider . '_api_key'],
                    $model,
                    'Respond with just: OK'
                );

                if ($testResult['success']) {
                    $result['status'] = 'connected';
                    $result['message'] = 'Working! Response: ' . substr($testResult['response'], 0, 50);
                } else {
                    $result['status'] = 'error';
                    $result['message'] = $testResult['error'];
                }
            } elseif (!$isActive) {
                $result['status'] = 'disabled';
                $result['message'] = 'Provider is not activated';
            } else {
                $result['status'] = 'no_key';
                $result['message'] = 'API key not configured';
            }

            $results[] = $result;
        }

        return $results;
    }

    // ============ RATE LIMITING ============

    private function checkRateLimit(): bool
    {
        $maxCalls = (int) ($this->settings['ai_max_daily_calls'] ?? 50);
        $today = date('Y-m-d');

        $count = $this->db->queryOne(
            "SELECT COUNT(*) as c FROM supervisor_activity_log
             WHERE action_type = 'ai_call' AND DATE(created_at) = ?",
            [$today]
        )['c'] ?? 0;

        return $count < $maxCalls;
    }

    private function logAiCall(string $action, string $provider, int $tokensUsed = 0): void
    {
        $this->db->execute(
            "INSERT INTO supervisor_activity_log (action_type, action_description, action_data, triggered_by)
             VALUES ('ai_call', ?, ?, 'manual')",
            [
                "AI {$action} via {$provider}",
                json_encode(['provider' => $provider, 'tokens' => $tokensUsed]),
            ]
        );
    }

    // ============ ANALYSIS METHODS ============

    /**
     * Analyze an error and provide explanation + fix suggestions.
     */
    public function analyzeError(array $error): array
    {
        if (!$this->isAvailable()) {
            return ['success' => false, 'error' => 'No AI provider configured. Go to Supervisor Settings to add an API key.'];
        }
        if (!$this->checkRateLimit()) {
            return ['success' => false, 'error' => 'Daily AI call limit reached. Increase the limit in Settings.'];
        }

        $prompt = "You are a senior PHP developer troubleshooting a website error. Analyze the following error and provide:
1. **Root Cause** — What is causing this error (2-3 sentences)
2. **How to Fix** — Step-by-step fix instructions (numbered)
3. **Prevention** — How to prevent this in the future (1-2 sentences)

Error Details:
- Type: " . ($error['error_type'] ?? 'unknown') . "
- Message: " . ($error['message'] ?? 'No message') . "
- File: " . ($error['file_path'] ?? 'unknown') . "
- Line: " . ($error['line_number'] ?? '?') . "
- Severity: " . ($error['severity'] ?? 'unknown') . "
- Occurrences: " . ($error['occurrence_count'] ?? '1') . "
- First Seen: " . ($error['created_at'] ?? 'unknown') . "

Keep the response concise and actionable. Use markdown formatting.";

        $result = $this->sendPrompt($prompt, 'analyze_error');

        if ($result['success']) {
            // Store analysis result on the error record
            $this->db->execute(
                "UPDATE supervisor_errors SET notes = ? WHERE id = ?",
                ['AI Analysis (' . $this->activeProvider . '): ' . $result['response'], $error['id']]
            );
        }

        return $result;
    }

    /**
     * Analyze scan results and generate a summary with priorities.
     */
    public function analyzeScanResults(string $scanType, array $scanData): array
    {
        if (!$this->isAvailable()) {
            return ['success' => false, 'error' => 'No AI provider configured.'];
        }
        if (!$this->checkRateLimit()) {
            return ['success' => false, 'error' => 'Daily AI call limit reached.'];
        }

        $scanSummary = json_encode([
            'type' => $scanType,
            'total' => $scanData['total'] ?? 0,
            'passed' => $scanData['passed'] ?? 0,
            'failed' => $scanData['failed'] ?? 0,
            'warnings' => $scanData['warnings'] ?? 0,
            'sample_results' => array_slice($scanData['results'] ?? [], 0, 5),
        ], JSON_PRETTY_PRINT);

        $typeLabel = match ($scanType) {
            'seo' => 'SEO Audit',
            'performance' => 'Performance',
            'links' => 'Link Check',
            'images' => 'Image Audit',
            'health' => 'Health Check',
            default => $scanType,
        };

        $prompt = "You are a website optimization expert. Analyze these {$typeLabel} scan results and provide:

1. **Summary** — Brief overview of the website's {$typeLabel} status (2-3 sentences)
2. **Top 3 Priority Actions** — Most impactful improvements to make first (numbered)
3. **Quick Wins** — Easy fixes that can be done in < 15 minutes (bullet list)
4. **Overall Assessment** — Rate as: Excellent / Good / Needs Work / Critical

Scan Results:
{$scanSummary}

Keep the response concise and actionable. Use markdown formatting.";

        return $this->sendPrompt($prompt, 'analyze_scan');
    }

    /**
     * Analyze a single page's SEO result and provide specific advice.
     */
    public function analyzePageSeo(array $seoResult): array
    {
        if (!$this->isAvailable()) {
            return ['success' => false, 'error' => 'No AI provider configured.'];
        }
        if (!$this->checkRateLimit()) {
            return ['success' => false, 'error' => 'Daily AI call limit reached.'];
        }

        $pageData = json_encode($seoResult, JSON_PRETTY_PRINT);

        $prompt = "You are an SEO expert. Analyze this page's SEO scan result and write specific, actionable improvements:

Page SEO Data:
{$pageData}

Provide:
1. **SEO Rating** (out of 10) with one-line justification
2. **Priority Fixes** — the 3 most impactful things to fix (with specific instructions, not generic advice)
3. **Meta Tag Suggestions** — if meta title/description is missing or bad, write example ones

Keep response under 200 words. Use markdown.";

        return $this->sendPrompt($prompt, 'analyze_seo');
    }

    // ============ CORE API CALL ============

    /**
     * Send a prompt to the active AI provider.
     */
    private function sendPrompt(string $prompt, string $action): array
    {
        $result = $this->callProvider($this->activeProvider, $this->apiKey, $this->model, $prompt);

        if ($result['success']) {
            $this->logAiCall($action, $this->activeProvider, $result['tokens'] ?? 0);
        }

        $result['provider'] = $this->activeProvider;
        $result['model'] = $this->model;
        return $result;
    }

    /**
     * Makes the actual API call to a specific provider.
     */
    private function callProvider(string $provider, string $apiKey, string $model, string $prompt): array
    {
        return match ($provider) {
            'chatgpt' => $this->callOpenAI($apiKey, $model, $prompt),
            'gemini' => $this->callGemini($apiKey, $model, $prompt),
            'claude' => $this->callClaude($apiKey, $model, $prompt),
            default => ['success' => false, 'error' => "Unknown provider: {$provider}"],
        };
    }

    // ============ PROVIDER IMPLEMENTATIONS ============

    /**
     * OpenAI ChatGPT API
     */
    private function callOpenAI(string $apiKey, string $model, string $prompt): array
    {
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional web developer and SEO expert helping analyze a website.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 1000,
            'temperature' => 0.3,
        ]);

        $ch = curl_init(self::ENDPOINTS['chatgpt']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            return ['success' => false, 'error' => 'cURL error: ' . curl_error($ch)];
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $error = $data['error']['message'] ?? 'HTTP ' . $httpCode;
            return ['success' => false, 'error' => "OpenAI API error: {$error}"];
        }

        $text = $data['choices'][0]['message']['content'] ?? '';
        $tokens = $data['usage']['total_tokens'] ?? 0;

        return ['success' => true, 'response' => trim($text), 'tokens' => $tokens];
    }

    /**
     * Google Gemini API
     */
    private function callGemini(string $apiKey, string $model, string $prompt): array
    {
        $url = str_replace('{model}', $model, self::ENDPOINTS['gemini']) . '?key=' . $apiKey;

        $payload = json_encode([
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'maxOutputTokens' => 1000,
                'temperature' => 0.3,
            ],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            return ['success' => false, 'error' => 'cURL error: ' . curl_error($ch)];
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $error = $data['error']['message'] ?? 'HTTP ' . $httpCode;
            return ['success' => false, 'error' => "Gemini API error: {$error}"];
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $tokens = ($data['usageMetadata']['totalTokenCount'] ?? 0);

        return ['success' => true, 'response' => trim($text), 'tokens' => $tokens];
    }

    /**
     * Anthropic Claude API
     */
    private function callClaude(string $apiKey, string $model, string $prompt): array
    {
        $payload = json_encode([
            'model' => $model,
            'max_tokens' => 1000,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $ch = curl_init(self::ENDPOINTS['claude']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            return ['success' => false, 'error' => 'cURL error: ' . curl_error($ch)];
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $error = $data['error']['message'] ?? 'HTTP ' . $httpCode;
            return ['success' => false, 'error' => "Claude API error: {$error}"];
        }

        $text = $data['content'][0]['text'] ?? '';
        $tokens = ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0);

        return ['success' => true, 'response' => trim($text), 'tokens' => $tokens];
    }
}
