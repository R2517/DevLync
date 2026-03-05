<?php
declare(strict_types=1);

/**
 * Article Model
 * Core content model. Handles all 4 content types: blog, review, comparison, news.
 */
class Article
{
    /**
     * Gets a full article by ID with author and category joined.
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT a.*, au.name AS author_name, au.slug AS author_slug,
                    au.short_bio AS author_bio, au.avatar_url AS author_avatar,
                    c.name AS category_name, c.slug AS category_slug
             FROM articles a
             LEFT JOIN authors au ON a.author_id = au.id
             LEFT JOIN categories c ON a.category_id = c.id
             WHERE a.id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * Gets a full article by slug for public display.
     *
     * @param string $slug
     * @return array|null
     */
    public static function getBySlug(string $slug): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT a.*, au.name AS author_name, au.slug AS author_slug,
                    au.short_bio AS author_bio, au.avatar_url AS author_avatar,
                    au.social_twitter, au.social_linkedin, au.expertise,
                    c.name AS category_name, c.slug AS category_slug
             FROM articles a
             LEFT JOIN authors au ON a.author_id = au.id
             LEFT JOIN categories c ON a.category_id = c.id
             WHERE a.slug = ? AND a.status = \'published\' LIMIT 1',
            [$slug]
        );
    }

    /**
     * Returns paginated published articles by content type.
     *
     * @param string $type    Content type: blog|review|comparison|news
     * @param int    $page    Current page (1-indexed)
     * @param int    $perPage Articles per page
     * @return array{items: array, total: int, pages: int}
     */
    public static function getPublished(string $type, int $page = 1, int $perPage = POSTS_PER_PAGE): array
    {
        $offset = ($page - 1) * $perPage;
        $total = (int) (Database::getInstance()->queryOne(
            'SELECT COUNT(*) AS cnt FROM articles WHERE content_type = ? AND status = \'published\'',
            [$type]
        )['cnt'] ?? 0);

        $items = Database::getInstance()->query(
            'SELECT a.id, a.title, a.slug, a.content_type, a.excerpt, a.featured_image_url,
                    a.featured_image_alt, a.overall_rating, a.reading_time, a.word_count,
                    a.published_at, a.focus_keyword,
                    au.name AS author_name, au.slug AS author_slug,
                    c.name AS category_name, c.slug AS category_slug
             FROM articles a
             LEFT JOIN authors au ON a.author_id = au.id
             LEFT JOIN categories c ON a.category_id = c.id
             WHERE a.content_type = ? AND a.status = \'published\'
             ORDER BY a.published_at DESC
             LIMIT ? OFFSET ?',
            [$type, $perPage, $offset]
        );

        return [
            'items' => $items,
            'total' => $total,
            'pages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Returns paginated articles for a specific category.
     *
     * @param int $categoryId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByCategory(int $categoryId, int $page = 1, int $perPage = POSTS_PER_PAGE): array
    {
        $offset = ($page - 1) * $perPage;
        $total = (int) (Database::getInstance()->queryOne(
            'SELECT COUNT(*) AS cnt FROM articles WHERE category_id = ? AND status = \'published\'',
            [$categoryId]
        )['cnt'] ?? 0);

        $items = Database::getInstance()->query(
            'SELECT a.id, a.title, a.slug, a.content_type, a.excerpt, a.featured_image_url,
                    a.featured_image_alt, a.overall_rating, a.reading_time, a.published_at,
                    au.name AS author_name, au.slug AS author_slug,
                    c.name AS category_name, c.slug AS category_slug
             FROM articles a
             LEFT JOIN authors au ON a.author_id = au.id
             LEFT JOIN categories c ON a.category_id = c.id
             WHERE a.category_id = ? AND a.status = \'published\'
             ORDER BY a.published_at DESC
             LIMIT ? OFFSET ?',
            [$categoryId, $perPage, $offset]
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) ceil($total / $perPage)];
    }

    /**
     * Returns paginated articles for a specific tag.
     *
     * @param int $tagId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByTag(int $tagId, int $page = 1, int $perPage = POSTS_PER_PAGE): array
    {
        $offset = ($page - 1) * $perPage;
        $total = (int) (Database::getInstance()->queryOne(
            'SELECT COUNT(*) AS cnt FROM articles a
             INNER JOIN article_tags at ON a.id = at.article_id
             WHERE at.tag_id = ? AND a.status = \'published\'',
            [$tagId]
        )['cnt'] ?? 0);

        $items = Database::getInstance()->query(
            'SELECT a.id, a.title, a.slug, a.content_type, a.excerpt, a.featured_image_url,
                    a.overall_rating, a.reading_time, a.published_at,
                    au.name AS author_name, c.name AS category_name, c.slug AS category_slug
             FROM articles a
             INNER JOIN article_tags at ON a.id = at.article_id
             LEFT JOIN authors au ON a.author_id = au.id
             LEFT JOIN categories c ON a.category_id = c.id
             WHERE at.tag_id = ? AND a.status = \'published\'
             ORDER BY a.published_at DESC
             LIMIT ? OFFSET ?',
            [$tagId, $perPage, $offset]
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) ceil($total / $perPage)];
    }

    /**
     * Returns paginated articles for a specific author.
     *
     * @param int $authorId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function getByAuthor(int $authorId, int $page = 1, int $perPage = POSTS_PER_PAGE): array
    {
        $offset = ($page - 1) * $perPage;
        $total = (int) (Database::getInstance()->queryOne(
            'SELECT COUNT(*) AS cnt FROM articles WHERE author_id = ? AND status = \'published\'',
            [$authorId]
        )['cnt'] ?? 0);

        $items = Database::getInstance()->query(
            'SELECT a.id, a.title, a.slug, a.content_type, a.excerpt, a.featured_image_url,
                    a.overall_rating, a.reading_time, a.published_at,
                    c.name AS category_name, c.slug AS category_slug
             FROM articles a
             LEFT JOIN categories c ON a.category_id = c.id
             WHERE a.author_id = ? AND a.status = \'published\'
             ORDER BY a.published_at DESC
             LIMIT ? OFFSET ?',
            [$authorId, $perPage, $offset]
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) ceil($total / $perPage)];
    }

    /**
     * Returns related articles from the same category, excluding the current one.
     *
     * @param int $articleId
     * @param int $limit
     * @return array
     */
    public static function getRelated(int $articleId, int $limit = 4): array
    {
        $article = self::getById($articleId);
        if (!$article || !$article['category_id']) {
            return [];
        }

        return Database::getInstance()->query(
            'SELECT a.id, a.title, a.slug, a.content_type, a.excerpt, a.featured_image_url,
                    a.overall_rating, a.reading_time, a.published_at
             FROM articles a
             WHERE a.category_id = ? AND a.id != ? AND a.status = \'published\'
             ORDER BY a.published_at DESC
             LIMIT ?',
            [$article['category_id'], $articleId, $limit]
        );
    }

    /**
     * Returns recently published articles across all types.
     *
     * @param int $limit
     * @return array
     */
    public static function getRecent(int $limit = 6): array
    {
        return Database::getInstance()->query(
            'SELECT a.id, a.title, a.slug, a.content_type, a.excerpt, a.featured_image_url,
                    a.overall_rating, a.reading_time, a.published_at,
                    au.name AS author_name, c.name AS category_name, c.slug AS category_slug
             FROM articles a
             LEFT JOIN authors au ON a.author_id = au.id
             LEFT JOIN categories c ON a.category_id = c.id
             WHERE a.status = \'published\'
             ORDER BY a.published_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    /**
     * Performs a fulltext search and returns paginated results.
     *
     * @param string $query
     * @param int    $page
     * @param int    $perPage
     * @return array
     */
    public static function search(string $query, int $page = 1, int $perPage = POSTS_PER_PAGE): array
    {
        $offset = ($page - 1) * $perPage;
        $total = (int) (Database::getInstance()->queryOne(
            'SELECT COUNT(*) AS cnt FROM articles
             WHERE MATCH(title, content, focus_keyword) AGAINST(? IN BOOLEAN MODE)
             AND status = \'published\'',
            [$query]
        )['cnt'] ?? 0);

        $items = Database::getInstance()->query(
            'SELECT id, title, slug, content_type, excerpt, featured_image_url,
                    overall_rating, reading_time, published_at
             FROM articles
             WHERE MATCH(title, content, focus_keyword) AGAINST(? IN BOOLEAN MODE)
             AND status = \'published\'
             ORDER BY published_at DESC
             LIMIT ? OFFSET ?',
            [$query, $perPage, $offset]
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) ceil($total / $perPage)];
    }

    /**
     * Inserts a new article and returns its ID.
     *
     * @param array $data
     * @return int New article ID
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO articles (
                title, slug, content_type, content, excerpt, content_json,
                meta_title, meta_description, focus_keyword, secondary_keywords,
                search_intent, seo_score, readability_level,
                schema_type, schema_json, word_count, reading_time,
                direct_answer, key_takeaways,
                overall_rating, rating_features, rating_pricing, rating_performance,
                rating_support, rating_ease_of_use,
                pros, cons, specifications, verdict, best_for,
                who_should_use, who_should_avoid, alternatives, testing_methodology,
                comparison_table, product_reviews, winner_product, winner_reason,
                dateline, news_sources, key_facts, industry_impact, expert_opinions,
                faq, common_mistakes, expert_tips,
                featured_image_url, featured_image_alt, featured_image_prompt, images,
                affiliate_links_data, has_affiliate_links, ftc_disclosure,
                internal_links, external_links, sources,
                author_id, category_id, roadmap_item_id,
                status, published_at, generation_cost, model_used
             ) VALUES (
                :title, :slug, :content_type, :content, :excerpt, :content_json,
                :meta_title, :meta_description, :focus_keyword, :secondary_keywords,
                :search_intent, :seo_score, :readability_level,
                :schema_type, :schema_json, :word_count, :reading_time,
                :direct_answer, :key_takeaways,
                :overall_rating, :rating_features, :rating_pricing, :rating_performance,
                :rating_support, :rating_ease_of_use,
                :pros, :cons, :specifications, :verdict, :best_for,
                :who_should_use, :who_should_avoid, :alternatives, :testing_methodology,
                :comparison_table, :product_reviews, :winner_product, :winner_reason,
                :dateline, :news_sources, :key_facts, :industry_impact, :expert_opinions,
                :faq, :common_mistakes, :expert_tips,
                :featured_image_url, :featured_image_alt, :featured_image_prompt, :images,
                :affiliate_links_data, :has_affiliate_links, :ftc_disclosure,
                :internal_links, :external_links, :sources,
                :author_id, :category_id, :roadmap_item_id,
                :status, :published_at, :generation_cost, :model_used
             )',
            self::prepareData($data)
        );
        return $db->lastInsertId();
    }

    /**
     * Updates specified fields for an article.
     *
     * @param int   $id
     * @param array $data
     * @return void
     */
    public static function update(int $id, array $data): void
    {
        $allowed = [
            'title',
            'slug',
            'content',
            'excerpt',
            'meta_title',
            'meta_description',
            'focus_keyword',
            'status',
            'published_at',
            'updated_content_at'
        ];
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            if (in_array($col, $allowed, true)) {
                $sets[] = "$col = ?";
                $params[] = $val;
            }
        }
        if (!$sets) {
            return;
        }
        $params[] = $id;
        Database::getInstance()->execute(
            'UPDATE articles SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $params
        );
    }

    /**
     * Updates only the status field of an article.
     *
     * @param int    $id
     * @param string $status draft|review|published|archived
     * @return void
     */
    public static function updateStatus(int $id, string $status): void
    {
        $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;
        Database::getInstance()->execute(
            'UPDATE articles SET status = ?, published_at = COALESCE(published_at, ?) WHERE id = ?',
            [$status, $publishedAt, $id]
        );
    }

    /**
     * Soft-deletes an article by setting status to archived.
     *
     * @param int $id
     * @return void
     */
    public static function delete(int $id): void
    {
        Database::getInstance()->execute(
            'UPDATE articles SET status = \'archived\' WHERE id = ?',
            [$id]
        );
    }

    /**
     * Returns a count of articles grouped by content type.
     *
     * @return array<string, int>
     */
    public static function countByType(): array
    {
        $rows = Database::getInstance()->query(
            'SELECT content_type, COUNT(*) AS cnt FROM articles
             WHERE status = \'published\' GROUP BY content_type'
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['content_type']] = (int) $row['cnt'];
        }
        return $result;
    }

    /**
     * Returns a count of articles grouped by status.
     *
     * @return array<string, int>
     */
    public static function countByStatus(): array
    {
        $rows = Database::getInstance()->query(
            'SELECT status, COUNT(*) AS cnt FROM articles GROUP BY status'
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = (int) $row['cnt'];
        }
        return $result;
    }

    /**
     * Returns all published articles (minimal fields) for sitemap generation.
     *
     * @return array
     */
    public static function getAllPublishedForSitemap(): array
    {
        return Database::getInstance()->query(
            'SELECT slug, content_type, updated_at FROM articles
             WHERE status = \'published\' ORDER BY updated_at DESC'
        );
    }

    /**
     * Alias for sitemap — includes image data.
     *
     * @return array
     */
    public static function getSitemapArticles(): array
    {
        return Database::getInstance()->query(
            'SELECT slug, content_type, updated_content_at, updated_at, published_at,
                    featured_image_url, featured_image_alt
             FROM articles WHERE status = \'published\'
             ORDER BY published_at DESC'
        );
    }

    /**
     * Returns total article count, optionally filtered by content type.
     *
     * @param string|null $type
     * @return int
     */
    public static function count(?string $type = null): int
    {
        if ($type) {
            $row = Database::getInstance()->queryOne(
                'SELECT COUNT(*) AS cnt FROM articles WHERE content_type = ? AND status != \'archived\'',
                [$type]
            );
        } else {
            $row = Database::getInstance()->queryOne(
                'SELECT COUNT(*) AS cnt FROM articles WHERE status != \'archived\''
            );
        }
        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Updates a single field value on an article.
     *
     * @param int    $id
     * @param string $field
     * @param mixed  $value
     * @return void
     */
    public static function updateField(int $id, string $field, mixed $value): void
    {
        $safeFields = [
            'content',
            'status',
            'featured_image_url',
            'featured_image_alt',
            'has_affiliate_links',
            'updated_content_at',
            'word_count',
            'reading_time',
        ];
        if (!in_array($field, $safeFields, true)) {
            return;
        }
        Database::getInstance()->execute(
            "UPDATE articles SET $field = ?, updated_at = NOW() WHERE id = ?",
            [$value, $id]
        );
    }

    /**
     * Returns paginated articles for admin panel with filtering by status and type.
     *
     * @param string|null $status
     * @param string|null $type
     * @param int         $page
     * @param int         $perPage
     * @return array{items: array, total: int, pages: int}
     */
    public static function getForAdmin(?string $status = null, ?string $type = null, int $page = 1, int $perPage = 20): array
    {
        $conditions = ["status != 'archived'"];
        $params = [];
        if ($status) {
            $conditions[] = 'status = ?';
            $params[] = $status;
        }
        if ($type) {
            $conditions[] = 'content_type = ?';
            $params[] = $type;
        }
        $where = 'WHERE ' . implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $total = (int) (Database::getInstance()->queryOne(
            "SELECT COUNT(*) AS cnt FROM articles $where",
            $params
        )['cnt'] ?? 0);

        $items = Database::getInstance()->query(
            "SELECT a.id, a.title, a.slug, a.content_type, a.status,
                    a.overall_rating, a.reading_time, a.has_affiliate_links,
                    a.published_at, a.created_at,
                    au.name AS author_name, c.name AS category_name
             FROM articles a
             LEFT JOIN authors au ON a.author_id = au.id
             LEFT JOIN categories c ON a.category_id = c.id
             $where
             ORDER BY a.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) ceil($total / $perPage)];
    }

    /**
     * Alias for getByCategory used by CategoryController.
     *
     * @param int $categoryId
     * @param int $page
     * @return array
     */
    public static function getByCategoryId(int $categoryId, int $page = 1): array
    {
        return self::getByCategory($categoryId, $page);
    }

    /**
     * Alias for getByTag used by TagController.
     *
     * @param int $tagId
     * @param int $page
     * @return array
     */
    public static function getByTagId(int $tagId, int $page = 1): array
    {
        return self::getByTag($tagId, $page);
    }

    /**
     * Alias for getByAuthor used by AuthorController.
     *
     * @param int $authorId
     * @param int $page
     * @return array
     */
    public static function getByAuthorId(int $authorId, int $page = 1): array
    {
        return self::getByAuthor($authorId, $page);
    }

    /**
     * Prepares article data for SQL by encoding JSON fields.
     *
     * @param array $data
     * @return array
     */
    private static function prepareData(array $data): array
    {
        $jsonFields = [
            'secondary_keywords',
            'schema_json',
            'key_takeaways',
            'pros',
            'cons',
            'specifications',
            'alternatives',
            'comparison_table',
            'product_reviews',
            'news_sources',
            'key_facts',
            'expert_opinions',
            'faq',
            'common_mistakes',
            'expert_tips',
            'images',
            'affiliate_links_data',
            'internal_links',
            'external_links',
            'sources',
            'content_json',
        ];

        $prepared = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $jsonFields, true) && is_array($v)) {
                $prepared[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
            } else {
                $prepared[$k] = $v;
            }
        }

        // Ensure required defaults
        $defaults = [
            'content_json' => null,
            'secondary_keywords' => null,
            'schema_json' => null,
            'seo_score' => 0,
            'readability_level' => 'medium',
            'word_count' => 0,
            'reading_time' => 0,
            'direct_answer' => null,
            'key_takeaways' => null,
            'overall_rating' => null,
            'rating_features' => null,
            'rating_pricing' => null,
            'rating_performance' => null,
            'rating_support' => null,
            'rating_ease_of_use' => null,
            'pros' => null,
            'cons' => null,
            'specifications' => null,
            'verdict' => null,
            'best_for' => null,
            'who_should_use' => null,
            'who_should_avoid' => null,
            'alternatives' => null,
            'testing_methodology' => null,
            'comparison_table' => null,
            'product_reviews' => null,
            'winner_product' => null,
            'winner_reason' => null,
            'dateline' => null,
            'news_sources' => null,
            'key_facts' => null,
            'industry_impact' => null,
            'expert_opinions' => null,
            'faq' => null,
            'common_mistakes' => null,
            'expert_tips' => null,
            'featured_image_url' => null,
            'featured_image_alt' => null,
            'featured_image_prompt' => null,
            'images' => null,
            'affiliate_links_data' => null,
            'has_affiliate_links' => 0,
            'ftc_disclosure' => null,
            'internal_links' => null,
            'external_links' => null,
            'sources' => null,
            'roadmap_item_id' => null,
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s'),
            'generation_cost' => 0,
            'model_used' => null,
        ];

        return array_merge($defaults, $prepared);
    }
}
