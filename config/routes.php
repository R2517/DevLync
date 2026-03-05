<?php
declare(strict_types=1);

/**
 * Route Definitions for DevLync.com
 * All public, API, and admin routes registered here.
 */

// ── Public Routes ─────────────────────────────────────────────────────────────

$router->get('/', [HomeController::class, 'index']);

// Blog
$router->get('/blog', [BlogController::class, 'index']);
$router->get('/blog/{slug}', [BlogController::class, 'show']);

// Reviews
$router->get('/reviews', [ReviewController::class, 'index']);
$router->get('/reviews/{slug}', [ReviewController::class, 'show']);

// Comparisons
$router->get('/comparisons', [ComparisonController::class, 'index']);
$router->get('/comparisons/{slug}', [ComparisonController::class, 'show']);

// News
$router->get('/news', [NewsController::class, 'index']);
$router->get('/news/{slug}', [NewsController::class, 'show']);

// Category, Tag, Author
$router->get('/category/{slug}', [CategoryController::class, 'show']);
$router->get('/tag/{slug}', [TagController::class, 'show']);
$router->get('/author/{slug}', [AuthorController::class, 'show']);

// Search
$router->get('/search', [SearchController::class, 'index']);

// Static Pages
$router->get('/about', [StaticController::class, 'about']);
$router->get('/contact', [StaticController::class, 'contact']);
$router->get('/editorial-policy', [StaticController::class, 'editorialPolicy']);
$router->get('/fact-checking-policy', [StaticController::class, 'factCheckingPolicy']);
$router->get('/affiliate-disclosure', [StaticController::class, 'affiliateDisclosure']);
$router->get('/privacy-policy', [StaticController::class, 'privacyPolicy']);

// ── Admin Routes ──────────────────────────────────────────────────────────────

$router->get('/admin/login', [AdminController::class, 'login']);
$router->post('/admin/login', [AdminController::class, 'login']);
$router->get('/admin/logout', [AdminController::class, 'logout']);
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/automation', [AutomationController::class, 'dashboard']);
$router->get('/admin/automation/logs', [AutomationController::class, 'logs']);
$router->get('/admin/automation/providers', [AutomationController::class, 'providers']);
$router->get('/admin/automation/social', [AutomationController::class, 'social']);
$router->get('/admin/automation/settings', [AutomationController::class, 'settings']);
$router->get('/admin/automation/knowledge', [AutomationController::class, 'knowledge']);
$router->get('/admin/automation/scrape-logs', [AutomationController::class, 'scrapeLogs']);
$router->get('/admin/automation/scraper', [AutomationController::class, 'scraper']);
$router->get('/admin/automation/competitors', [AutomationController::class, 'competitors']);
$router->get('/admin/articles', [AdminController::class, 'articles']);
$router->get('/admin/articles/create', [AdminController::class, 'createArticle']);
$router->post('/admin/articles/create', [AdminController::class, 'createArticle']);
$router->get('/admin/articles/{id}/publish', [AdminController::class, 'publishArticle']);
$router->get('/admin/articles/{id}/edit', [AdminController::class, 'editArticle']);
$router->post('/admin/articles/{id}/edit', [AdminController::class, 'editArticle']);
$router->get('/admin/images', [AdminController::class, 'images']);
$router->get('/admin/images/{id}/approve', [AdminController::class, 'approveImage']);
$router->get('/admin/images/{id}/reject', [AdminController::class, 'rejectImage']);
$router->get('/admin/affiliates', [AdminController::class, 'affiliates']);
$router->get('/admin/affiliates/create', [AdminController::class, 'createAffiliate']);
$router->post('/admin/affiliates/create', [AdminController::class, 'createAffiliate']);
$router->post('/admin/affiliates/activate', [AdminController::class, 'activateAffiliate']);
$router->post('/admin/affiliates/update', [AdminController::class, 'updateAffiliate']);
$router->post('/admin/affiliates/delete', [AdminController::class, 'deleteAffiliate']);
$router->get('/admin/roadmap', [AdminController::class, 'roadmap']);
$router->get('/admin/roadmap/create', [AdminController::class, 'createRoadmapItem']);
$router->post('/admin/roadmap/create', [AdminController::class, 'createRoadmapItem']);
$router->get('/admin/costs', [AdminController::class, 'costs']);
$router->get('/admin/settings', [AdminController::class, 'settings']);
$router->post('/admin/settings', [AdminController::class, 'settings']);
$router->get('/admin/categories', [AdminController::class, 'categories']);
$router->get('/admin/cache-clear', [AdminController::class, 'clearCache']);

// ── Supervisor Routes ────────────────────────────────────────────────────────
$router->get('/admin/supervisor', [SupervisorController::class, 'index']);
$router->get('/admin/supervisor/errors', [SupervisorController::class, 'errors']);
$router->get('/admin/supervisor/suggestions', [SupervisorController::class, 'suggestions']);
$router->get('/admin/supervisor/reports', [SupervisorController::class, 'reports']);
$router->get('/admin/supervisor/activity', [SupervisorController::class, 'activity']);
$router->get('/admin/supervisor/settings', [SupervisorController::class, 'settings']);
$router->post('/admin/supervisor/settings', [SupervisorController::class, 'settings']);

// ── Feed Route ───────────────────────────────────────────────────────────────
$router->get('/feed', function () {
    require_once ROOT_PATH . '/feed.php';
});

// ── API Routes (n8n Webhooks) ─────────────────────────────────────────────────

$router->post('/api/articles/publish', [ApiController::class, 'publishArticle']);
$router->post('/api/images/upload', [ApiController::class, 'uploadImage']);
$router->post('/api/knowledge/add', [ApiController::class, 'addKnowledge']);
$router->post('/api/affiliate/process', [ApiController::class, 'processAffiliateLinks']);
$router->post('/api/cache/clear', [ApiController::class, 'clearCache']);
$router->get('/api/track-click', [ApiController::class, 'trackClick']);
$router->post('/api/roadmap/add', [ApiController::class, 'addRoadmapItems']);

// API GET health-check pings (for Supervisor monitoring — no action triggered)
$router->get('/api/articles/publish', [ApiController::class, 'healthPing']);
$router->get('/api/images/upload', [ApiController::class, 'healthPing']);
$router->get('/api/knowledge/add', [ApiController::class, 'healthPing']);
$router->get('/api/affiliate/process', [ApiController::class, 'healthPing']);
$router->get('/api/cache/clear', [ApiController::class, 'healthPing']);
$router->get('/api/roadmap/add', [ApiController::class, 'healthPing']);

// Automation action API aliases
$router->get('/api/automation', function () {
    require_once ROOT_PATH . '/api/automation.php';
});
$router->post('/api/automation', function () {
    require_once ROOT_PATH . '/api/automation.php';
});
