<?php
declare(strict_types=1);

/**
 * AffiliateLink Model
 * Brand management and automatic content replacement of brand mentions with affiliate links.
 */
class AffiliateLink
{
    /**
     * Normalizes external URL values to always include protocol.
     *
     * @param string $url
     * @return string
     */
    private static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, '#')) {
            return $url;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        return $url;
    }

    /**
     * Returns all affiliate links.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return Database::getInstance()->query(
            'SELECT id, brand_name, brand_slug, brand_aliases, affiliate_url, fallback_url,
                    program_name, commission_type, commission_value, logo_url,
                    status, click_count, articles_count, notes, created_at
             FROM affiliate_links ORDER BY brand_name ASC'
        );
    }

    /**
     * Returns only active (live) affiliate links.
     *
     * @return array
     */
    public static function getActive(): array
    {
        return Database::getInstance()->query(
            'SELECT id, brand_name, brand_slug, brand_aliases, affiliate_url,
                    fallback_url, logo_url, status
             FROM affiliate_links WHERE status = \'active\' ORDER BY brand_name ASC'
        );
    }

    /**
     * Returns links with pending status (no real URL yet).
     *
     * @return array
     */
    public static function getPending(): array
    {
        return Database::getInstance()->query(
            'SELECT id, brand_name, brand_slug, notes, articles_count
             FROM affiliate_links WHERE status = \'pending\' ORDER BY articles_count DESC'
        );
    }

    /**
     * Gets a single affiliate link by ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function getById(int $id): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT id, brand_name, brand_slug, brand_aliases, affiliate_url, fallback_url,
                    program_name, commission_type, commission_value, logo_url,
                    status, click_count, articles_count, notes
             FROM affiliate_links WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * Gets an affiliate link by brand slug.
     *
     * @param string $slug
     * @return array|null
     */
    public static function getByBrandSlug(string $slug): ?array
    {
        return Database::getInstance()->queryOne(
            'SELECT id, brand_name, brand_slug, affiliate_url, fallback_url, status
             FROM affiliate_links WHERE brand_slug = ? LIMIT 1',
            [$slug]
        );
    }

    /**
     * Creates a new affiliate link record.
     *
     * @param array $data
     * @return int New ID
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO affiliate_links
             (brand_name, brand_slug, brand_aliases, affiliate_url, fallback_url,
              program_name, commission_type, commission_value, logo_url, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['brand_name'],
                $data['brand_slug'],
                isset($data['brand_aliases']) ? json_encode($data['brand_aliases']) : null,
                $data['affiliate_url'] ?? null,
                $data['fallback_url'] ?? null,
                $data['program_name'] ?? null,
                $data['commission_type'] ?? null,
                $data['commission_value'] ?? null,
                $data['logo_url'] ?? null,
                $data['status'] ?? 'pending',
                $data['notes'] ?? null,
            ]
        );
        return $db->lastInsertId();
    }

    /**
     * Updates an affiliate link record.
     *
     * @param int   $id
     * @param array $data
     * @return void
     */
    public static function update(int $id, array $data): void
    {
        $allowed = [
            'brand_name',
            'affiliate_url',
            'fallback_url',
            'program_name',
            'commission_type',
            'commission_value',
            'logo_url',
            'status',
            'notes'
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
            'UPDATE affiliate_links SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $params
        );
    }

    /**
     * Scans HTML content and replaces brand-name text with affiliate links.
     * Brands without real URLs get dummy placeholder links.
     *
     * @param string $htmlContent
     * @param int    $articleId
     * @return array{content: string, linksAdded: int, linksPending: int}
     */
    public static function processContent(string $htmlContent, int $articleId): array
    {
        $affiliates = self::getAll();
        $linksAdded = 0;
        $linksPending = 0;

        foreach ($affiliates as $affiliate) {
            if ($affiliate['status'] === 'paused' || $affiliate['status'] === 'expired') {
                continue;
            }

            $aliases = [$affiliate['brand_name']];

            if (!empty($affiliate['brand_aliases'])) {
                $jsonAliases = json_decode($affiliate['brand_aliases'], true);
                if (is_array($jsonAliases)) {
                    $aliases = array_merge($aliases, $jsonAliases);
                }
            }

            $url = (string) ($affiliate['affiliate_url'] ?? $affiliate['fallback_url'] ?? '');
            $isDummy = empty($affiliate['affiliate_url']);
            $linkUrl = self::normalizeUrl($url);
            if ($linkUrl === '') {
                $linkUrl = '#affiliate-' . $affiliate['brand_slug'];
            }
            $rel = 'nofollow noopener sponsored';
            $class = 'affiliate-link';
            $dataAttr = 'data-brand="' . htmlspecialchars($affiliate['brand_slug']) . '"';

            foreach ($aliases as $alias) {
                // Match brand name only in visible text, not inside HTML tags or existing links
                $escaped = preg_quote($alias, '/');
                $pattern = '/(?<![\w\/">])' . $escaped . '(?![^<]*>)(?![^<]*<\/a>)\b/i';
                $count = 0;
                $htmlContent = preg_replace_callback(
                    $pattern,
                    function ($matches) use ($linkUrl, $rel, $class, $dataAttr, &$count) {
                        if ($count > 0) {
                            return $matches[0]; // Replace only first occurrence per alias
                        }
                        $count++;
                        return '<a href="' . htmlspecialchars($linkUrl) . '" rel="' . $rel . '" '
                            . 'class="' . $class . '" ' . $dataAttr . ' target="_blank">'
                            . $matches[0] . '</a>';
                    },
                    $htmlContent
                );

                if ($count > 0) {
                    if ($isDummy) {
                        $linksPending++;
                    } else {
                        $linksAdded++;
                    }
                    // Track usage
                    Database::getInstance()->execute(
                        'INSERT IGNORE INTO affiliate_link_usage
                         (affiliate_link_id, article_id, anchor_text, is_dummy)
                         VALUES (?, ?, ?, ?)',
                        [$affiliate['id'], $articleId, $alias, $isDummy ? 1 : 0]
                    );
                }
            }
        }

        return [
            'content' => $htmlContent,
            'linksAdded' => $linksAdded,
            'linksPending' => $linksPending,
        ];
    }

    /**
     * Updates all articles that contain dummy links for a brand with the real URL.
     *
     * @param int $affiliateLinkId
     * @return int Number of articles updated
     */
    public static function updateAllArticles(int $affiliateLinkId): int
    {
        $affiliate = self::getById($affiliateLinkId);
        if (!$affiliate) {
            return 0;
        }

        $normalizedUrl = self::normalizeUrl((string) ($affiliate['affiliate_url'] ?? ''));
        if ($normalizedUrl === '') {
            return 0;
        }

        $usages = Database::getInstance()->query(
            'SELECT article_id FROM affiliate_link_usage
             WHERE affiliate_link_id = ? AND is_dummy = 1',
            [$affiliateLinkId]
        );

        $updated = 0;
        foreach ($usages as $usage) {
            $article = Database::getInstance()->queryOne(
                'SELECT id, content FROM articles WHERE id = ?',
                [$usage['article_id']]
            );
            if (!$article) {
                continue;
            }

            $updatedContent = str_replace(
                'href="#affiliate-' . $affiliate['brand_slug'] . '"',
                'href="' . htmlspecialchars($normalizedUrl, ENT_QUOTES, 'UTF-8') . '"',
                $article['content']
            );

            if ($updatedContent !== $article['content']) {
                Database::getInstance()->execute(
                    'UPDATE articles SET content = ?, updated_content_at = NOW() WHERE id = ?',
                    [$updatedContent, $article['id']]
                );
                Database::getInstance()->execute(
                    'UPDATE affiliate_link_usage SET is_dummy = 0
                     WHERE affiliate_link_id = ? AND article_id = ?',
                    [$affiliateLinkId, $article['id']]
                );
                $updated++;
            }
        }

        // Update brand article count
        Database::getInstance()->execute(
            'UPDATE affiliate_links SET articles_count = ? WHERE id = ?',
            [$updated, $affiliateLinkId]
        );

        return $updated;
    }

    /**
     * Auto-discovers brand/tool names from article metadata and creates pending affiliate entries.
     * Called during postProcessArticle so brands appear on admin/affiliates automatically.
     *
     * @param array $payload  Article payload from AI writer
     * @param array $roadmap  Roadmap item data
     * @return array  List of brand names discovered
     */
    public static function autoDiscoverBrands(array $payload, array $roadmap): array
    {
        $type = trim((string) ($roadmap['content_type'] ?? 'blog'));
        $title = trim((string) ($roadmap['title'] ?? ''));
        $keyword = trim((string) ($roadmap['primary_keyword'] ?? ''));
        $discovered = [];

        // Extract brands from title patterns
        if ($type === 'review') {
            // "Lovable Review: ..." → extract "Lovable"
            if (preg_match('/^(.+?)\s+review\b/i', $title, $m)) {
                $discovered[] = trim($m[1]);
            }
            // primary_keyword often is the tool name
            if ($keyword !== '' && !preg_match('/\b(review|best|top|guide|tutorial)\b/i', $keyword)) {
                $discovered[] = $keyword;
            }
        } elseif ($type === 'comparison') {
            // "X vs Y" or "X vs. Y" patterns
            if (preg_match('/^(.+?)\s+vs\.?\s+(.+?)(?:\s*[:|\-–—]|$)/i', $title, $m)) {
                $discovered[] = trim($m[1]);
                $discovered[] = trim($m[2]);
            }
            // Also check payload comparison fields
            $winner = trim((string) ($payload['winnerProduct'] ?? ''));
            if ($winner !== '') {
                $discovered[] = $winner;
            }
        }

        // Extract from tags (tool names often appear as tags)
        $tags = $payload['tags'] ?? [];
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tag = trim((string) $tag);
                // Only consider tags that look like brand/tool names (capitalized, no common words)
                if ($tag !== '' && preg_match('/^[A-Z]/', $tag) && mb_strlen($tag) <= 30
                    && !preg_match('/^(Review|Comparison|News|Blog|Guide|Tutorial|Best|Top|Free|Open.Source|Development|Programming|Web|Mobile|AI|API)$/i', $tag)) {
                    $discovered[] = $tag;
                }
            }
        }

        // Deduplicate and clean
        $seen = [];
        $created = [];
        foreach ($discovered as $name) {
            $name = trim($name);
            if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 60) {
                continue;
            }
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $slug = trim($slug, '-');
            if ($slug === '' || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;

            // Check if brand already exists
            $existing = self::getByBrandSlug($slug);
            if ($existing) {
                continue;
            }

            // Create pending affiliate entry
            try {
                self::create([
                    'brand_name' => $name,
                    'brand_slug' => $slug,
                    'status' => 'pending',
                    'notes' => 'Auto-discovered from ' . $type . ' article: ' . mb_substr($title, 0, 100),
                ]);
                $created[] = $name;
            } catch (Throwable) {
                // Ignore duplicate key or other DB errors
            }
        }

        return $created;
    }

    /**
     * Increments click count for a brand.
     *
     * @param string $brandSlug
     * @return void
     */
    public static function trackClick(string $brandSlug): void
    {
        Database::getInstance()->execute(
            'UPDATE affiliate_links SET click_count = click_count + 1, last_click_at = NOW()
             WHERE brand_slug = ?',
            [$brandSlug]
        );
    }

    /**
     * Returns affiliate link usage data for a specific article.
     *
     * @param int $articleId
     * @return array
     */
    public static function getUsageForArticle(int $articleId): array
    {
        return Database::getInstance()->query(
            'SELECT al.brand_name, al.brand_slug, al.affiliate_url, al.status,
                    alu.anchor_text, alu.is_dummy
             FROM affiliate_link_usage alu
             INNER JOIN affiliate_links al ON alu.affiliate_link_id = al.id
             WHERE alu.article_id = ?',
            [$articleId]
        );
    }
}
