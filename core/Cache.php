<?php
declare(strict_types=1);

/**
 * File-Based Cache
 * Stores serialized PHP data in /cache directory.
 * Cache keys are MD5-hashed for safe filenames.
 */
class Cache
{
    private string $cacheDir;

    /**
     * Initializes cache with the directory path.
     *
     * @param string $cacheDir Absolute path to cache directory
     */
    public function __construct(string $cacheDir = '')
    {
        $this->cacheDir = $cacheDir ?: CACHE_PATH;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Retrieves a cached value by key.
     * Returns null if not found or expired.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize((string) file_get_contents($file));
        if (!is_array($data) || $data['expires'] < time()) {
            @unlink($file);
            return null;
        }

        return $data['value'];
    }

    /**
     * Stores a value in cache.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl   Time-to-live in seconds
     * @return bool
     */
    public function set(string $key, mixed $value, int $ttl = CACHE_TTL_ARTICLE): bool
    {
        $file = $this->getFilePath($key);
        $data = serialize([
            'key' => $key,
            'expires' => time() + $ttl,
            'value' => $value,
        ]);
        return (bool) file_put_contents($file, $data, LOCK_EX);
    }

    /**
     * Deletes a single cache entry.
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }

    /**
     * Deletes cache entries whose keys start with the given prefix.
     *
     * @param string $prefix
     * @return int Number of files deleted
     */
    public function deleteByPrefix(string $prefix): int
    {
        $hash = md5($prefix);
        $pattern = $this->cacheDir . '/cache_' . substr($hash, 0, 4) . '*.cache';
        // Fallback: scan all and match
        $files = glob($this->cacheDir . '/cache_*.cache') ?: [];
        $deleted = 0;
        foreach ($files as $file) {
            $stored = @unserialize((string) file_get_contents($file));
            if (isset($stored['key']) && str_starts_with($stored['key'], $prefix)) {
                unlink($file);
                $deleted++;
            }
        }
        return $deleted;
    }

    /**
     * Clears ALL cache files in the cache directory.
     *
     * @return int Number of files deleted
     */
    public function clear(): int
    {
        $files = glob($this->cacheDir . '/cache_*.cache') ?: [];
        $deleted = 0;
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    /**
     * Deletes cache files for a specific URL path.
     *
     * @param string $urlPath e.g. '/reviews/cursor-ide-review-2026'
     * @return void
     */
    public function deleteForUrl(string $urlPath): void
    {
        $this->delete($urlPath);
    }

    /**
     * Returns the cache file path for a given key.
     *
     * @param string $key
     * @return string
     */
    private function getFilePath(string $key): string
    {
        return $this->cacheDir . '/cache_' . md5($key) . '.cache';
    }
}
