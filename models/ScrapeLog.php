<?php
declare(strict_types=1);

/**
 * ScrapeLog Model
 * Tracks n8n scraping sessions and their outcomes.
 */
class ScrapeLog
{
    /**
     * Creates a new scrape log entry.
     *
     * @param array $data
     * @return int New ID
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO scrape_logs
             (session_id, source_type, query, items_found, items_saved, items_skipped,
              status, error_message, duration_seconds)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['session_id'] ?? null,
                $data['source_type'],
                $data['query'] ?? null,
                $data['items_found'] ?? 0,
                $data['items_saved'] ?? 0,
                $data['items_skipped'] ?? 0,
                $data['status'] ?? 'success',
                $data['error_message'] ?? null,
                $data['duration_seconds'] ?? 0,
            ]
        );
        return $db->lastInsertId();
    }

    /**
     * Returns the most recent scrape logs.
     *
     * @param int $limit
     * @return array
     */
    public static function getRecent(int $limit = 20): array
    {
        return Database::getInstance()->query(
            'SELECT id, session_id, source_type, query, items_found, items_saved,
                    items_skipped, status, duration_seconds, created_at
             FROM scrape_logs ORDER BY created_at DESC LIMIT ?',
            [$limit]
        );
    }

    /**
     * Returns all logs for a specific session ID.
     *
     * @param string $sessionId
     * @return array
     */
    public static function getBySession(string $sessionId): array
    {
        return Database::getInstance()->query(
            'SELECT id, source_type, query, items_found, items_saved,
                    items_skipped, status, error_message, duration_seconds, created_at
             FROM scrape_logs WHERE session_id = ? ORDER BY created_at ASC',
            [$sessionId]
        );
    }

    /**
     * Returns aggregate stats for scrape logs.
     *
     * @return array
     */
    public static function getStats(): array
    {
        $db = Database::getInstance();
        $total = (int) ($db->queryOne('SELECT COUNT(*) AS cnt FROM scrape_logs')['cnt'] ?? 0);
        $totalSuccess = (int) ($db->queryOne("SELECT COUNT(*) AS cnt FROM scrape_logs WHERE status = 'success'")['cnt'] ?? 0);
        $totalFailed = (int) ($db->queryOne("SELECT COUNT(*) AS cnt FROM scrape_logs WHERE status = 'error'")['cnt'] ?? 0);
        $totalItems = (int) ($db->queryOne('SELECT COALESCE(SUM(items_saved), 0) AS cnt FROM scrape_logs')['cnt'] ?? 0);

        $bySource = $db->query(
            'SELECT source_type,
                    COUNT(*) AS sessions,
                    SUM(items_found) AS total_found,
                    SUM(items_saved) AS total_saved,
                    SUM(items_skipped) AS total_skipped,
                    SUM(CASE WHEN status = \'success\' THEN 1 ELSE 0 END) AS success_count,
                    SUM(CASE WHEN status = \'error\' THEN 1 ELSE 0 END) AS error_count
             FROM scrape_logs GROUP BY source_type ORDER BY sessions DESC'
        );

        return [
            'total_sessions' => $total,
            'total_success' => $totalSuccess,
            'total_failed' => $totalFailed,
            'total_items_saved' => $totalItems,
            'by_source' => $bySource,
        ];
    }

    /**
     * Returns unique session IDs with their summary, most recent first.
     *
     * @param int $limit
     * @return array
     */
    public static function getSessionSummaries(int $limit = 50): array
    {
        return Database::getInstance()->query(
            'SELECT session_id,
                    MIN(created_at) AS started_at,
                    COUNT(*) AS source_count,
                    SUM(items_found) AS total_found,
                    SUM(items_saved) AS total_saved,
                    SUM(items_skipped) AS total_skipped,
                    SUM(CASE WHEN status = \'error\' THEN 1 ELSE 0 END) AS error_count,
                    MAX(duration_seconds) AS max_duration
             FROM scrape_logs
             WHERE session_id IS NOT NULL
             GROUP BY session_id
             ORDER BY started_at DESC
             LIMIT ?',
            [$limit]
        );
    }
}
