<?php
/**
 * Social Share Component
 * Share buttons for Twitter, LinkedIn, and Facebook.
 * Variables: $article (array with title, slug, content_type)
 */
$siteUrl = 'https://devlync.com';
$typeMap = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'];
$base = $typeMap[$article['content_type'] ?? 'blog'] ?? 'blog';
$pageUrl = urlencode($siteUrl . '/' . $base . '/' . ($article['slug'] ?? ''));
$title = urlencode($article['title'] ?? '');
?>
<div class="flex items-center gap-2 flex-wrap my-5 not-prose">
    <span class="text-sm text-gray-500 font-medium mr-1">Share:</span>

    <!-- Twitter/X -->
    <a href="https://twitter.com/intent/tweet?url=<?= $pageUrl ?>&text=<?= $title ?>&via=DevLync"
        rel="noopener noreferrer" target="_blank"
        class="flex items-center gap-1.5 bg-gray-900 text-white text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-black transition-colors">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
            <path
                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
        </svg>
        Twitter
    </a>

    <!-- LinkedIn -->
    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $pageUrl ?>" rel="noopener noreferrer"
        target="_blank"
        class="flex items-center gap-1.5 bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-blue-800 transition-colors">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
            <path
                d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
        </svg>
        LinkedIn
    </a>

    <!-- Facebook -->
    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $pageUrl ?>" rel="noopener noreferrer" target="_blank"
        class="flex items-center gap-1.5 bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-blue-700 transition-colors">
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
            <path
                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
        </svg>
        Facebook
    </a>

    <!-- Copy Link -->
    <button
        onclick="navigator.clipboard.writeText(decodeURIComponent('<?= $pageUrl ?>')).then(()=>this.textContent='Copied!')"
        class="flex items-center gap-1.5 bg-gray-100 text-gray-700 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-gray-200 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        Copy Link
    </button>
</div>