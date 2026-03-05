<?php
declare(strict_types=1);

/**
 * SocialPost Model
 * Tracks social media post status across platforms.
 */
class SocialPost
{
    /**
     * Creates a new social post record.
     *
     * @param array $data
     * @return int New ID
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO social_posts (article_id, platform, content, image_url, status)
             VALUES (?, ?, ?, ?, ?)',
            [
                $data['article_id'],
                $data['platform'],
                $data['content'],
                $data['image_url'] ?? null,
                $data['status'] ?? 'pending',
            ]
        );
        return $db->lastInsertId();
    }

    /**
     * Returns all social posts for a specific article.
     *
     * @param int $articleId
     * @return array
     */
    public static function getForArticle(int $articleId): array
    {
        return Database::getInstance()->query(
            'SELECT id, platform, content, post_url, status, posted_at, error_message
             FROM social_posts WHERE article_id = ? ORDER BY created_at DESC',
            [$articleId]
        );
    }

    /**
     * Updates the status of a social post, optionally adding the post URL.
     *
     * @param int         $id
     * @param string      $status posted|failed|pending
     * @param string|null $postUrl URL of the published post
     * @return void
     */
    public static function updateStatus(int $id, string $status, ?string $postUrl = null): void
    {
        $postedAt = ($status === 'posted') ? date('Y-m-d H:i:s') : null;
        Database::getInstance()->execute(
            'UPDATE social_posts SET status = ?, post_url = ?, posted_at = ? WHERE id = ?',
            [$status, $postUrl, $postedAt, $id]
        );
    }
}
