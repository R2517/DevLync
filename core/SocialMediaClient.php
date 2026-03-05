<?php
declare(strict_types=1);

/**
 * SocialMediaClient
 * Platform posting helper for Automation Module 3.
 */
class SocialMediaClient
{
    /**
     * Posts one item to a social platform.
     *
     * @param string     $platform
     * @param array      $article
     * @param string     $content
     * @param string     $articleUrl
     * @param array|null $image
     * @return array
     */
    public function publish(
        string $platform,
        array $article,
        string $content,
        string $articleUrl,
        ?array $image = null
    ): array {
        return match ($platform) {
            'twitter' => $this->postTwitter($content, $articleUrl),
            'linkedin' => $this->postLinkedIn($article, $content, $articleUrl),
            'facebook' => $this->postFacebook($content, $articleUrl),
            'instagram' => $this->postInstagram($content, $image),
            'pinterest' => $this->postPinterest($article, $content, $articleUrl, $image),
            'youtube' => $this->buildResult(false, 'skipped', null, null, 'YouTube community posting is not implemented'),
            'threads' => $this->postThreads($content, $articleUrl, $image),
            'bluesky' => $this->postBluesky($content, $articleUrl, $image),
            default => $this->buildResult(false, 'failed', null, null, 'Unsupported platform: ' . $platform),
        };
    }

    /**
     * Prepares image for visual platforms.
     *
     * @param string $platform
     * @param array  $article
     * @return array|null
     */
    public function preparePlatformImage(string $platform, array $article): ?array
    {
        $source = trim((string) ($article['featured_image_url'] ?? ''));
        if ($source === '') {
            return null;
        }

        $dims = match ($platform) {
            'instagram' => ['w' => 1080, 'h' => 1080],
            'pinterest' => ['w' => 1000, 'h' => 1500],
            default => null,
        };

        if ($dims === null) {
            $public = $this->toPublicUrl($source);
            if ($public === '') {
                return null;
            }
            return [
                'public_url' => $public,
                'local_path' => $this->resolveLocalPath($source),
                'alt' => (string) ($article['featured_image_alt'] ?? ''),
            ];
        }

        $local = $this->resolveLocalPath($source);
        if ($local === null) {
            $local = $this->downloadToTemp($source);
        }
        if ($local === null) {
            $public = $this->toPublicUrl($source);
            if ($public === '') {
                return null;
            }
            return [
                'public_url' => $public,
                'local_path' => null,
                'alt' => (string) ($article['featured_image_alt'] ?? ''),
            ];
        }

        $output = $this->resizeCoverJpeg($local, $dims['w'], $dims['h'], (string) ($article['slug'] ?? 'article'), $platform);
        if ($output === null) {
            $public = $this->toPublicUrl($source);
            if ($public === '') {
                return null;
            }
            return [
                'public_url' => $public,
                'local_path' => $local,
                'alt' => (string) ($article['featured_image_alt'] ?? ''),
            ];
        }

        return [
            'public_url' => $output['url'],
            'local_path' => $output['path'],
            'alt' => (string) ($article['featured_image_alt'] ?? ''),
        ];
    }

    /**
     * Posts to Twitter/X.
     *
     * @param string $content
     * @param string $articleUrl
     * @return array
     */
    private function postTwitter(string $content, string $articleUrl): array
    {
        $token = trim((string) (Setting::get('twitter_bearer_token') ?? ''));
        if ($token === '') {
            return $this->buildResult(false, 'failed', null, null, 'twitter_bearer_token missing');
        }

        $text = $this->truncate($content, 250);
        $text .= "\n\n" . $articleUrl;
        $response = HttpClient::postJson(
            'https://api.twitter.com/2/tweets',
            ['text' => $text],
            [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );

        if (!$response['success']) {
            return $this->buildResult(false, 'failed', null, null, (string) ($response['error'] ?? 'Twitter post failed'));
        }

        $id = (string) ($response['json']['data']['id'] ?? '');
        $url = $id !== '' ? 'https://twitter.com/i/status/' . rawurlencode($id) : null;
        return $this->buildResult(true, 'posted', $url, $id, null);
    }

    /**
     * Posts to LinkedIn.
     *
     * @param array  $article
     * @param string $content
     * @param string $articleUrl
     * @return array
     */
    private function postLinkedIn(array $article, string $content, string $articleUrl): array
    {
        $token = trim((string) (Setting::get('linkedin_access_token') ?? ''));
        $personId = trim((string) (Setting::get('linkedin_person_id') ?? ''));
        if ($token === '' || $personId === '') {
            return $this->buildResult(false, 'failed', null, null, 'LinkedIn credentials missing');
        }

        $payload = [
            'author' => 'urn:li:person:' . $personId,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => ['text' => $this->truncate($content, 2800)],
                    'shareMediaCategory' => 'ARTICLE',
                    'media' => [[
                        'status' => 'READY',
                        'originalUrl' => $articleUrl,
                        'title' => ['text' => (string) ($article['title'] ?? 'DevLync Article')],
                        'description' => ['text' => 'Read on DevLync'],
                    ]],
                ],
            ],
            'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
        ];

