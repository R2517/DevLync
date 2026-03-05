<?php
declare(strict_types=1);

/**
 * Database Configuration
 * MySQL + PDO connection settings for Hostinger Cloud
 */

// ------- DATABASE CREDENTIALS -------
// Update these values when deploying to Hostinger
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'devlync_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Creates and returns a PDO database connection instance.
 * Uses DSN with utf8mb4 charset for full Unicode and emoji support.
 *
 * @return PDO
 * @throws PDOException if connection fails
 */
function createDatabaseConnection(): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ];

    return new PDO($dsn, DB_USER, DB_PASS, $options);
}
