<?php
declare(strict_types=1);

/**
 * Category Model
 * Topic organization and pillar page management.
 */
class Category
{
    /**
     * Gets a category by ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT id, name, slug, description, meta_title, meta_description,
                    parent_id, icon, sort_order, is_active, articles_count
             FROM categories WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * Gets a category by slug.
     *
     * @param string $slug
     * @return array|null
     */
    public static function getBySlug(string $slug): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT id, name, slug, description, meta_title, meta_description,
                    parent_id, icon, sort_order, is_active, articles_count
             FROM categories WHERE slug = ? LIMIT 1',
            [$slug]
        );
    }

    /**
     * Returns all categories ordered by sort_order.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return Database::getInstance()->query(
            'SELECT id, name, slug, description, icon, sort_order, articles_count
             FROM categories ORDER BY sort_order ASC'
        );
    }

    /**
     * Returns only active categories.
     *
     * @return array
     */
    public static function getActive(): array
    {
        return Database::getInstance()->query(
            'SELECT id, name, slug, description, icon, sort_order, articles_count
             FROM categories WHERE is_active = 1 ORDER BY sort_order ASC'
        );
    }

    /**
     * Increments the article count for a category.
     *
     * @param int $id
     * @return void
     */
    public static function incrementArticleCount(int $id): void
    {
        Database::getInstance()->execute(
            'UPDATE categories SET articles_count = articles_count + 1 WHERE id = ?',
            [$id]
        );
    }
}
