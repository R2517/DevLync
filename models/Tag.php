<?php
declare(strict_types=1);

/**
 * Tag Model
 * Article tagging for cross-linking and discovery.
 */
class Tag
{
    /**
     * Gets a tag by slug.
     *
     * @param string $slug
     * @return array|null
     */
    public static function getBySlug(string $slug): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT id, name, slug, articles_count FROM tags WHERE slug = ? LIMIT 1',
            [$slug]
        );
    }

    /**
     * Returns all tags ordered by article count.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return Database::getInstance()->query(
            'SELECT id, name, slug, articles_count FROM tags ORDER BY articles_count DESC'
        );
    }

    /**
     * Finds an existing tag by name or creates a new one.
     *
     * @param string $name
     * @return int Tag ID
     */
    public static function findOrCreate(string $name): int
    {
        $slug = self::toSlug($name);
        $existing = Database::getInstance()->queryOne(
            'SELECT id FROM tags WHERE slug = ? LIMIT 1',
            [$slug]
        );
        if ($existing) {
            return (int) $existing['id'];
        }
        Database::getInstance()->execute(
            'INSERT INTO tags (name, slug) VALUES (?, ?)',
            [$name, $slug]
        );
        return Database::getInstance()->lastInsertId();
    }

    /**
     * Returns all tags associated with an article.
     *
     * @param int $articleId
     * @return array
     */
    public static function getForArticle(int $articleId): array
    {
        return Database::getInstance()->query(
            'SELECT t.id, t.name, t.slug FROM tags t
             INNER JOIN article_tags at ON t.id = at.tag_id
             WHERE at.article_id = ?
             ORDER BY t.name ASC',
            [$articleId]
        );
    }

    /**
     * Syncs tag associations for an article (replaces all existing ones).
     *
     * @param int   $articleId
     * @param array $tagIds
     * @return void
     */
    public static function syncForArticle(int $articleId, array $tagIds): void
    {
        $db = Database::getInstance();
        $db->execute('DELETE FROM article_tags WHERE article_id = ?', [$articleId]);

        foreach ($tagIds as $tagId) {
            $db->execute(
                'INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (?, ?)',
                [$articleId, (int) $tagId]
            );
            $db->execute(
                'UPDATE tags SET articles_count = articles_count + 1 WHERE id = ?',
                [(int) $tagId]
            );
        }
    }

    /**
     * Converts a tag name to a URL-friendly slug.
     *
     * @param string $name
     * @return string
     */
    private static function toSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
}
