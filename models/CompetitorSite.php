<?php
declare(strict_types=1);

/**
 * CompetitorSite Model
 * Manages competitor domains imported from CSV and their RSS feed discovery.
 */
class CompetitorSite
{
    /**
     * Import competitors from CSV file.
     *
     * @param string $csvPath Absolute path to the CSV file
     * @return array{imported: int, skipped: int, errors: int}
     */
    public static function importFromCsv(string $csvPath): array
    {
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => 0];

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            return $result;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return $result;
        }

        $header = array_map('trim', array_map(function ($h) {
            return str_replace('"', '', $h);
        }, $header));

        $db = Database::getInstance();
        $sql = 'INSERT INTO competitor_sites (domain, competition_level, common_keywords, se_keywords, traffic, costs, paid_keywords, rss_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE competition_level = VALUES(competition_level), common_keywords = VALUES(common_keywords),
                se_keywords = VALUES(se_keywords), traffic = VALUES(traffic), costs = VALUES(costs), paid_keywords = VALUES(paid_keywords)';

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 7) {
                $result['errors']++;
                continue;
            }

            $domain = trim(str_replace('"', '', $row[0]));
            if ($domain === '' || $domain === 'Domain') {
                continue;
            }

            $competitionLevel = (float) str_replace('"', '', $row[1]);
            $commonKeywords = (int) str_replace(['"', ','], '', $row[2]);
            $seKeywords = (int) str_replace(['"', ','], '', $row[3]);
            $traffic = (int) str_replace(['"', ','], '', $row[4]);
            $costs = (int) str_replace(['"', ','], '', $row[5]);
            $paidKeywords = (int) str_replace(['"', ','], '', $row[6]);

            try {
                $db->execute($sql, [
                    $domain,
                    $competitionLevel,
                    $commonKeywords,
                    $seKeywords,
                    $traffic,
                    $costs,
                    $paidKeywords,
                    'pending',
                ]);
                $result['imported']++;
            } catch (Throwable $e) {
                $result['errors']++;
            }
        }

        fclose($handle);
        return $result;
    }

    /**
     * Get paginated list of competitor sites.
     *
     * @param int         $page
     * @param int         $perPage
     * @param string|null $rssStatus Filter by rss_status
     * @param string|null $search    Search domain
     * @param string      $orderBy   Column to order by
     * @param string      $orderDir  ASC or DESC
     * @return array{items: array, total: int, pages: int}
     */
    public static function getFiltered(
        int $page = 1,
        int $perPage = 50,
        ?string $rssStatus = null,
        ?string $search = null,
        string $orderBy = 'traffic',
        string $orderDir = 'DESC'
    ): array {
        $where = [];
        $params = [];

        if ($rssStatus !== null && $rssStatus !== '') {
            $where[] = 'cs.rss_status = ?';
            $params[] = $rssStatus;
        }

        if ($search !== null && $search !== '') {
            $where[] = 'cs.domain LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $allowedOrder = ['traffic', 'competition_level', 'se_keywords', 'common_keywords', 'domain', 'rss_status', 'created_at'];
        if (!in_array($orderBy, $allowedOrder, true)) {
            $orderBy = 'traffic';
        }
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $db = Database::getInstance();

        $countRow = $db->query(
            "SELECT COUNT(*) as cnt FROM competitor_sites cs {$whereClause}",
            $params
        );
        $total = (int) ($countRow[0]['cnt'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $items = $db->query(
            "SELECT cs.*, 
                    (SELECT COUNT(*) FROM competitor_feeds cf WHERE cf.competitor_id = cs.id) as feed_count,
                    (SELECT COUNT(*) FROM competitor_feeds cf WHERE cf.competitor_id = cs.id AND cf.is_enabled = 1) as active_feeds
             FROM competitor_sites cs
             {$whereClause}
             ORDER BY cs.{$orderBy} {$orderDir}
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => $pages];
    }

    /**
     * Get stats summary.
     *
     * @return array
     */
    public static function getStats(): array
    {
        $db = Database::getInstance();

        $total = $db->query('SELECT COUNT(*) as cnt FROM competitor_sites');
        $byStatus = $db->query('SELECT rss_status, COUNT(*) as cnt FROM competitor_sites GROUP BY rss_status');
        $feedStats = $db->query(
            'SELECT COUNT(*) as total_feeds, SUM(is_enabled) as active_feeds FROM competitor_feeds'
        );
        $topTraffic = $db->query(
            'SELECT domain, traffic, rss_status FROM competitor_sites ORDER BY traffic DESC LIMIT 10'
        );

        return [
            'total' => (int) ($total[0]['cnt'] ?? 0),
            'by_status' => $byStatus,
            'total_feeds' => (int) ($feedStats[0]['total_feeds'] ?? 0),
            'active_feeds' => (int) ($feedStats[0]['active_feeds'] ?? 0),
            'top_traffic' => $topTraffic,
        ];
    }

    /**
     * Get batch of pending sites for RSS discovery.
     *
     * @param int $limit
     * @return array
     */
    public static function getPendingForDiscovery(int $limit = 50): array
    {
        return Database::getInstance()->query(
            'SELECT id, domain FROM competitor_sites WHERE rss_status = ? AND is_enabled = 1 ORDER BY traffic DESC LIMIT ?',
            ['pending', $limit]
        );
    }

    /**
     * Update RSS discovery status for a site.
     *
     * @param int    $id
     * @param string $status
     * @return void
     */
    public static function updateRssStatus(int $id, string $status): void
    {
        Database::getInstance()->execute(
            'UPDATE competitor_sites SET rss_status = ?, rss_checked_at = NOW() WHERE id = ?',
            [$status, $id]
        );
    }

    /**
     * Get a single site by ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        $rows = Database::getInstance()->query(
            'SELECT * FROM competitor_sites WHERE id = ?',
            [$id]
        );
        return $rows[0] ?? null;
    }

    /**
     * Toggle enabled status.
     *
     * @param int  $id
     * @param bool $enabled
     * @return void
     */
    public static function setEnabled(int $id, bool $enabled): void
    {
        Database::getInstance()->execute(
            'UPDATE competitor_sites SET is_enabled = ? WHERE id = ?',
            [$enabled ? 1 : 0, $id]
        );
    }

    /**
     * Reset all sites to pending for re-discovery.
     *
     * @return int Number of rows reset
     */
    public static function resetAllToPending(): int
    {
        return Database::getInstance()->execute(
            "UPDATE competitor_sites SET rss_status = 'pending', rss_checked_at = NULL WHERE rss_status IN ('no_feed', 'error')"
        );
    }
}