        $response = HttpClient::postJson(
            'https://api.linkedin.com/v2/ugcPosts',
            $payload,
            [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'X-Restli-Protocol-Version: 2.0.0',
            ],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );

        if (!$response['success']) {
            return $this->buildResult(false, 'failed', null, null, (string) ($response['error'] ?? 'LinkedIn post failed'));
        }

        $id = (string) ($response['json']['id'] ?? '');
        $url = $id !== '' ? 'https://www.linkedin.com/feed/update/' . rawurlencode($id) : null;
        return $this->buildResult(true, 'posted', $url, $id, null);
    }

    /**
     * Posts to Facebook Page feed.
     *
     * @param string $content
     * @param string $articleUrl
     * @return array
     */
    private function postFacebook(string $content, string $articleUrl): array
    {
        $pageId = trim((string) (Setting::get('facebook_page_id') ?? ''));
        $token = trim((string) (Setting::get('facebook_page_token') ?? ''));
        if ($pageId === '' || $token === '') {
            return $this->buildResult(false, 'failed', null, null, 'Facebook page credentials missing');
        }

        $body = http_build_query([
            'message' => $this->truncate($content, 60000) . "\n\n" . $articleUrl,
            'link' => $articleUrl,
            'access_token' => $token,
        ]);

        $response = HttpClient::post(
            'https://graph.facebook.com/v21.0/' . rawurlencode($pageId) . '/feed',
            $body,
            ['Content-Type: application/x-www-form-urlencoded'],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );

        if (!$response['success']) {
            return $this->buildResult(false, 'failed', null, null, (string) ($response['error'] ?? 'Facebook post failed'));
        }

        $id = (string) ($response['json']['id'] ?? '');
        $url = $id !== '' ? 'https://facebook.com/' . rawurlencode($id) : null;
        return $this->buildResult(true, 'posted', $url, $id, null);
    }

    /**
     * Posts to Instagram (container + poll + publish).
     *
     * @param string     $caption
     * @param array|null $image
     * @return array
     */
    private function postInstagram(string $caption, ?array $image): array
    {
        $businessId = trim((string) (Setting::get('instagram_business_id') ?? ''));
        $token = trim((string) (Setting::get('instagram_access_token') ?? ''));
        if ($businessId === '' || $token === '') {
            return $this->buildResult(false, 'failed', null, null, 'Instagram credentials missing');
        }

        $publicImage = trim((string) ($image['public_url'] ?? ''));
        if ($publicImage === '' || !$this->isLikelyPublicUrl($publicImage)) {
            return $this->buildResult(false, 'skipped', null, null, 'Instagram requires public image URL');
        }

        $create = HttpClient::post(
            'https://graph.facebook.com/v21.0/' . rawurlencode($businessId) . '/media',
            http_build_query([
                'image_url' => $publicImage,
                'caption' => $this->truncate($caption, 2200),
                'access_token' => $token,
            ]),
            ['Content-Type: application/x-www-form-urlencoded'],
            40,
            ['retries' => 2, 'verify_ssl' => true]
        );
        if (!$create['success']) {
            return $this->buildResult(false, 'failed', null, null, (string) ($create['error'] ?? 'Instagram create container failed'));
        }

        $containerId = (string) ($create['json']['id'] ?? '');
        if ($containerId === '') {
            return $this->buildResult(false, 'failed', null, null, 'Instagram container ID missing');
        }

        $pollError = $this->pollInstagramContainer($containerId, $token);
        if ($pollError !== null) {
            return $this->buildResult(false, 'failed', null, null, $pollError);
        }

        $publish = HttpClient::post(
            'https://graph.facebook.com/v21.0/' . rawurlencode($businessId) . '/media_publish',
            http_build_query([
                'creation_id' => $containerId,
                'access_token' => $token,
            ]),
            ['Content-Type: application/x-www-form-urlencoded'],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );
        if (!$publish['success']) {
            return $this->buildResult(false, 'failed', null, null, (string) ($publish['error'] ?? 'Instagram publish failed'));
        }

        $mediaId = (string) ($publish['json']['id'] ?? '');
        $url = null;
        if ($mediaId !== '') {
            $permalink = HttpClient::get(
                'https://graph.facebook.com/v21.0/' . rawurlencode($mediaId)
                . '?fields=permalink&access_token=' . rawurlencode($token),
                ['Accept: application/json'],
                15,
                ['retries' => 1, 'verify_ssl' => true]
            );
            $url = trim((string) ($permalink['json']['permalink'] ?? ''));
            if ($url === '') {
                $url = null;
            }
        }
        return $this->buildResult(true, 'posted', $url, $mediaId, null);
    }

    /**
     * Polls Instagram media container status.
     *
     * @param string $containerId
     * @param string $token
     * @return string|null
     */
    private function pollInstagramContainer(string $containerId, string $token): ?string
    {
        $deadline = time() + 30;
        while (time() <= $deadline) {
            $status = HttpClient::get(
                'https://graph.facebook.com/v21.0/' . rawurlencode($containerId)
                . '?fields=status_code&access_token=' . rawurlencode($token),
                ['Accept: application/json'],
                20,
                ['retries' => 1, 'verify_ssl' => true]
            );

            if (!$status['success']) {
                return (string) ($status['error'] ?? 'Instagram status poll failed');
            }

            $code = strtoupper(trim((string) ($status['json']['status_code'] ?? '')));
            if ($code === 'FINISHED' || $code === 'PUBLISHED') {
                return null;
            }
            if ($code === 'ERROR' || $code === 'EXPIRED') {
                return 'Instagram container status: ' . $code;
            }
            sleep(5);
        }

        return 'Instagram container polling timed out';
    }

    /**
     * Posts to Pinterest.
     *
     * @param array      $article
     * @param string     $description
     * @param string     $articleUrl
     * @param array|null $image
     * @return array
     */
    private function postPinterest(array $article, string $description, string $articleUrl, ?array $image): array
    {
        $token = trim((string) (Setting::get('pinterest_access_token') ?? ''));
        $boardId = trim((string) (Setting::get('pinterest_board_id') ?? ''));
        if ($token === '' || $boardId === '') {
            return $this->buildResult(false, 'failed', null, null, 'Pinterest credentials missing');
        }

        $publicImage = trim((string) ($image['public_url'] ?? ''));
        if ($publicImage === '' || !$this->isLikelyPublicUrl($publicImage)) {
            return $this->buildResult(false, 'skipped', null, null, 'Pinterest requires public image URL');
        }

        $payload = [
            'board_id' => $boardId,
            'title' => (string) ($article['title'] ?? 'DevLync Article'),
            'description' => $this->truncate($description, 500),
            'link' => $articleUrl,
            'media_source' => [
                'source_type' => 'image_url',
                'url' => $publicImage,
            ],
            'alt_text' => (string) ($image['alt'] ?? $article['featured_image_alt'] ?? ''),
        ];

        $response = HttpClient::postJson(
            'https://api.pinterest.com/v5/pins',
            $payload,
            [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );

        if (!$response['success']) {
            return $this->buildResult(false, 'failed', null, null, (string) ($response['error'] ?? 'Pinterest post failed'));
        }

        $id = (string) ($response['json']['id'] ?? '');
        $url = $id !== '' ? 'https://www.pinterest.com/pin/' . rawurlencode($id) . '/' : null;
        return $this->buildResult(true, 'posted', $url, $id, null);
    }

    /**
     * Posts to Threads via create + publish.
     *
     * @param string     $content
     * @param string     $articleUrl
     * @param array|null $image
     * @return array
     */
    private function postThreads(string $content, string $articleUrl, ?array $image): array
    {
        $token = trim((string) (Setting::get('threads_access_token') ?? ''));
        $userId = trim((string) (Setting::get('threads_user_id') ?? ''));
        if ($token === '' || $userId === '') {
            return $this->buildResult(false, 'failed', null, null, 'Threads credentials missing');
        }

        $publicImage = trim((string) ($image['public_url'] ?? ''));
        $payload = [
            'media_type' => $publicImage !== '' ? 'IMAGE' : 'TEXT',
            'text' => $this->truncate($content, 450) . "\n\n" . $articleUrl,
            'access_token' => $token,
        ];
        if ($publicImage !== '' && $this->isLikelyPublicUrl($publicImage)) {
            $payload['image_url'] = $publicImage;
        } else {
            $payload['media_type'] = 'TEXT';
        }

        $create = HttpClient::post(
            'https://graph.threads.net/v1.0/' . rawurlencode($userId) . '/threads',
            http_build_query($payload),
            ['Content-Type: application/x-www-form-urlencoded'],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );
        if (!$create['success']) {
            return $this->buildResult(false, 'failed', null, null, (string) ($create['error'] ?? 'Threads create failed'));
        }

        $containerId = (string) ($create['json']['id'] ?? '');
        if ($containerId === '') {
            return $this->buildResult(false, 'failed', null, null, 'Threads container ID missing');
        }

        $publish = HttpClient::post(
            'https://graph.threads.net/v1.0/' . rawurlencode($userId) . '/threads_publish',
            http_build_query([
                'creation_id' => $containerId,
                'access_token' => $token,
            ]),
            ['Content-Type: application/x-www-form-urlencoded'],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );
        if (!$publish['success']) {
            return $this->buildResult(false, 'failed', null, null, (string) ($publish['error'] ?? 'Threads publish failed'));
        }

        $id = (string) ($publish['json']['id'] ?? '');
        $url = $id !== '' ? 'https://www.threads.net/t/' . rawurlencode($id) : null;
        return $this->buildResult(true, 'posted', $url, $id, null);
    }

    /**
     * Posts to Bluesky with link facets.
     *
     * @param string     $content
     * @param string     $articleUrl
     * @param array|null $image
     * @return array
     */
    private function postBluesky(string $content, string $articleUrl, ?array $image): array
    {
        $handle = trim((string) (Setting::get('bluesky_handle') ?? ''));
        $appPassword = trim((string) (Setting::get('bluesky_app_password') ?? ''));
        if ($handle === '' || $appPassword === '') {
            return $this->buildResult(false, 'failed', null, null, 'Bluesky credentials missing');
        }

        $session = HttpClient::postJson(
            'https://bsky.social/xrpc/com.atproto.server.createSession',
            ['identifier' => $handle, 'password' => $appPassword],
            ['Content-Type: application/json'],
            30,
            ['retries' => 1, 'verify_ssl' => true]
        );
        if (!$session['success']) {
            return $this->buildResult(false, 'failed', null, null, (string) ($session['error'] ?? 'Bluesky auth failed'));
        }

        $did = (string) ($session['json']['did'] ?? '');
        $jwt = (string) ($session['json']['accessJwt'] ?? '');
        if ($did === '' || $jwt === '') {
            return $this->buildResult(false, 'failed', null, null, 'Bluesky session missing did/jwt');
        }

        $rich = $this->buildBlueskyRichText($content, $articleUrl);
        $record = [
            '$type' => 'app.bsky.feed.post',
            'text' => $rich['text'],
            'createdAt' => gmdate('c'),
            'facets' => [[
                'index' => ['byteStart' => $rich['byte_start'], 'byteEnd' => $rich['byte_end']],
                'features' => [[
                    '$type' => 'app.bsky.richtext.facet#link',
                    'uri' => $articleUrl,
                ]],
            ]],
        ];

        $embed = $this->uploadBlueskyImage($jwt, $image);
        if ($embed !== null) {
            $record['embed'] = [
                '$type' => 'app.bsky.embed.images',
                'images' => [[
                    'alt' => (string) ($image['alt'] ?? 'DevLync article image'),
                    'image' => $embed,
                ]],
            ];
        }

        $create = HttpClient::postJson(
            'https://bsky.social/xrpc/com.atproto.repo.createRecord',
            [
                'repo' => $did,
                'collection' => 'app.bsky.feed.post',
                'record' => $record,
            ],
            [
                'Authorization: Bearer ' . $jwt,
                'Content-Type: application/json',
            ],
            30,
            ['retries' => 2, 'verify_ssl' => true]
        );
        if (!$create['success']) {
            return $this->buildResult(false, 'failed', null, null, (string) ($create['error'] ?? 'Bluesky createRecord failed'));
        }

        $uri = (string) ($create['json']['uri'] ?? '');
        $url = $this->blueskyUriToWebUrl($uri, $did);
        return $this->buildResult(true, 'posted', $url, $uri, null);
    }

    /**
     * Converts at:// uri to Bluesky web URL.
     *
     * @param string $uri
     * @param string $did
     * @return string|null
     */
    private function blueskyUriToWebUrl(string $uri, string $did): ?string
    {
        if (!str_starts_with($uri, 'at://')) {
            return null;
        }

        $parts = explode('/', substr($uri, 5));
        if (count($parts) < 3) {
            return null;
        }

        $profile = $parts[0] !== '' ? $parts[0] : $did;
        $postId = $parts[2] ?? '';
        if ($postId === '') {
            return null;
        }

        return 'https://bsky.app/profile/' . rawurlencode($profile) . '/post/' . rawurlencode($postId);
    }

    /**
     * Uploads image blob to Bluesky.
     *
     * @param string     $jwt
     * @param array|null $image
     * @return array|null
     */
    private function uploadBlueskyImage(string $jwt, ?array $image): ?array
    {
        if ($image === null) {
            return null;
        }

        $bytes = null;
        $mime = 'image/jpeg';
        $localPath = (string) ($image['local_path'] ?? '');
        if ($localPath !== '' && is_file($localPath)) {
            $bytes = @file_get_contents($localPath);
            if (function_exists('mime_content_type')) {
                $detected = (string) @mime_content_type($localPath);
                if ($detected !== '') {
                    $mime = $detected;
                }
            }
        }

        if ($bytes === null || $bytes === false) {
            $public = trim((string) ($image['public_url'] ?? ''));
            if ($public === '' || !$this->isLikelyPublicUrl($public)) {
                return null;
            }
            $dl = HttpClient::get($public, ['Accept: image/*'], 20, ['retries' => 1, 'verify_ssl' => true]);
            if (!$dl['success'] || empty($dl['body'])) {
                return null;
            }
            $bytes = (string) $dl['body'];
            $ct = (string) ($dl['headers']['content-type'] ?? '');
            if ($ct !== '') {
                $mime = $ct;
            }
        }

        if ($bytes === null || $bytes === false || $bytes === '') {
            return null;
        }

        $response = HttpClient::post(
            'https://bsky.social/xrpc/com.atproto.repo.uploadBlob',
            $bytes,
            [
                'Authorization: Bearer ' . $jwt,
                'Content-Type: ' . $mime,
            ],
            30,
            ['retries' => 1, 'verify_ssl' => true]
        );
        if (!$response['success']) {
            return null;
        }

        $blob = $response['json']['blob'] ?? null;
        return is_array($blob) ? $blob : null;
    }

    /**
     * Builds Bluesky text with URL facet byte offsets.
     *
     * @param string $content
     * @param string $articleUrl
     * @return array{text: string, byte_start: int, byte_end: int}
     */
    private function buildBlueskyRichText(string $content, string $articleUrl): array
    {
        $base = trim(preg_replace('/\s+/', ' ', $content) ?? $content);
        $suffix = "\n\n" . $articleUrl;
        $maxBaseLen = max(10, 300 - mb_strlen($suffix, 'UTF-8'));
        if (mb_strlen($base, 'UTF-8') > $maxBaseLen) {
            $base = rtrim(mb_substr($base, 0, $maxBaseLen - 3, 'UTF-8')) . '...';
        }

        $text = trim($base) . $suffix;
        $pos = mb_strpos($text, $articleUrl, 0, 'UTF-8');
        if ($pos === false) {
            $text = $this->truncate($base, 240) . "\n\n" . $articleUrl;
            $pos = mb_strpos($text, $articleUrl, 0, 'UTF-8');
        }
        $pos = $pos === false ? 0 : $pos;

        $prefix = mb_substr($text, 0, $pos, 'UTF-8');
        $byteStart = strlen($prefix);
        $byteEnd = $byteStart + strlen($articleUrl);

        return [
            'text' => $text,
            'byte_start' => $byteStart,
            'byte_end' => $byteEnd,
        ];
    }

    /**
     * Crops and resizes an image to exact dimensions (cover mode).
     *
     * @param string $sourcePath
     * @param int    $targetW
     * @param int    $targetH
     * @param string $slug
     * @param string $platform
     * @return array|null
     */
    private function resizeCoverJpeg(string $sourcePath, int $targetW, int $targetH, string $slug, string $platform): ?array
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            return null;
        }

        $bytes = @file_get_contents($sourcePath);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        $src = @imagecreatefromstring($bytes);
        if ($src === false) {
            return null;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW <= 0 || $srcH <= 0) {
            imagedestroy($src);
            return null;
        }

        $srcRatio = $srcW / $srcH;
        $targetRatio = $targetW / $targetH;
        if ($srcRatio > $targetRatio) {
            $cropH = $srcH;
            $cropW = (int) round($cropH * $targetRatio);
            $cropX = (int) floor(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            $cropW = $srcW;
            $cropH = (int) round($cropW / $targetRatio);
            $cropX = 0;
            $cropY = (int) floor(($srcH - $cropH) / 2);
        }

        $dst = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $targetW, $targetH, $cropW, $cropH);
        imagedestroy($src);

        $dir = ROOT_PATH . '/uploads/images/social';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $safeSlug = preg_replace('/[^a-z0-9-]+/i', '-', strtolower($slug)) ?? 'article';
        $safeSlug = trim($safeSlug, '-');
        if ($safeSlug === '') {
            $safeSlug = 'article';
        }
        $filename = $safeSlug . '-' . $platform . '-' . gmdate('YmdHis') . '.jpg';
        $path = $dir . '/' . $filename;
        $ok = imagejpeg($dst, $path, 85);
        imagedestroy($dst);
        if (!$ok) {
            return null;
        }

        return [
            'path' => $path,
            'url' => SITE_URL . '/uploads/images/social/' . $filename,
        ];
    }

    /**
     * Resolves local path for project-relative image URL.
     *
     * @param string $url
     * @return string|null
     */
    private function resolveLocalPath(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (is_file($url)) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return null;
        }

        if (defined('BASE_PATH') && BASE_PATH !== '' && str_starts_with($path, BASE_PATH . '/')) {
            $path = substr($path, strlen(BASE_PATH));
        }

        $candidate = ROOT_PATH . '/' . ltrim($path, '/');
        return is_file($candidate) ? $candidate : null;
    }

    /**
     * Downloads image URL to temporary file.
     *
     * @param string $url
     * @return string|null
     */
    private function downloadToTemp(string $url): ?string
    {
        $public = $this->toPublicUrl($url);
        if ($public === '') {
            return null;
        }

        $response = HttpClient::get($public, ['Accept: image/*'], 20, ['retries' => 1, 'verify_ssl' => true]);
        if (!$response['success'] || empty($response['body'])) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'dl_soc_');
        if ($tmp === false) {
            return null;
        }

        if (@file_put_contents($tmp, (string) $response['body']) === false) {
            @unlink($tmp);
            return null;
        }

        return $tmp;
    }

    /**
     * Converts relative URL/path to absolute public URL.
     *
     * @param string $value
     * @return string
     */
    private function toPublicUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            return $value;
        }

        if (str_starts_with($value, '//')) {
            return 'https:' . $value;
        }

        if (!str_starts_with($value, '/')) {
            $value = '/' . $value;
        }

        return SITE_URL . $value;
    }

    /**
     * Checks whether URL looks publicly reachable.
     *
     * @param string $url
     * @return bool
     */
    private function isLikelyPublicUrl(string $url): bool
    {
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return false;
        }

        return true;
    }

    /**
     * Truncates text to maximum characters.
     *
     * @param string $text
     * @param int    $max
     * @return string
     */
    private function truncate(string $text, int $max): string
    {
        $text = trim($text);
        if (mb_strlen($text, 'UTF-8') <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(1, $max - 3), 'UTF-8')) . '...';
    }

    /**
     * Builds standardized post result.
     *
     * @param bool        $success
     * @param string      $status
     * @param string|null $postUrl
     * @param string|null $postId
     * @param string|null $error
     * @return array
     */
    private function buildResult(bool $success, string $status, ?string $postUrl, ?string $postId, ?string $error): array
    {
        return [
            'success' => $success,
            'status' => $status,
            'post_url' => $postUrl,
            'platform_post_id' => $postId,
            'error' => $error,
        ];
    }
}
