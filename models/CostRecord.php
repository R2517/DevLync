<?php
declare(strict_types=1);

/**
 * CostRecord Model
 * Tracks AI API spending per article and per provider.
 */
class CostRecord
{
    /**
     * Creates a new cost record.
     *
     * @param array $data
     * @return int New ID
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO cost_records (article_id, step, model, provider, input_tokens, output_tokens, cost)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['article_id'] ?? null,
                $data['step'],
                $data['model'],
                $data['provider'],
                $data['input_tokens'] ?? 0,
                $data['output_tokens'] ?? 0,
                $data['cost'] ?? 0,
            ]
        );
        return $db->lastInsertId();
    }

    /**
     * Returns the total cost incurred today.
     *
     * @return float
     */
    public static function getTotalToday(): float
    {
        $row = Database::getInstance()->queryOne(
            'SELECT COALESCE(SUM(cost), 0) AS total FROM cost_records
             WHERE DATE(created_at) = CURDATE()'
        );
        return (float) ($row['total'] ?? 0);
    }

    /**
     * Returns the total cost for the current week.
     *
     * @return float
     */
    public static function getTotalThisWeek(): float
    {
        $row = Database::getInstance()->queryOne(
            'SELECT COALESCE(SUM(cost), 0) AS total FROM cost_records
             WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)'
        );
        return (float) ($row['total'] ?? 0);
    }

    /**
     * Returns the total cost for the current calendar month.
     *
     * @return float
     */
    public static function getTotalThisMonth(): float
    {
        $row = Database::getInstance()->queryOne(
            'SELECT COALESCE(SUM(cost), 0) AS total FROM cost_records
             WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())'
        );
        return (float) ($row['total'] ?? 0);
    }

    /**
     * Returns all cost records for a specific article.
     *
     * @param int $articleId
     * @return array
     */
    public static function getByArticle(int $articleId): array
    {
        return Database::getInstance()->query(
            'SELECT id, step, model, provider, input_tokens, output_tokens, cost, created_at
             FROM cost_records WHERE article_id = ? ORDER BY created_at ASC',
            [$articleId]
        );
    }

    /**
     * Returns daily cost breakdown for the past N days.
     *
     * @param int $days
     * @return array
     */
    public static function getDailyBreakdown(int $days = 30): array
    {
        return Database::getInstance()->query(
            'SELECT DATE(created_at) AS day, provider,
                    SUM(cost) AS total_cost,
                    COUNT(DISTINCT article_id) AS articles
             FROM cost_records
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(created_at), provider
             ORDER BY day DESC',
            [$days]
        );
    }
}
