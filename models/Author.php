<?php
declare(strict_types=1);

/**
 * Author Model
 * EEAT author entity management.
 */
class Author
{
    /**
     * Gets a single author by ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT id, name, slug, bio, short_bio, expertise, avatar_url,
                    social_twitter, social_linkedin, social_github, social_website,
                    is_active, articles_count, created_at
             FROM authors WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * Gets a single author by slug.
     *
     * @param string $slug
     * @return array|null
     */
    public static function getBySlug(string $slug): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT id, name, slug, bio, short_bio, expertise, avatar_url,
                    social_twitter, social_linkedin, social_github, social_website,
                    is_active, articles_count
             FROM authors WHERE slug = ? AND is_active = 1 LIMIT 1',
            [$slug]
        );
    }

    /**
     * Returns all active authors.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return Database::getInstance()->query(
            'SELECT id, name, slug, short_bio, avatar_url, articles_count
             FROM authors WHERE is_active = 1 ORDER BY name ASC'
        );
    }

    /**
     * Increments the article count for an author.
     *
     * @param int $id
     * @return void
     */
    public static function incrementArticleCount(int $id): void
    {
        Database::getInstance()->execute(
            'UPDATE authors SET articles_count = articles_count + 1 WHERE id = ?',
            [$id]
        );
    }
}
