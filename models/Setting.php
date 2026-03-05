<?php
declare(strict_types=1);

/**
 * Setting Model
 * Access site-wide configuration stored in the settings table.
 */
class Setting
{
    private static array $cache = [];

    /**
     * Gets a single setting value by key.
     *
     * @param string $key Setting key
     * @return string|null
     */
    public static function get(string $key): ?string
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $row = Database::getInstance()->queryOne(
            'SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1',
            [$key]
        );

        $value = $row ? $row['setting_value'] : null;
        self::$cache[$key] = $value;
        return $value;
    }

    /**
     * Sets a single setting value.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public static function set(string $key, string $value): void
    {
        Database::getInstance()->execute(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, $value]
        );
        self::$cache[$key] = $value;
    }

    /**
     * Returns ALL settings as a key-value array.
     *
     * @return array<string, string>
     */
    public static function getAll(): array
    {
        $rows = Database::getInstance()->query('SELECT setting_key, setting_value FROM settings');
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }

    /**
     * Returns multiple settings by their keys.
     *
     * @param array $keys
     * @return array<string, string>
     */
    public static function getMultiple(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $rows = Database::getInstance()->query(
            "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)",
            $keys
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }

    /**
     * Returns a setting value decoded as JSON.
     *
     * @param string $key
     * @return mixed
     */
    public static function getJson(string $key): mixed
    {
        $value = self::get($key);
        return $value ? json_decode($value, true) : null;
    }
}
