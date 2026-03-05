<?php
declare(strict_types=1);

/**
 * KnowledgeItem Model
 * Scraped and manual knowledge data used by n8n AI article generator.
 */
class KnowledgeItem
{
    /**
     * Returns paginated knowledge items.
     *
     * @param int $page
     * @param int $perPage
     * @return array{items: array, total: int, pages: int}
     */
    public static function getAll(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $total = (int) (Database::getInstance()->queryOne(
            'SELECT COUNT(*) AS cnt FROM knowledge_items'
        )['cnt'] ?? 0);

        $items = Database::getInstance()->query(
            'SELECT id, title, summary, source_type, source_name, source_url,
                    topics, keywords, sentiment, is_processed, created_at
             FROM knowledge_items ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$perPage, $offset]
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) ceil($total / $perPage)];
    }

    /**
     * Returns knowledge items filtered by source type.
     *
     * @param string $type Source type enum value
     * @return array
     */
    public static function getBySourceType(string $type): array
    {
        return Database::getInstance()->query(
            'SELECT id, title, summary, source_name, source_url, topics, created_at
             FROM knowledge_items WHERE source_type = ? ORDER BY created_at DESC',
            [$type]
        );
    }

    /**
     * Inserts a new knowledge item.
     *
     * @param array $data
     * @return int New ID
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO knowledge_items
             (title, content, summary, source_url, source_type, source_name, source_id,
              topics, keywords, entities, sentiment, quality_score, is_processed, processed_at, processing_cost)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['title'],
                $data['content'],
                $data['summary'] ?? null,
                $data['source_url'] ?? null,
                $data['source_type'] ?? 'manual',
                $data['source_name'] ?? null,
                $data['source_id'] ?? null,
                isset($data['topics']) ? json_encode($data['topics']) : null,
                isset($data['keywords']) ? json_encode($data['keywords']) : null,
                isset($data['entities']) ? json_encode($data['entities']) : null,
                $data['sentiment'] ?? null,
                $data['quality_score'] ?? 50,
                isset($data['is_processed']) ? (int) $data['is_processed'] : 1,
                $data['processed_at'] ?? date('Y-m-d H:i:s'),
                $data['processing_cost'] ?? 0,
            ]
        );
        return $db->lastInsertId();
    }

    /**
     * Gets a knowledge item by source_id.
     *
     * @param string $sourceId
     * @return array|null
     */
    public static function getBySourceId(string $sourceId): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT * FROM knowledge_items WHERE source_id = ? LIMIT 1',
            [$sourceId]
        );
    }

    /**
     * Returns knowledge items by IDs.
     *
     * @param array $ids
     * @return array
     */
    public static function getByIds(array $ids): array
    {
        $clean = array_values(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0));
        if (!$clean) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($clean), '?'));
        return Database::getInstance()->query(
            "SELECT * FROM knowledge_items WHERE id IN ({$placeholders}) ORDER BY created_at DESC",
            $clean
        );
    }

    /**
     * Performs a fulltext search on knowledge items.
     *
     * @param string $query
     * @return array
     */
    public static function search(string $query): array
    {
        return Database::getInstance()->query(
            'SELECT id, title, summary, source_type, source_name, created_at
             FROM knowledge_items
             WHERE MATCH(title, content) AGAINST(? IN BOOLEAN MODE)
             ORDER BY created_at DESC LIMIT 20',
            [$query]
        );
    }

    /**
     * Returns recent knowledge items.
     *
     * @param int $limit
     * @return array
     */
    public static function getRecent(int $limit = 10): array
    {
        return Database::getInstance()->query(
            'SELECT id, title, source_type, source_name, sentiment, created_at
             FROM knowledge_items ORDER BY created_at DESC LIMIT ?',
            [$limit]
        );
    }

    /**
     * Checks if a knowledge item with the given source_id already exists.
     *
     * @param string $sourceId
     * @return bool
     */
    public static function existsBySourceId(string $sourceId): bool
    {
        $row = Database::getInstance()->queryOne(
            'SELECT id FROM knowledge_items WHERE source_id = ? LIMIT 1',
            [$sourceId]
        );
        return (bool) $row;
    }

    /**
     * Returns paginated knowledge items with optional filters.
     *
     * @param int         $page
     * @param int         $perPage
     * @param string|null $sourceType
     * @param string|null $sentiment
     * @param string|null $reviewed   '1', '0', or null for all
     * @param string|null $searchQuery
     * @return array{items: array, total: int, pages: int}
     */
    public static function getFiltered(
        int $page = 1,
        int $perPage = 25,
        ?string $sourceType = null,
        ?string $sentiment = null,
        ?string $reviewed = null,
        ?string $searchQuery = null
    ): array {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if ($sourceType !== null && $sourceType !== '') {
            $where[] = 'source_type = ?';
            $params[] = $sourceType;
        }
        if ($sentiment !== null && $sentiment !== '') {
            $where[] = 'sentiment = ?';
            $params[] = $sentiment;
        }
        if ($reviewed !== null && $reviewed !== '') {
            $where[] = 'is_reviewed = ?';
            $params[] = (int) $reviewed;
        }
        if ($searchQuery !== null && $searchQuery !== '') {
            $where[] = '(title LIKE ? OR summary LIKE ? OR source_name LIKE ?)';
            $term = '%' . $searchQuery . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) (Database::getInstance()->queryOne(
            "SELECT COUNT(*) AS cnt FROM knowledge_items $whereClause",
            $params
        )['cnt'] ?? 0);

        $items = Database::getInstance()->query(
            "SELECT id, title, summary, content, source_type, source_name, source_url, source_id,
                    topics, keywords, entities, sentiment, quality_score, is_reviewed, is_processed, created_at
             FROM knowledge_items $whereClause
             ORDER BY created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) ceil($total / max($perPage, 1))];
    }

    /**
     * Returns aggregate stats for the knowledge base.
     *
     * @return array
     */
    public static function getStats(): array
    {
        $db = Database::getInstance();
        $total = (int) ($db->queryOne('SELECT COUNT(*) AS cnt FROM knowledge_items')['cnt'] ?? 0);
        $unreviewed = (int) ($db->queryOne('SELECT COUNT(*) AS cnt FROM knowledge_items WHERE is_reviewed = 0')['cnt'] ?? 0);

        $bySource = $db->query(
            'SELECT source_type, COUNT(*) AS cnt FROM knowledge_items GROUP BY source_type ORDER BY cnt DESC'
        );

        $bySentiment = $db->query(
            'SELECT sentiment, COUNT(*) AS cnt FROM knowledge_items WHERE sentiment IS NOT NULL GROUP BY sentiment ORDER BY cnt DESC'
        );

        return [
            'total' => $total,
            'unreviewed' => $unreviewed,
            'by_source' => $bySource,
            'by_sentiment' => $bySentiment,
        ];
    }

    /**
     * Returns a single knowledge item by ID with all fields.
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT * FROM knowledge_items WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * Deletes a knowledge item by ID.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        return Database::getInstance()->execute(
            'DELETE FROM knowledge_items WHERE id = ?',
            [$id]
        );
    }

    /**
     * Bulk-deletes knowledge items by IDs.
     *
     * @param array $ids
     * @return int Number of deleted rows
     */
    public static function bulkDelete(array $ids): int
    {
        $clean = array_values(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0));
        if (!$clean) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($clean), '?'));
        Database::getInstance()->execute(
            "DELETE FROM knowledge_items WHERE id IN ({$placeholders})",
            $clean
        );
        return count($clean);
    }

    /**
     * Marks a knowledge item as reviewed.
     *
     * @param int $id
     * @return void
     */
    public static function markReviewed(int $id): void
    {
        Database::getInstance()->execute(
            'UPDATE knowledge_items SET is_reviewed = 1 WHERE id = ?',
            [$id]
        );
    }

    /**
     * Detects trending topics from recent knowledge items.
     * Aggregates keywords/topics from items created within given hours and ranks by frequency.
     *
     * @param int $hours  Lookback window in hours
     * @param int $limit  Max trends to return
     * @return array [{topic: string, count: int, sources: string[], sentiment_breakdown: array}]
     */
    public static function detectTrends(int $hours = 48, int $limit = 15): array
    {
        $cutoff = date('Y-m-d H:i:s', time() - ($hours * 3600));
        $items = Database::getInstance()->query(
            'SELECT topics, keywords, source_type, sentiment FROM knowledge_items WHERE created_at >= ? ORDER BY created_at DESC',
            [$cutoff]
        );

        $topicCounts = [];
        $topicSources = [];
        $topicSentiments = [];

        foreach ($items as $item) {
            $topics = json_decode((string) ($item['topics'] ?? '[]'), true) ?: [];
            $keywords = json_decode((string) ($item['keywords'] ?? '[]'), true) ?: [];
            $merged = array_unique(array_map('mb_strtolower', array_merge($topics, $keywords)));
            $sourceType = (string) ($item['source_type'] ?? 'unknown');
            $sentiment = (string) ($item['sentiment'] ?? 'neutral');

            foreach ($merged as $term) {
                $term = trim($term);
                if (mb_strlen($term) < 3) {
                    continue;
                }
                if (!isset($topicCounts[$term])) {
                    $topicCounts[$term] = 0;
                    $topicSources[$term] = [];
                    $topicSentiments[$term] = [];
                }
                $topicCounts[$term]++;
                $topicSources[$term][$sourceType] = true;
                $topicSentiments[$term][$sentiment] = ($topicSentiments[$term][$sentiment] ?? 0) + 1;
            }
        }

        arsort($topicCounts);
        $trends = [];
        $i = 0;
        foreach ($topicCounts as $topic => $count) {
            if ($count < 2) {
                break;
            }
            if ($i >= $limit) {
                break;
            }
            $trends[] = [
                'topic' => $topic,
                'count' => $count,
                'sources' => array_keys($topicSources[$topic]),
                'sentiment_breakdown' => $topicSentiments[$topic],
            ];
            $i++;
        }

        return $trends;
    }

    /**
     * Bulk-marks knowledge items as reviewed.
     *
     * @param array $ids
     * @return void
     */
    public static function bulkMarkReviewed(array $ids): void
    {
        $clean = array_values(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0));
        if (!$clean) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($clean), '?'));
        Database::getInstance()->execute(
            "UPDATE knowledge_items SET is_reviewed = 1 WHERE id IN ({$placeholders})",
            $clean
        );
    }
}
