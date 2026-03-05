<?php
declare(strict_types=1);

/**
 * ImageLibrary Model
 * Manages all article images: generated, scraped, and uploaded.
 */
class ImageLibrary
{
    /**
     * Returns paginated image library entries, optionally filtered by status.
     *
     * @param string|null $status
     * @param int         $page
     * @param int         $perPage
     * @return array
     */
    public static function getAll(?string $status = null, int $page = 1, int $perPage = 24): array
    {
        $offset = ($page - 1) * $perPage;
        $where = $status ? 'WHERE status = ?' : '';
        $params = $status ? [$status] : [];

        $total = (int) (Database::getInstance()->queryOne(
            "SELECT COUNT(*) AS cnt FROM image_library $where",
            $params
        )['cnt'] ?? 0);

        $items = Database::getInstance()->query(
            "SELECT id, filename, file_url, file_size, width, height, format,
                    source_type, alt_text, article_id, image_slot, status, created_at
             FROM image_library $where
             ORDER BY created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return ['items' => $items, 'total' => $total, 'pages' => (int) ceil($total / $perPage)];
    }

    /**
     * Returns all images for a specific article.
     *
     * @param int $articleId
     * @return array
     */
    public static function getForArticle(int $articleId): array
    {
        return Database::getInstance()->query(
            'SELECT id, filename, file_url, alt_text, image_slot, placement, status
             FROM image_library WHERE article_id = ? ORDER BY image_slot ASC',
            [$articleId]
        );
    }

    /**
     * Creates a new image library record.
     *
     * @param array $data
     * @return int New ID
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO image_library
             (filename, filepath, file_url, file_size, width, height, format,
              source_type, source_url, ai_prompt, alt_text, title, description,
              article_id, image_slot, placement, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['filename'],
                $data['filepath'],
                $data['file_url'],
                $data['file_size'] ?? 0,
                $data['width'] ?? 0,
                $data['height'] ?? 0,
                $data['format'] ?? 'webp',
                $data['source_type'],
                $data['source_url'] ?? null,
                $data['ai_prompt'] ?? null,
                $data['alt_text'] ?? null,
                $data['title'] ?? null,
                $data['description'] ?? null,
                $data['article_id'] ?? null,
                $data['image_slot'] ?? null,
                $data['placement'] ?? null,
                $data['status'] ?? 'needs_review',
            ]
        );
        return $db->lastInsertId();
    }

    /**
     * Updates an image record.
     *
     * @param int   $id
     * @param array $data
     * @return void
     */
    public static function update(int $id, array $data): void
    {
        $allowed = [
            'alt_text',
            'title',
            'description',
            'article_id',
            'image_slot',
            'placement',
            'status',
            'file_url',
            'file_size'
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
            'UPDATE image_library SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $params
        );
    }

    /**
     * Updates only the status field for an image.
     *
     * @param int    $id
     * @param string $status
     * @return void
     */
    public static function updateStatus(int $id, string $status): void
    {
        Database::getInstance()->execute(
            'UPDATE image_library SET status = ? WHERE id = ?',
            [$status, $id]
        );
    }

    /**
     * Returns images that need to be uploaded (source URL exists, local file missing).
     *
     * @return array
     */
    public static function getNeedsUpload(): array
    {
        return Database::getInstance()->query(
            'SELECT id, filename, file_url, source_url, alt_text, article_id, image_slot
             FROM image_library WHERE status = \'needs_upload\' ORDER BY created_at ASC'
        );
    }

    /**
     * Returns images that need admin review before publishing.
     *
     * @return array
     */
    public static function getNeedsReview(): array
    {
        return Database::getInstance()->query(
            'SELECT il.id, il.filename, il.file_url, il.source_type, il.alt_text,
                    il.article_id, il.image_slot, il.created_at,
                    a.title AS article_title, a.slug AS article_slug
             FROM image_library il
             LEFT JOIN articles a ON il.article_id = a.id
             WHERE il.status = \'needs_review\'
             ORDER BY il.created_at DESC'
        );
    }

    /**
     * Saves an uploaded image file, converts to WebP, and creates a library record.
     *
     * @param array  $file      $_FILES array element
     * @param int    $articleId
     * @param string $slot      Image slot identifier
     * @return int|null New image ID or null on failure
     */
    public static function saveUploadedImage(array $file, int $articleId, string $slot): ?int
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $article = Database::getInstance()->queryOne(
            'SELECT slug FROM articles WHERE id = ? LIMIT 1',
            [$articleId]
        );
        if (!$article) {
            return null;
        }

        $dir = IMAGES_PATH . '/' . $article['slug'];
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $slot . '.webp';
        $filepath = $dir . '/' . $filename;
        $fileUrl = '/assets/images/articles/' . $article['slug'] . '/' . $filename;
        $imageInfo = getimagesize($file['tmp_name']);

        if (!$imageInfo) {
            return null;
        }

        // Convert to WebP
        $srcImage = match ($imageInfo['mime']) {
            'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
            'image/png' => imagecreatefrompng($file['tmp_name']),
            'image/webp' => imagecreatefromwebp($file['tmp_name']),
            default => null,
        };

        if (!$srcImage) {
            return null;
        }

        // Resize if needed
        $origW = imagesx($srcImage);
        $origH = imagesy($srcImage);
        if ($origW > IMAGE_MAX_WIDTH) {
            $ratio = IMAGE_MAX_WIDTH / $origW;
            $newW = IMAGE_MAX_WIDTH;
            $newH = (int) ($origH * $ratio);
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $srcImage, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($srcImage);
            $srcImage = $resized;
        }

        imagewebp($srcImage, $filepath, IMAGE_QUALITY_WEBP);
        imagedestroy($srcImage);

        return self::create([
            'filename' => $filename,
            'filepath' => $filepath,
            'file_url' => $fileUrl,
            'file_size' => filesize($filepath),
            'width' => imagesx(imagecreatefromwebp($filepath)),
            'height' => imagesy(imagecreatefromwebp($filepath)),
            'format' => 'webp',
            'source_type' => 'uploaded',
            'article_id' => $articleId,
            'image_slot' => $slot,
            'status' => 'ready',
        ]);
    }
}
