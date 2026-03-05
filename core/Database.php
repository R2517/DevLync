<?php
declare(strict_types=1);

/**
 * Database Singleton Wrapper
 * Wraps PDO with helper methods. All queries use prepared statements.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Private constructor — use getInstance() instead.
     */
    private function __construct()
    {
        $this->pdo = createDatabaseConnection();
    }

    /**
     * Returns the singleton Database instance.
     *
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Executes a SELECT query and returns all matching rows.
     *
     * @param string $sql   Prepared SQL statement
     * @param array  $params Bound parameters
     * @return array
     */
    public function query(string $sql, array $params = []): array
    {
        $start = microtime(true);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $this->logSlowQuery($sql, microtime(true) - $start);
        return $result;
    }

    /**
     * Executes a SELECT query and returns a single row.
     *
     * @param string $sql
     * @param array  $params
     * @return array|null
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $start = microtime(true);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch() ?: null;
        $this->logSlowQuery($sql, microtime(true) - $start);
        return $result;
    }

    /**
     * Executes INSERT / UPDATE / DELETE and returns affected row count.
     *
     * @param string $sql
     * @param array  $params
     * @return int  Number of affected rows
     */
    public function execute(string $sql, array $params = []): int
    {
        $start = microtime(true);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logSlowQuery($sql, microtime(true) - $start);
        return $stmt->rowCount();
    }

    /**
     * Returns the last auto-increment ID after an INSERT.
     *
     * @return int
     */
    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Begins a database transaction.
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commits a database transaction.
     *
     * @return void
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Rolls back a database transaction.
     *
     * @return void
     */
    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Logs queries that take longer than 1 second.
     *
     * @param string $sql
     * @param float  $duration
     * @return void
     */
    private function logSlowQuery(string $sql, float $duration): void
    {
        if ($duration > 1.0) {
            $logFile = ROOT_PATH . '/logs/slow_queries.log';
            $entry = sprintf(
                "[%s] Duration: %.4fs | SQL: %s\n",
                date('Y-m-d H:i:s'),
                $duration,
                preg_replace('/\s+/', ' ', trim($sql))
            );
            @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        }
    }
}
