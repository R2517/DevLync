<?php
declare(strict_types=1);

/**
 * CompetitorFeed Model
 * Manages discovered RSS/Atom feeds from competitor sites.
 */
class CompetitorFeed
{
    /**
     * Create a new discovered feed.
     *
     * @param array $data
     * @return int Insert ID
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO competitor_feeds (competitor_id, feed_url, feed_type, feed_title, is_enabled)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE feed_title = VALUES(feed_title), feed_type = VALUES(feed_type)',
            [
                (int) $data['competitor_id'],
                (string) $data['feed_url'],
                (string) ($data['feed_type'] ?? 'rss'),
                $data['feed_title'] ?? null,
                (int) ($data['is_enabled'] ?? 0),
            ]
        );
        return (int) $db->lastInsertId();
    }

    /**
     * Get all feeds for a competitor.
     *
     * @param int $competitorId
     * @return array
     */
    public static function getByCompetitor(int $competitorId): array
    {
        return Database::getInstance()->query(
            'SELECT * FROM competitor_feeds WHERE competitor_id = ? ORDER BY is_enabled DESC, created_at DESC',
            [$competitorId]
        );
    }

    /**
     * Get all enabled feeds for scraping.
     *
     * @return array
     */
    public static function getEnabledFeeds(): array
    {
        return Database::getInstance()->query(
            'SELECT cf.*, cs.domain 
             FROM competitor_feeds cf
             JOIN competitor_sites cs ON cs.id = cf.competitor_id
             WHERE cf.is_enabled = 1 AND cs.is_enabled = 1
             ORDER BY cs.traffic DESC'
        );
    }

    /**
     * Toggle feed enabled status.
     *
     * @param int  $id
     * @param bool $enabled
     * @return void
     */
    public static function setEnabled(int $id, bool $enabled): void
    {
        Database::getInstance()->execute(
            'UPDATE competitor_feeds SET is_enabled = ? WHERE id = ?',
            [$enabled ? 1 : 0, $id]
        );
    }

    /**
     * Bulk enable feeds.
     *
     * @param array $ids
     * @return void
     */
    public static function bulkEnable(array $ids): void
    {
        $clean = array_values(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0));
        if (!$clean) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($clean), '?'));
        Database::getInstance()->execute(
            "UPDATE competitor_feeds SET is_enabled = 1 WHERE id IN ({$placeholders})",
            $clean
        );
    }

    /**
     * Bulk disable feeds.
     *
     * @param array $ids
     * @return void
     */
    public static function bulkDisable(array $ids): void
    {
        $clean = array_values(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0));
        if (!$clean) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($clean), '?'));
        Database::getInstance()->execute(
            "UPDATE competitor_feeds SET is_enabled = 0 WHERE id IN ({$placeholders})",
            $clean
        );
    }

    /**
     * Update after scrape attempt.
     *
     * @param int         $id
     * @param int         $itemsFound
     * @param string|null $error
     * @return void
     */
    public static function updateAfterScrape(int $id, int $itemsFound, ?string $error = null): void
    {
        if ($error !== null) {
            Database::getInstance()->execute(
                'UPDATE competitor_feeds SET error_count = error_count + 1, last_error = ?, last_scraped_at = NOW() WHERE id = ?',
                [mb_substr($error, 0, 500), $id]
            );
        } else {
            Database::getInstance()->execute(
                'UPDATE competitor_feeds SET items_found = items_found + ?, last_scraped_at = NOW(), last_error = NULL WHERE id = ?',
                [$itemsFound, $id]
            );
        }
    }

    /**
     * Get paginated feeds with competitor info.
     *
     * @param int         $page
     * @param int         $perPage
     * @param bool|null   $enabledOnly
     * @param string|null $search
     * @return array{items: array, total: int, pages: int}
     */
    public static function getFiltered(
        int $page = 1,
        int $perPage = 50,
        ?bool $enabledOnly = null,
        ?string $search = null
    ): array {
        $where = [];
        $params = [];

        if ($enabledOnly !== null) {
            $where[] = 'cf.is_enabled = ?';
            $params[] = $enabledOnly ? 1 : 0;
        }

        if ($search !== null && $search !== '') {
            $where[] = '(cs.domain LIKE ? OR cf.feed_url LIKE ? OR cf.feed_title LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $db = Database::getInstance();

        $countRow = $db->query(
            "SELECT COUNT(*) as cnt FROM competitor_feeds cf JOIN competitor_sites cs ON cs.id = cf.competitor_id {$whereClause}",
            $params
        );
        $total = (int) ($countRow[0]['cnt'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $items = $db->query(
            "SELECT cf.*, cs.domain, cs.traffic, cs.competition_level
             FROM competitor_feeds cf
             JOIN competitor_sites cs ON cs.id = cf.competitor_id
             {$whereClause}
             ORDER BY cf.is_enabled DESC, cs.traffic DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => $pages];
    }
}
