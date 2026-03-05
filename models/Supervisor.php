<?php
declare(strict_types=1);

/**
 * Supervisor Model
 * Handles all AI Supervisor data: health checks, errors, page status,
 * suggestions, scans, reports, activity log, settings, and incidents.
 */
class Supervisor
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ============ DASHBOARD DATA ============

    public function getDashboardStats(): array
    {
        $stats = $this->db->queryOne("SELECT * FROM v_supervisor_dashboard");
        return $stats ?: [];
    }

    // ============ HEALTH CHECKS ============

    public function runHealthCheck(string $url, string $pageName = ''): array
    {
        $result = [
            'url' => $url,
            'page_name' => $pageName,
            'status_code' => 0,
            'response_time_ms' => 0,
            'content_length' => 0,
            'is_healthy' => false,
            'error_message' => null,
        ];

        try {
            // Build URL — try XAMPP Apache (port 80) first to avoid
            // single-threaded PHP dev server deadlock
            $currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
            $urls = [];
            if (strpos($currentHost, ':8000') !== false) {
                $urls[] = 'http://localhost/devlync.com' . $url;
            }
            $urls[] = $scheme . '://' . $currentHost . $basePath . $url;

            // Admin/API pages require auth — don't follow redirects to avoid
            // Apache thread deadlock on self-referencing requests
            $isProtected = str_starts_with($url, '/admin') || str_starts_with($url, '/api');

            $response = false;
            $httpCode = 0;
            $totalTime = 0;

            foreach ($urls as $fetchUrl) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $fetchUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => !$isProtected,
                    CURLOPT_TIMEOUT => $isProtected ? 3 : 5,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_NOBODY => false,
                    CURLOPT_HTTPHEADER => ['User-Agent: DevLync-Supervisor/1.0'],
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

                if (!curl_errno($ch) && $httpCode > 0 && $httpCode < 400) {
                    break; // Success — don't try next URL
                }
                // For protected pages, a 302 redirect to login IS healthy
                if ($isProtected && !curl_errno($ch) && $httpCode === 302) {
                    break;
                }
            }

            if (curl_errno($ch)) {
                $result['error_message'] = curl_error($ch);
            }
            $result['status_code'] = $httpCode;
            $result['response_time_ms'] = (int) ($totalTime * 1000);
            $result['content_length'] = strlen($response ?: '');
            // Protected pages: 302 redirect to login = healthy (auth is working)
            $result['is_healthy'] = $isProtected
                ? (($httpCode === 302 || ($httpCode >= 200 && $httpCode < 400)) && $result['response_time_ms'] < 2500)
                : ($httpCode >= 200 && $httpCode < 400 && $result['response_time_ms'] < 2500);

        } catch (\Exception $e) {
            $result['error_message'] = $e->getMessage();
        }

        // Save to database
        $this->db->execute(
            "INSERT INTO supervisor_health_checks (url, page_name, status_code, response_time_ms, content_length, is_healthy, error_message)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$url, $pageName, $result['status_code'], $result['response_time_ms'], $result['content_length'], $result['is_healthy'] ? 1 : 0, $result['error_message']]
        );

        // Update page status
        $this->updatePageStatus($url, $result);

        return $result;
    }

    public function runFullHealthScan(): array
    {
        $scanId = $this->createScan('health', 'manual');

        $pages = $this->db->query(
            "SELECT * FROM supervisor_page_status WHERE page_type IN ('public','admin','api','sitemap','feed') ORDER BY id"
        );

        $results = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;

        foreach ($pages as $page) {
            $url = $page['url_pattern'];

            // Skip pattern URLs (contain {slug})
            if (strpos($url, '{') !== false) {
                continue;
            }

            $check = $this->runHealthCheck($url, $page['page_name']);
            $results[] = $check;

            if ($check['is_healthy'] && $check['response_time_ms'] < 1500) {
                $passed++;
            } elseif ($check['is_healthy']) {
                $warnings++;
            } else {
                $failed++;
            }
        }

        // Also check a sample of published articles
        $articles = $this->db->query(
            "SELECT slug, content_type FROM articles WHERE status = 'published' ORDER BY RAND() LIMIT 5"
        );
        foreach ($articles as $article) {
            $url = '/' . ($article['content_type'] === 'blog' ? 'blog' : $article['content_type'] . 's') . '/' . $article['slug'];
            $check = $this->runHealthCheck($url, 'Article: ' . $article['slug']);
            $results[] = $check;
            if ($check['is_healthy']) {
                $passed++;
            } else {
                $failed++;
            }
        }

        // Complete scan record
        $this->completeScan($scanId, count($results), $passed, $failed, $warnings, $results);
        $this->logActivity('health_scan', "Full health scan completed: {$passed} passed, {$failed} failed, {$warnings} warnings");

        return [
            'scan_id' => $scanId,
            'total' => count($results),
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'results' => $results,
        ];
    }

    // ============ ERROR MANAGEMENT ============

    public function logError(string $type, string $severity, string $message, array $extra = []): int
    {
        // Deduplication check
        $existing = $this->db->queryOne(
            "SELECT id, occurrence_count FROM supervisor_errors
             WHERE error_type = ? AND message = ? AND status != 'resolved'
             ORDER BY last_seen_at DESC LIMIT 1",
            [$type, $message]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE supervisor_errors SET occurrence_count = occurrence_count + 1, last_seen_at = NOW() WHERE id = ?",
                [$existing['id']]
            );
            return (int) $existing['id'];
        }

        $this->db->execute(
            "INSERT INTO supervisor_errors (error_type, severity, message, file_path, line_number, url, stack_trace)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $type,
                $severity,
                $message,
                $extra['file'] ?? null,
                $extra['line'] ?? null,
                $extra['url'] ?? null,
                $extra['trace'] ?? null,
            ]
        );

        return $this->db->lastInsertId();
    }

    public function getErrors(string $status = 'all', string $severity = 'all', int $limit = 50): array
    {
        $where = [];
        $params = [];

        if ($status !== 'all') {
            $where[] = "status = ?";
            $params[] = $status;
        }
        if ($severity !== 'all') {
            $where[] = "severity = ?";
            $params[] = $severity;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->db->query(
            "SELECT * FROM supervisor_errors {$whereClause}
             ORDER BY FIELD(severity, 'critical', 'warning', 'info', 'optimization'), last_seen_at DESC
             LIMIT ?",
            array_merge($params, [$limit])
        );
    }

    public function getErrorById(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM supervisor_errors WHERE id = ?",
            [$id]
        );
    }

    public function updateErrorStatus(int $id, string $status): bool
    {
        $resolvedAt = ($status === 'resolved') ? date('Y-m-d H:i:s') : null;
        return $this->db->execute(
            "UPDATE supervisor_errors SET status = ?, resolved_at = ? WHERE id = ?",
            [$status, $resolvedAt, $id]
        ) > 0;
    }

    public function parsePhpErrorLog(int $maxLines = 200): array
    {
        $errors = [];
        $logFile = ini_get('error_log');

        if (!$logFile || !file_exists($logFile) || !is_readable($logFile)) {
            return ['error' => 'No accessible error log found'];
        }

        $lines = array_slice(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -$maxLines);

        foreach ($lines as $line) {
            if (preg_match('/\[(.*?)\] (PHP )?(Fatal error|Warning|Notice|Parse error|Deprecated):(.+?)( in (.+?) on line (\d+))?$/i', $line, $matches)) {
                $severity = 'info';
                $errorLevel = strtolower($matches[3] ?? '');
                if (strpos($errorLevel, 'fatal') !== false) {
                    $severity = 'critical';
                } elseif (strpos($errorLevel, 'warning') !== false) {
                    $severity = 'warning';
                } elseif (strpos($errorLevel, 'parse') !== false) {
                    $severity = 'critical';
                }

                $this->logError('php', $severity, trim($matches[4] ?? $line), [
                    'file' => $matches[6] ?? null,
                    'line' => isset($matches[7]) ? (int) $matches[7] : null,
                ]);

                $errors[] = [
                    'timestamp' => $matches[1] ?? '',
                    'level' => $matches[3] ?? 'Unknown',
                    'message' => trim($matches[4] ?? $line),
                    'file' => $matches[6] ?? null,
                    'line' => $matches[7] ?? null,
                ];
            }
        }

        return $errors;
    }

    // ============ PAGE STATUS ============

    public function getPageStatuses(): array
    {
        return $this->db->query(
            "SELECT * FROM supervisor_page_status ORDER BY FIELD(page_type, 'public', 'admin', 'api', 'sitemap', 'feed'), id"
        );
    }

    public function getPageStatusSummary(): array
    {
        return $this->db->query(
            "SELECT page_type, COUNT(*) as total,
                    SUM(CASE WHEN is_functional = 1 AND last_response_time_ms < 1500 THEN 1 ELSE 0 END) as healthy,
                    SUM(CASE WHEN is_functional = 1 AND last_response_time_ms >= 1500 THEN 1 ELSE 0 END) as slow,
                    SUM(CASE WHEN is_functional = 0 AND last_check_at IS NOT NULL THEN 1 ELSE 0 END) as broken,
                    SUM(CASE WHEN last_check_at IS NULL THEN 1 ELSE 0 END) as unchecked
             FROM supervisor_page_status
             GROUP BY page_type"
        );
    }

    private function updatePageStatus(string $url, array $checkResult): void
    {
        $this->db->execute(
            "UPDATE supervisor_page_status
             SET is_functional = ?,
                 last_status_code = ?,
                 last_response_time_ms = ?,
                 last_check_at = NOW(),
                 check_count = check_count + 1,
                 fail_count = fail_count + IF(? = 0, 1, 0),
                 avg_response_time_ms = CASE
                     WHEN check_count = 0 THEN ?
                     ELSE ROUND((COALESCE(avg_response_time_ms, 0) * check_count + ?) / (check_count + 1))
                 END
             WHERE url_pattern = ?",
            [
                $checkResult['is_healthy'] ? 1 : 0,
                $checkResult['status_code'],
                $checkResult['response_time_ms'],
                $checkResult['is_healthy'] ? 1 : 0,
                $checkResult['response_time_ms'],
                $checkResult['response_time_ms'],
                $url,
            ]
        );
    }

    // ============ SUGGESTIONS ============

    public function addSuggestion(array $data): int
    {
        $this->db->execute(
            "INSERT INTO supervisor_suggestions (category, priority, title, description, impact_area, impact_score, effort_score, estimated_time, fix_instructions, fix_code, affected_files, affected_urls)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['category'],
                $data['priority'] ?? 'medium',
                $data['title'],
                $data['description'] ?? null,
                $data['impact_area'] ?? null,
                $data['impact_score'] ?? 50,
                $data['effort_score'] ?? 50,
                $data['estimated_time'] ?? null,
                $data['fix_instructions'] ?? null,
                $data['fix_code'] ?? null,
                isset($data['affected_files']) ? json_encode($data['affected_files']) : null,
                isset($data['affected_urls']) ? json_encode($data['affected_urls']) : null,
            ]
        );
        return $this->db->lastInsertId();
    }

    public function getSuggestions(string $status = 'pending', string $category = 'all', int $limit = 50): array
    {
        $where = [];
        $params = [];

        if ($status !== 'all') {
            $where[] = "status = ?";
            $params[] = $status;
        }
        if ($category !== 'all') {
            $where[] = "category = ?";
            $params[] = $category;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->db->query(
            "SELECT * FROM supervisor_suggestions {$whereClause}
             ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low'),
                      (impact_score / GREATEST(effort_score, 1)) DESC
             LIMIT ?",
            array_merge($params, [$limit])
        );
    }

    public function updateSuggestionStatus(int $id, string $status): bool
    {
        $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
        return $this->db->execute(
            "UPDATE supervisor_suggestions SET status = ?, completed_at = ? WHERE id = ?",
            [$status, $completedAt, $id]
        ) > 0;
    }

    // ============ SCANS ============

    public function createScan(string $type, string $trigger = 'manual'): int
    {
        $this->db->execute(
            "INSERT INTO supervisor_scans (scan_type, status, trigger_type, started_at) VALUES (?, 'running', ?, NOW())",
            [$type, $trigger]
        );
        return $this->db->lastInsertId();
    }

    public function completeScan(int $scanId, int $total, int $passed, int $failed, int $warnings, array $results): void
    {
        $this->db->execute(
            "UPDATE supervisor_scans
             SET status = 'completed', total_checks = ?, passed_checks = ?, failed_checks = ?, warning_checks = ?,
                 results_json = ?, completed_at = NOW(),
                 duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW())
             WHERE id = ?",
            [$total, $passed, $failed, $warnings, json_encode($results), $scanId]
        );
    }

    public function getRecentScans(int $limit = 10): array
    {
        return $this->db->query(
            "SELECT * FROM supervisor_scans ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    // ============ REPORTS ============

    public function getLatestReport(string $type = 'full_audit'): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM supervisor_reports WHERE report_type = ? ORDER BY generated_at DESC LIMIT 1",
            [$type]
        );
    }

    public function getReports(int $limit = 20): array
    {
        return $this->db->query(
            "SELECT id, report_type, title, overall_score, summary, issues_found, issues_fixed, issues_pending, generated_at
             FROM supervisor_reports ORDER BY generated_at DESC LIMIT ?",
            [$limit]
        );
    }

    // ============ ACTIVITY LOG ============

    public function logActivity(string $type, string $description, array $data = []): void
    {
        $this->db->execute(
            "INSERT INTO supervisor_activity_log (action_type, action_description, action_data, triggered_by)
             VALUES (?, ?, ?, ?)",
            [$type, $description, !empty($data) ? json_encode($data) : null, $data['triggered_by'] ?? 'manual']
        );
    }

    public function getActivityLog(int $limit = 50): array
    {
        return $this->db->query(
            "SELECT * FROM supervisor_activity_log ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    // ============ SETTINGS ============

    public function getSetting(string $key): ?string
    {
        $row = $this->db->queryOne(
            "SELECT setting_value FROM supervisor_settings WHERE setting_key = ?",
            [$key]
        );
        return $row['setting_value'] ?? null;
    }

    public function updateSetting(string $key, string $value): bool
    {
        return $this->db->execute(
            "UPDATE supervisor_settings SET setting_value = ? WHERE setting_key = ?",
            [$value, $key]
        ) > 0;
    }

    public function getAllSettings(): array
    {
        $rows = $this->db->query("SELECT * FROM supervisor_settings ORDER BY id");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    // ============ INCIDENTS ============

    public function getActiveIncidents(): array
    {
        return $this->db->query(
            "SELECT * FROM supervisor_incidents WHERE status IN ('active', 'investigating') ORDER BY started_at DESC"
        );
    }

    // ============ HELPERS ============

    public function getFullUrl(string $path): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        return $scheme . '://' . $host . $basePath . $path;
    }

    public function calculateWebsiteScore(): int
    {
        $scores = [];

        // Health score
        $pageStats = $this->db->queryOne(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN is_functional = 1 THEN 1 ELSE 0 END) as working
             FROM supervisor_page_status WHERE last_check_at IS NOT NULL"
        );
        if ($pageStats && $pageStats['total'] > 0) {
            $scores['health'] = (int) (($pageStats['working'] / $pageStats['total']) * 100);
        }

        // Error score
        $errorCount = $this->db->queryOne(
            "SELECT COUNT(*) as c FROM supervisor_errors WHERE status NOT IN ('resolved', 'ignored')"
        )['c'] ?? 0;
        $scores['errors'] = max(0, 100 - ($errorCount * 5));

        // Performance score
        $avgResponse = $this->db->queryOne(
            "SELECT AVG(response_time_ms) as avg_ms FROM supervisor_health_checks
             WHERE checked_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        )['avg_ms'] ?? null;
        if ($avgResponse) {
            if ($avgResponse < 500)
                $scores['performance'] = 100;
            elseif ($avgResponse < 1000)
                $scores['performance'] = 85;
            elseif ($avgResponse < 1500)
                $scores['performance'] = 70;
            elseif ($avgResponse < 2000)
                $scores['performance'] = 55;
            elseif ($avgResponse < 2500)
                $scores['performance'] = 40;
            else
                $scores['performance'] = 20;
        }

        // Overall = weighted average
        $overall = 0;
        $weights = ['health' => 0.35, 'errors' => 0.30, 'performance' => 0.35];
        $totalWeight = 0;

        foreach ($weights as $key => $weight) {
            if (isset($scores[$key])) {
                $overall += $scores[$key] * $weight;
                $totalWeight += $weight;
            }
        }

        $finalScore = $totalWeight > 0 ? (int) ($overall / $totalWeight) : 0;

        $this->updateSetting('website_score', (string) $finalScore);

        return $finalScore;
    }

    // ============ PHASE 2: SEO SCANNER ============

    /**
     * Runs an SEO scan across all public pages + published articles.
     */
    public function runSeoScan(): array
    {
        $scanId = $this->createScan('seo', 'manual');
        $results = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;

        // Scan public pages
        $pages = $this->db->query(
            "SELECT url_pattern, page_name FROM supervisor_page_status WHERE page_type = 'public'"
        );
        foreach ($pages as $page) {
            if (strpos($page['url_pattern'], '{') !== false)
                continue;
            $result = $this->scanPageSeo($page['url_pattern'], $page['page_name']);
            $results[] = $result;
            if ($result['seo_score'] >= 80)
                $passed++;
            elseif ($result['seo_score'] >= 50)
                $warnings++;
            else
                $failed++;
        }

        // Scan sample articles
        $articles = $this->db->query(
            "SELECT slug, content_type, title FROM articles WHERE status = 'published' ORDER BY RAND() LIMIT 10"
        );
        foreach ($articles as $article) {
            $url = '/' . ($article['content_type'] === 'blog' ? 'blog' : $article['content_type'] . 's') . '/' . $article['slug'];
            $result = $this->scanPageSeo($url, $article['title']);
            $results[] = $result;
            if ($result['seo_score'] >= 80)
                $passed++;
            elseif ($result['seo_score'] >= 50)
                $warnings++;
            else
                $failed++;
        }

        $this->completeScan($scanId, count($results), $passed, $failed, $warnings, $results);
        $this->logActivity('seo_scan', "SEO scan completed: {$passed} passed, {$failed} failed, {$warnings} warnings");

        // Generate suggestions from SEO findings
        foreach ($results as $r) {
            if (!empty($r['issues'])) {
                foreach ($r['issues'] as $issue) {
                    $this->addSuggestion([
                        'category' => 'seo',
                        'priority' => $r['seo_score'] < 50 ? 'high' : 'medium',
                        'title' => $issue,
                        'description' => "Found on page: {$r['url']}",
                        'impact_area' => 'SEO',
                        'impact_score' => 70,
                        'effort_score' => 30,
                        'estimated_time' => '15 min',
                        'affected_urls' => [$r['url']],
                    ]);
                }
            }
        }

        return [
            'scan_id' => $scanId,
            'total' => count($results),
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'results' => $results,
        ];
    }

    /**
     * Scans a single page for SEO factors.
     */
    public function scanPageSeo(string $url, string $pageName = ''): array
    {
        $result = [
            'url' => $url,
            'page_name' => $pageName,
            'seo_score' => 0,
            'issues' => [],
        ];

        $html = $this->fetchPage($url);

        if (!$html) {
            $result['issues'][] = "Page could not be fetched";
            return $result;
        }

        $score = 0;
        $maxScore = 0;

        // Meta title
        $maxScore += 15;
        $hasTitle = (bool) preg_match('/<title[^>]*>(.+?)<\/title>/is', $html, $titleMatch);
        $titleLength = strlen(trim($titleMatch[1] ?? ''));
        $result['meta_title'] = trim($titleMatch[1] ?? '');
        $result['meta_title_length'] = $titleLength;
        if ($hasTitle && $titleLength >= 30 && $titleLength <= 65) {
            $score += 15;
        } elseif ($hasTitle) {
            $score += 8;
            if ($titleLength < 30)
                $result['issues'][] = "Meta title too short ({$titleLength} chars, ideal: 30-65)";
            if ($titleLength > 65)
                $result['issues'][] = "Meta title too long ({$titleLength} chars, ideal: 30-65)";
        } else {
            $result['issues'][] = "Missing meta title tag";
        }

        // Meta description
        $maxScore += 15;
        $hasDesc = (bool) preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)/i', $html, $descMatch);
        $descLength = strlen(trim($descMatch[1] ?? ''));
        $result['meta_description_length'] = $descLength;
        if ($hasDesc && $descLength >= 120 && $descLength <= 160) {
            $score += 15;
        } elseif ($hasDesc && $descLength > 0) {
            $score += 8;
            if ($descLength < 120)
                $result['issues'][] = "Meta description too short ({$descLength} chars, ideal: 120-160)";
            if ($descLength > 160)
                $result['issues'][] = "Meta description too long ({$descLength} chars, ideal: 120-160)";
        } else {
            $result['issues'][] = "Missing meta description";
        }

        // H1 tag
        $maxScore += 10;
        preg_match_all('/<h1[^>]*>/i', $html, $h1Matches);
        $h1Count = count($h1Matches[0]);
        $result['h1_count'] = $h1Count;
        if ($h1Count === 1) {
            $score += 10;
        } elseif ($h1Count === 0) {
            $result['issues'][] = "Missing H1 heading tag";
        } else {
            $score += 5;
            $result['issues'][] = "Multiple H1 tags found ({$h1Count}), use only one per page";
        }

        // Canonical tag
        $maxScore += 10;
        $hasCanonical = (bool) preg_match('/<link[^>]+rel=["\']canonical["\']/i', $html);
        $result['has_canonical'] = $hasCanonical;
        if ($hasCanonical) {
            $score += 10;
        } else {
            $result['issues'][] = "Missing canonical tag";
        }

        // Open Graph tags
        $maxScore += 10;
        $hasOg = (bool) preg_match('/<meta[^>]+property=["\']og:/i', $html);
        $result['has_og_tags'] = $hasOg;
        if ($hasOg) {
            $score += 10;
        } else {
            $result['issues'][] = "Missing Open Graph tags (og:title, og:description, og:image)";
        }

        // Schema markup
        $maxScore += 10;
        $hasSchema = (bool) preg_match('/<script[^>]+type=["\']application\/ld\+json["\']/i', $html);
        $result['has_schema'] = $hasSchema;
        if ($hasSchema) {
            $score += 10;
        } else {
            $result['issues'][] = "Missing Schema.org structured data (JSON-LD)";
        }

        // Images with alt tags
        $maxScore += 10;
        preg_match_all('/<img[^>]*>/i', $html, $imgMatches);
        $totalImages = count($imgMatches[0]);
        $imagesWithoutAlt = 0;
        foreach ($imgMatches[0] as $img) {
            if (!preg_match('/alt=["\'][^"\']+["\']/i', $img)) {
                $imagesWithoutAlt++;
            }
        }
        $result['image_count'] = $totalImages;
        $result['images_without_alt'] = $imagesWithoutAlt;
        if ($totalImages === 0 || $imagesWithoutAlt === 0) {
            $score += 10;
        } elseif ($imagesWithoutAlt <= 2) {
            $score += 5;
            $result['issues'][] = "{$imagesWithoutAlt} images missing alt tags";
        } else {
            $result['issues'][] = "{$imagesWithoutAlt}/{$totalImages} images missing alt tags";
        }

        // Internal links
        $maxScore += 10;
        preg_match_all('/<a[^>]+href=["\']\/[^"\']*["\']/i', $html, $internalLinks);
        $internalCount = count($internalLinks[0]);
        $result['internal_links'] = $internalCount;
        if ($internalCount >= 3) {
            $score += 10;
        } elseif ($internalCount >= 1) {
            $score += 5;
            $result['issues'][] = "Only {$internalCount} internal links, add more for better interlinking";
        } else {
            $result['issues'][] = "No internal links found on this page";
        }

        // Word count
        $maxScore += 10;
        $textContent = strip_tags($html);
        $wordCount = str_word_count($textContent);
        $result['word_count'] = $wordCount;
        if ($wordCount >= 300) {
            $score += 10;
        } elseif ($wordCount >= 100) {
            $score += 5;
            $result['issues'][] = "Thin content ({$wordCount} words). Aim for 300+ words";
        } else {
            $result['issues'][] = "Very thin content ({$wordCount} words)";
        }

        // Calculate final score as percentage
        $result['seo_score'] = $maxScore > 0 ? (int) (($score / $maxScore) * 100) : 0;

        // Save to DB
        $this->db->execute(
            "INSERT INTO supervisor_seo_scores (url, has_meta_title, meta_title_length, has_meta_description, meta_description_length, has_h1, h1_count, has_schema_markup, has_canonical, has_og_tags, internal_links_count, image_count, images_without_alt, word_count, seo_score, issues)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $url,
                $hasTitle ? 1 : 0,
                $titleLength,
                $hasDesc ? 1 : 0,
                $descLength,
                $h1Count > 0 ? 1 : 0,
                $h1Count,
                $hasSchema ? 1 : 0,
                $hasCanonical ? 1 : 0,
                $hasOg ? 1 : 0,
                $internalCount,
                $totalImages,
                $imagesWithoutAlt,
                $wordCount,
                $result['seo_score'],
                json_encode($result['issues']),
            ]
        );

        return $result;
    }

    // ============ PHASE 2: PERFORMANCE MONITOR ============

    /**
     * Runs performance scan across public pages + sample articles.
     */
    public function runPerformanceScan(): array
    {
        $scanId = $this->createScan('performance', 'manual');
        $results = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;

        $pages = $this->db->query(
            "SELECT url_pattern, page_name FROM supervisor_page_status WHERE page_type = 'public'"
        );
        foreach ($pages as $page) {
            if (strpos($page['url_pattern'], '{') !== false)
                continue;
            $result = $this->measurePagePerformance($page['url_pattern'], $page['page_name']);
            $results[] = $result;
            if ($result['performance_score'] >= 80)
                $passed++;
            elseif ($result['performance_score'] >= 50)
                $warnings++;
            else
                $failed++;
        }

        $this->completeScan($scanId, count($results), $passed, $failed, $warnings, $results);
        $this->logActivity('performance_scan', "Performance scan completed: {$passed} passed, {$failed} failed, {$warnings} warnings");

        // Generate suggestions for slow pages
        foreach ($results as $r) {
            if ($r['performance_score'] < 70) {
                $tips = [];
                if (($r['total_load_ms'] ?? 0) > 2000)
                    $tips[] = "Page load time is {$r['total_load_ms']}ms - optimize server response";
                if (($r['total_size_bytes'] ?? 0) > 500000)
                    $tips[] = "Page size is " . round(($r['total_size_bytes'] ?? 0) / 1024) . "KB - reduce payload";
                if (!($r['gzip_enabled'] ?? false))
                    $tips[] = "Enable Gzip compression for faster transfer";

                foreach ($tips as $tip) {
                    $this->addSuggestion([
                        'category' => 'performance',
                        'priority' => $r['performance_score'] < 50 ? 'high' : 'medium',
                        'title' => $tip,
                        'description' => "Page: {$r['url']}",
                        'impact_area' => 'Performance',
                        'impact_score' => 80,
                        'effort_score' => 40,
                        'estimated_time' => '30 min',
                        'affected_urls' => [$r['url']],
                    ]);
                }
            }
        }

        return [
            'scan_id' => $scanId,
            'total' => count($results),
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'results' => $results,
        ];
    }

    /**
     * Measures performance metrics for a single page.
     */
    public function measurePagePerformance(string $url, string $pageName = ''): array
    {
        $result = [
            'url' => $url,
            'page_name' => $pageName,
            'ttfb_ms' => 0,
            'total_load_ms' => 0,
            'dom_size_bytes' => 0,
            'total_size_bytes' => 0,
            'gzip_enabled' => false,
            'performance_score' => 0,
        ];

        $startTime = microtime(true);
        $response = $this->fetchPage($url);
        $elapsed = (int) ((microtime(true) - $startTime) * 1000);

        if ($response) {
            $result['ttfb_ms'] = (int) ($elapsed * 0.6);
            $result['total_load_ms'] = $elapsed;
            $result['total_size_bytes'] = strlen($response);
            $result['dom_size_bytes'] = strlen($response);

            // Calculate score
            $score = 0;
            $maxScore = 0;

            // TTFB (Time to First Byte)
            $maxScore += 25;
            if ($result['ttfb_ms'] < 200)
                $score += 25;
            elseif ($result['ttfb_ms'] < 500)
                $score += 20;
            elseif ($result['ttfb_ms'] < 1000)
                $score += 12;
            elseif ($result['ttfb_ms'] < 2000)
                $score += 5;

            // Total load time
            $maxScore += 30;
            if ($result['total_load_ms'] < 500)
                $score += 30;
            elseif ($result['total_load_ms'] < 1000)
                $score += 25;
            elseif ($result['total_load_ms'] < 2000)
                $score += 15;
            elseif ($result['total_load_ms'] < 3000)
                $score += 8;

            // Page size
            $maxScore += 25;
            $sizeKB = $result['total_size_bytes'] / 1024;
            if ($sizeKB < 100)
                $score += 25;
            elseif ($sizeKB < 300)
                $score += 20;
            elseif ($sizeKB < 500)
                $score += 12;
            elseif ($sizeKB < 1000)
                $score += 5;

            // Gzip (can't measure via file_get_contents, give benefit of doubt for local)
            $maxScore += 20;
            $score += 10; // Neutral

            $result['performance_score'] = $maxScore > 0 ? (int) (($score / $maxScore) * 100) : 0;
        }

        // Save to DB
        $this->db->execute(
            "INSERT INTO supervisor_performance_logs (url, ttfb_ms, total_load_ms, dom_size_bytes, total_size_bytes, gzip_enabled, performance_score)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $url,
                $result['ttfb_ms'],
                $result['total_load_ms'],
                $result['dom_size_bytes'],
                $result['total_size_bytes'],
                $result['gzip_enabled'] ? 1 : 0,
                $result['performance_score'],
            ]
        );

        return $result;
    }

    // ============ PHASE 2: LINK CHECKER ============

    /**
     * Checks all links across published articles for broken links.
     */
    public function runLinkCheck(): array
    {
        $scanId = $this->createScan('links', 'manual');
        $results = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;

        // Get published articles and scan for links
        $articles = $this->db->query(
            "SELECT id, slug, title, content, content_type FROM articles WHERE status = 'published' ORDER BY RAND() LIMIT 20"
        );

        $allLinks = [];

        foreach ($articles as $article) {
            $content = $article['content'] ?? '';
            // Extract all links from content
            preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $linkMatches);

            foreach ($linkMatches[1] as $link) {
                // Skip anchors, mailto, javascript
                if (str_starts_with($link, '#') || str_starts_with($link, 'mailto:') || str_starts_with($link, 'javascript:')) {
                    continue;
                }

                $allLinks[$link] = $allLinks[$link] ?? [
                    'url' => $link,
                    'found_in' => [],
                    'is_internal' => str_starts_with($link, '/') || strpos($link, 'devlync.com') !== false,
                ];
                $allLinks[$link]['found_in'][] = $article['title'];
            }
        }

        // Also scan public pages for links
        $pages = $this->db->query(
            "SELECT url_pattern, page_name FROM supervisor_page_status WHERE page_type = 'public' AND url_pattern NOT LIKE '%{%}%'"
        );
        foreach ($pages as $page) {
            $html = $this->fetchPage($page['url_pattern']);
            if ($html) {
                preg_match_all('/href=["\']([^"\'#]+)["\']/i', $html, $linkMatches);
                foreach ($linkMatches[1] as $link) {
                    if (str_starts_with($link, 'mailto:') || str_starts_with($link, 'javascript:'))
                        continue;
                    $allLinks[$link] = $allLinks[$link] ?? [
                        'url' => $link,
                        'found_in' => [],
                        'is_internal' => str_starts_with($link, '/') || strpos($link, 'devlync.com') !== false,
                    ];
                    $allLinks[$link]['found_in'][] = $page['page_name'];
                }
            }
        }

        // Check unique links (limit to avoid timeout)
        $checked = 0;
        foreach ($allLinks as $link => &$info) {
            if ($checked >= 100)
                break; // Max 100 links per scan

            $checkResult = $this->checkSingleLink($link, $info['is_internal']);
            $info['status_code'] = $checkResult['status_code'];
            $info['is_broken'] = $checkResult['is_broken'];
            $info['response_ms'] = $checkResult['response_ms'];
            $info['error'] = $checkResult['error'];

            $results[] = $info;

            if (!$checkResult['is_broken']) {
                $passed++;
            } elseif ($checkResult['status_code'] >= 500) {
                $failed++;
            } else {
                $warnings++;
            }

            $checked++;
        }

        $this->completeScan($scanId, count($results), $passed, $failed, $warnings, $results);
        $this->logActivity('link_check', "Link check completed: {$passed} OK, {$failed} broken, {$warnings} warnings out of {$checked} links");

        // Generate suggestions for broken links
        foreach ($results as $r) {
            if ($r['is_broken'] ?? false) {
                $this->addSuggestion([
                    'category' => 'content',
                    'priority' => $r['is_internal'] ? 'high' : 'medium',
                    'title' => "Broken link: {$r['url']} (HTTP {$r['status_code']})",
                    'description' => "Found on: " . implode(', ', array_slice($r['found_in'], 0, 3)),
                    'impact_area' => 'SEO & UX',
                    'impact_score' => $r['is_internal'] ? 90 : 60,
                    'effort_score' => 20,
                    'estimated_time' => '5 min',
                    'affected_urls' => [$r['url']],
                ]);
            }
        }

        return [
            'scan_id' => $scanId,
            'total' => count($results),
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'results' => $results,
        ];
    }

    /**
     * Checks if a single link is accessible.
     */
    public function checkSingleLink(string $url, bool $isInternal = false): array
    {
        $result = ['status_code' => 0, 'is_broken' => true, 'response_ms' => 0, 'error' => null];

        $checkUrl = $isInternal && str_starts_with($url, '/') ? $this->getFullUrl($url) : $url;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $checkUrl,
            CURLOPT_NOBODY => true, // HEAD request
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['User-Agent: DevLync-LinkChecker/1.0'],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        curl_exec($ch);
        $result['status_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result['response_ms'] = (int) (curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000);

        if (curl_errno($ch)) {
            $result['error'] = curl_error($ch);
        }

        $result['is_broken'] = ($result['status_code'] === 0 || $result['status_code'] >= 400);

        return $result;
    }

    // ============ PHASE 2: IMAGE AUDITOR ============

    /**
     * Audits images across published articles and public pages.
     */
    public function runImageAudit(): array
    {
        $scanId = $this->createScan('images', 'manual');
        $results = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;

        // Check articles for featured images
        $articles = $this->db->query(
            "SELECT id, title, slug, content_type, featured_image_url FROM articles WHERE status = 'published' LIMIT 50"
        );

        foreach ($articles as $article) {
            $item = [
                'type' => 'article',
                'title' => $article['title'],
                'slug' => $article['slug'],
                'issues' => [],
            ];

            // Check featured image
            if (empty($article['featured_image_url'])) {
                $item['issues'][] = 'Missing featured image';
                $failed++;
            } else {
                $imgPath = ROOT_PATH . '/' . ltrim($article['featured_image_url'], '/');
                if (!file_exists($imgPath)) {
                    $item['issues'][] = "Featured image file not found: {$article['featured_image_url']}";
                    $failed++;
                } else {
                    $sizeKB = filesize($imgPath) / 1024;
                    if ($sizeKB > 500) {
                        $item['issues'][] = "Featured image too large: " . round($sizeKB) . "KB (should be < 500KB)";
                        $warnings++;
                    } else {
                        $passed++;
                    }
                    $item['file_size_kb'] = round($sizeKB);
                }
            }

            $results[] = $item;
        }

        // Scan public pages for images without alt tags
        $pages = $this->db->query(
            "SELECT url_pattern, page_name FROM supervisor_page_status WHERE page_type = 'public' AND url_pattern NOT LIKE '%{%}%'"
        );

        foreach ($pages as $page) {
            $html = $this->fetchPage($page['url_pattern']);
            if (!$html)
                continue;

            preg_match_all('/<img[^>]*>/i', $html, $imgMatches);
            $missingAlt = 0;
            $totalImgs = count($imgMatches[0]);

            foreach ($imgMatches[0] as $img) {
                if (!preg_match('/alt=["\'][^"\']+["\']/i', $img)) {
                    $missingAlt++;
                }
            }

            if ($missingAlt > 0) {
                $results[] = [
                    'type' => 'page',
                    'title' => $page['page_name'],
                    'url' => $page['url_pattern'],
                    'issues' => ["{$missingAlt}/{$totalImgs} images missing alt tags on {$page['page_name']}"],
                ];
                $warnings++;

                $this->addSuggestion([
                    'category' => 'accessibility',
                    'priority' => 'medium',
                    'title' => "{$missingAlt} images missing alt text on {$page['page_name']}",
                    'description' => "Page {$page['url_pattern']} has {$missingAlt} images without alt attributes",
                    'impact_area' => 'Accessibility & SEO',
                    'impact_score' => 65,
                    'effort_score' => 25,
                    'estimated_time' => '10 min',
                    'affected_urls' => [$page['url_pattern']],
                ]);
            }
        }

        $this->completeScan($scanId, count($results), $passed, $failed, $warnings, $results);
        $this->logActivity('image_audit', "Image audit completed: {$passed} OK, {$failed} missing, {$warnings} warnings");

        return [
            'scan_id' => $scanId,
            'total' => count($results),
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'results' => $results,
        ];
    }

    // ============ PHASE 3: WEEKLY REPORT ============

    /**
     * Generates a weekly summary report of all supervisor activity.
     */
    public function generateWeeklyReport(): array
    {
        $weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));

        // Gather weekly stats
        $healthChecks = $this->db->queryOne(
            "SELECT COUNT(*) as total, SUM(is_healthy) as healthy FROM supervisor_health_checks WHERE checked_at >= ?",
            [$weekAgo]
        );

        $errors = $this->db->queryOne(
            "SELECT COUNT(*) as total, SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
             FROM supervisor_errors WHERE first_seen_at >= ?",
            [$weekAgo]
        );

        $suggestions = $this->db->queryOne(
            "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'implemented' THEN 1 ELSE 0 END) as implemented
             FROM supervisor_suggestions WHERE created_at >= ?",
            [$weekAgo]
        );

        $scans = $this->db->queryOne(
            "SELECT COUNT(*) as total, GROUP_CONCAT(DISTINCT scan_type) as types FROM supervisor_scans WHERE created_at >= ?",
            [$weekAgo]
        );

        $incidents = $this->db->queryOne(
            "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
             FROM supervisor_incidents WHERE created_at >= ?",
            [$weekAgo]
        );

        $currentScore = $this->calculateWebsiteScore();

        $reportData = [
            'period' => ['from' => $weekAgo, 'to' => date('Y-m-d H:i:s')],
            'health_checks' => [
                'total' => (int) ($healthChecks['total'] ?? 0),
                'healthy' => (int) ($healthChecks['healthy'] ?? 0),
                'uptime_pct' => ($healthChecks['total'] ?? 0) > 0
                    ? round(((int) $healthChecks['healthy'] / (int) $healthChecks['total']) * 100, 1) : 0,
            ],
            'errors' => [
                'total' => (int) ($errors['total'] ?? 0),
                'critical' => (int) ($errors['critical'] ?? 0),
                'resolved' => (int) ($errors['resolved'] ?? 0),
            ],
            'suggestions' => [
                'total' => (int) ($suggestions['total'] ?? 0),
                'approved' => (int) ($suggestions['approved'] ?? 0),
                'implemented' => (int) ($suggestions['implemented'] ?? 0),
            ],
            'scans' => [
                'total' => (int) ($scans['total'] ?? 0),
                'types' => $scans['types'] ?? '',
            ],
            'incidents' => [
                'total' => (int) ($incidents['total'] ?? 0),
                'resolved' => (int) ($incidents['resolved'] ?? 0),
            ],
            'website_score' => $currentScore,
        ];

        // Build summary text
        $summary = "Weekly Report ({$reportData['period']['from']} to {$reportData['period']['to']}): ";
        $summary .= "Score: {$currentScore}/100. ";
        $summary .= "Health checks: {$reportData['health_checks']['total']} ({$reportData['health_checks']['uptime_pct']}% uptime). ";
        $summary .= "Errors: {$reportData['errors']['total']} ({$reportData['errors']['resolved']} resolved). ";
        $summary .= "Suggestions: {$reportData['suggestions']['total']} ({$reportData['suggestions']['implemented']} implemented). ";
        $summary .= "Scans: {$reportData['scans']['total']}. ";
        $summary .= "Incidents: {$reportData['incidents']['total']} ({$reportData['incidents']['resolved']} resolved).";

        // Save report to DB
        $this->db->execute(
            "INSERT INTO supervisor_reports (report_type, title, overall_score, summary, report_data, issues_found, issues_fixed, issues_pending)
             VALUES ('weekly', ?, ?, ?, ?, ?, ?, ?)",
            [
                'Weekly Report — ' . date('M d, Y'),
                $currentScore,
                $summary,
                json_encode($reportData),
                $reportData['errors']['total'] + $reportData['incidents']['total'],
                $reportData['errors']['resolved'] + $reportData['incidents']['resolved'],
                $reportData['suggestions']['total'] - $reportData['suggestions']['implemented'],
            ]
        );

        $reportId = $this->db->lastInsertId();
        $this->logActivity('weekly_report', "Weekly report generated: score {$currentScore}/100", ['triggered_by' => 'auto']);

        return ['report_id' => $reportId, 'data' => $reportData, 'summary' => $summary];
    }

    // ============ PHASE 4: FULL AUDIT ============

    /**
     * Runs all scan types sequentially and generates a combined report.
     */
    public function runFullAudit(): array
    {
        $startTime = microtime(true);
        $results = [];

        // Run each scan type
        $results['health'] = $this->runFullHealthScan();
        $results['seo'] = $this->runSeoScan();
        $results['performance'] = $this->runPerformanceScan();
        $results['links'] = $this->runLinkCheck();
        $results['images'] = $this->runImageAudit();

        $duration = round(microtime(true) - $startTime, 2);

        // Aggregate stats
        $totalPassed = 0;
        $totalFailed = 0;
        $totalWarnings = 0;
        foreach ($results as $scan) {
            $totalPassed += $scan['passed'] ?? 0;
            $totalFailed += $scan['failed'] ?? 0;
            $totalWarnings += $scan['warnings'] ?? 0;
        }

        $overallScore = $this->calculateWebsiteScore();

        // Save as a report
        $summary = "Full audit completed in {$duration}s. Score: {$overallScore}/100. "
            . "Passed: {$totalPassed}, Failed: {$totalFailed}, Warnings: {$totalWarnings}.";

        $this->db->execute(
            "INSERT INTO supervisor_reports (report_type, title, overall_score, summary, report_data, issues_found, issues_fixed, issues_pending)
             VALUES ('full_audit', ?, ?, ?, ?, ?, 0, ?)",
            [
                'Full Audit — ' . date('M d, Y H:i'),
                $overallScore,
                $summary,
                json_encode($results),
                $totalFailed + $totalWarnings,
                $totalFailed + $totalWarnings,
            ]
        );

        $reportId = $this->db->lastInsertId();
        $this->logActivity('full_audit', $summary);

        return [
            'report_id' => $reportId,
            'overall_score' => $overallScore,
            'duration_seconds' => $duration,
            'passed' => $totalPassed,
            'failed' => $totalFailed,
            'warnings' => $totalWarnings,
            'results' => $results,
            'summary' => $summary,
        ];
    }

    // ============ PHASE 4: TELEGRAM ALERTS ============

    /**
     * Sends a Telegram alert message.
     */
    public function sendTelegramAlert(string $message, string $level = 'info'): bool
    {
        $botToken = $this->getSetting('telegram_bot_token');
        $chatId = $this->getSetting('telegram_chat_id');
        $enabled = $this->getSetting('telegram_alerts_enabled') ?: $this->getSetting('telegram_enabled');

        if ($enabled !== 'true' || empty($botToken) || empty($chatId)) {
            return false;
        }

        $emoji = match ($level) {
            'critical' => '🚨',
            'warning' => '⚠️',
            'success' => '✅',
            default => 'ℹ️',
        };

        $text = "{$emoji} *DevLync Supervisor*\n\n{$message}";

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $success = $httpCode === 200;

        if ($success) {
            $this->logActivity('telegram_alert', "Telegram alert sent: {$level} — " . substr($message, 0, 100));
        } else {
            $this->logActivity('telegram_alert_fail', "Telegram alert failed (HTTP {$httpCode}): " . substr($response, 0, 200));
        }

        return $success;
    }

    // ============ PHASE 5: SECURITY SCAN ============

    /**
     * Runs a security scan checking common vulnerabilities.
     */
    public function runSecurityScan(): array
    {
        $scanId = $this->createScan('security', 'manual');
        $results = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;

        // 1. Check security headers on homepage
        $url = $this->getFullUrl('/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $headerResponse = curl_exec($ch);
        curl_close($ch);

        $requiredHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1',
            'Referrer-Policy' => 'strict-origin',
        ];

        foreach ($requiredHeaders as $header => $expectedValue) {
            $found = stripos($headerResponse, $header) !== false;
            $results[] = [
                'check' => "Security header: {$header}",
                'status' => $found ? 'pass' : 'fail',
                'detail' => $found ? "Present" : "Missing — add to .htaccess or server config",
            ];
            $found ? $passed++ : $failed++;
        }

        // 2. Check sensitive file access
        $sensitiveFiles = ['.env', 'config/database.php', 'config/app.php', '.htaccess'];
        foreach ($sensitiveFiles as $file) {
            $testUrl = $this->getFullUrl('/' . $file);
            $ch = curl_init($testUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_NOBODY => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $blocked = ($code === 403 || $code === 404 || $code === 0);
            $results[] = [
                'check' => "Sensitive file blocked: {$file}",
                'status' => $blocked ? 'pass' : 'fail',
                'detail' => $blocked ? "Blocked (HTTP {$code})" : "EXPOSED! HTTP {$code} — block in .htaccess",
            ];
            $blocked ? $passed++ : $failed++;
        }

        // 3. Check directory listing disabled
        $testDirs = ['/assets/', '/views/', '/models/', '/controllers/'];
        foreach ($testDirs as $dir) {
            $testUrl = $this->getFullUrl($dir);
            $ch = curl_init($testUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $listingDisabled = ($code === 403 || $code === 404 || !str_contains($body ?: '', 'Index of'));
            $results[] = [
                'check' => "Directory listing disabled: {$dir}",
                'status' => $listingDisabled ? 'pass' : 'warn',
                'detail' => $listingDisabled ? "Disabled" : "Directory listing enabled — disable in Apache config",
            ];
            $listingDisabled ? $passed++ : $warnings++;
        }

        // 4. Check HTTPS redirect (production only)
        $isLocal = str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');
        if (!$isLocal) {
            $httpUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'devlync.com') . '/';
            $ch = curl_init($httpUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $redirects = ($code === 301 || $code === 302);
            $results[] = [
                'check' => "HTTP → HTTPS redirect",
                'status' => $redirects ? 'pass' : 'warn',
                'detail' => $redirects ? "Redirects correctly" : "No HTTPS redirect detected",
            ];
            $redirects ? $passed++ : $warnings++;
        }

        // 5. Check PHP version
        $phpVersion = PHP_VERSION;
        $isModern = version_compare($phpVersion, '8.0.0', '>=');
        $results[] = [
            'check' => "PHP version: {$phpVersion}",
            'status' => $isModern ? 'pass' : 'warn',
            'detail' => $isModern ? "PHP 8+ (good)" : "Upgrade to PHP 8+ recommended",
        ];
        $isModern ? $passed++ : $warnings++;

        // 6. Check error display off in production
        $displayErrors = ini_get('display_errors');
        $results[] = [
            'check' => "display_errors setting",
            'status' => ($displayErrors === '0' || $displayErrors === '' || $isLocal) ? 'pass' : 'warn',
            'detail' => ($displayErrors === '0' || $displayErrors === '') ? "Off (good for production)" : "On — disable in production",
        ];
        ($displayErrors === '0' || $displayErrors === '' || $isLocal) ? $passed++ : $warnings++;

        // Generate suggestions for failures
        foreach ($results as $r) {
            if ($r['status'] === 'fail') {
                $this->addSuggestion([
                    'category' => 'security',
                    'priority' => 'high',
                    'title' => $r['check'] . ' — FAILED',
                    'description' => $r['detail'],
                    'impact_area' => 'Security',
                    'impact_score' => 95,
                    'effort_score' => 15,
                    'estimated_time' => '5 min',
                ]);
            }
        }

        $this->completeScan($scanId, count($results), $passed, $failed, $warnings, $results);
        $this->logActivity('security_scan', "Security scan: {$passed} pass, {$failed} fail, {$warnings} warnings");

        return [
            'scan_id' => $scanId,
            'total' => count($results),
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'results' => $results,
        ];
    }

    // ============ PHASE 5: CONTENT QUALITY CHECK ============

    /**
     * Checks content quality across published articles.
     */
    public function runContentQualityCheck(): array
    {
        $scanId = $this->createScan('content', 'manual');
        $results = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;

        $articles = $this->db->query(
            "SELECT id, title, slug, content, excerpt, meta_description, featured_image_url, content_type, word_count, status
             FROM articles WHERE status = 'published' ORDER BY published_at DESC LIMIT 50"
        );

        foreach ($articles as $article) {
            $issues = [];
            $content = $article['content'] ?? '';
            $wordCount = $article['word_count'] ?? str_word_count(strip_tags($content));

            // Check word count
            if ($wordCount < 300) {
                $issues[] = "Too short: {$wordCount} words (minimum 300 recommended)";
            } elseif ($wordCount < 800) {
                $issues[] = "Could be longer: {$wordCount} words (800+ recommended for SEO)";
            }

            // Check meta description
            if (empty($article['meta_description'])) {
                $issues[] = "Missing meta description";
            } elseif (strlen($article['meta_description']) < 50) {
                $issues[] = "Meta description too short: " . strlen($article['meta_description']) . " chars (50-160 recommended)";
            } elseif (strlen($article['meta_description']) > 160) {
                $issues[] = "Meta description too long: " . strlen($article['meta_description']) . " chars (50-160 recommended)";
            }

            // Check excerpt
            if (empty($article['excerpt'])) {
                $issues[] = "Missing excerpt";
            }

            // Check featured image
            if (empty($article['featured_image_url'])) {
                $issues[] = "Missing featured image";
            }

            // Check title length
            $titleLen = strlen($article['title']);
            if ($titleLen < 20) {
                $issues[] = "Title too short: {$titleLen} chars";
            } elseif ($titleLen > 70) {
                $issues[] = "Title too long for SEO: {$titleLen} chars (50-60 ideal)";
            }

            // Check for headings in content
            if (!preg_match('/<h[2-6]/i', $content)) {
                $issues[] = "No subheadings (H2-H6) found in content";
            }

            // Check for internal links
            if (!preg_match('/href=["\'][^"\']*devlync|href=["\']\//i', $content)) {
                $issues[] = "No internal links found in content";
            }

            // Determine status
            $hasCritical = false;
            foreach ($issues as $issue) {
                if (str_contains($issue, 'Missing') || str_contains($issue, 'Too short')) {
                    $hasCritical = true;
                    break;
                }
            }

            if (empty($issues)) {
                $passed++;
            } elseif ($hasCritical) {
                $failed++;
            } else {
                $warnings++;
            }

            $results[] = [
                'type' => 'article',
                'title' => $article['title'],
                'slug' => $article['slug'],
                'content_type' => $article['content_type'],
                'word_count' => $wordCount,
                'issues' => $issues,
                'quality_score' => max(0, 100 - (count($issues) * 15)),
            ];
        }

        // Generate suggestions for worst content
        $worstArticles = array_filter($results, fn($r) => count($r['issues']) >= 3);
        foreach (array_slice($worstArticles, 0, 5) as $r) {
            $this->addSuggestion([
                'category' => 'content',
                'priority' => 'medium',
                'title' => "Improve content quality: {$r['title']}",
                'description' => implode('; ', $r['issues']),
                'impact_area' => 'Content & SEO',
                'impact_score' => 70,
                'effort_score' => 40,
                'estimated_time' => '15 min',
                'affected_urls' => ["/{$r['content_type']}/{$r['slug']}"],
            ]);
        }

        $this->completeScan($scanId, count($results), $passed, $failed, $warnings, $results);
        $this->logActivity('content_quality', "Content quality check: {$passed} good, {$failed} poor, {$warnings} need improvement out of " . count($results) . " articles");

        return [
            'scan_id' => $scanId,
            'total' => count($results),
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'results' => $results,
        ];
    }

    // ============ HELPER: FETCH PAGE CONTENT ============

    /**
     * Fetches page HTML content for analysis.
     */
    private function fetchPage(string $url): ?string
    {
        // PHP built-in server is single-threaded — cURL back to the same server deadlocks.
        // Try XAMPP Apache (port 80) first, then fallback to file_get_contents.
        $urls = [];
        $currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';

        if (strpos($currentHost, ':8000') !== false) {
            $urls[] = 'http://localhost/devlync.com' . $url;
        }
        $urls[] = $scheme . '://' . $currentHost . $basePath . $url;

        $context = stream_context_create([
            'http' => [
                'timeout' => 8,
                'user_agent' => 'DevLync-Supervisor/1.0',
                'ignore_errors' => true,
                'follow_location' => 1,
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        foreach ($urls as $fetchUrl) {
            $html = @file_get_contents($fetchUrl, false, $context);
            if ($html && strlen($html) > 100) {
                return $html;
            }
        }
        return null;
    }
}
