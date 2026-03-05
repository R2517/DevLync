<?php
declare(strict_types=1);

/**
 * DevLync.com — Front Controller
 * Entry point for all web requests. Loads all dependencies and dispatches routing.
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

// ── Core Classes ──────────────────────────────────────────────────────────────

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Template.php';
require_once __DIR__ . '/core/Cache.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/ApiAuth.php';
require_once __DIR__ . '/core/HttpClient.php';
require_once __DIR__ . '/core/AutomationRunner.php';
require_once __DIR__ . '/core/SocialMediaClient.php';
require_once __DIR__ . '/core/SupervisorErrorHandler.php';

// ── Models ────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/models/Setting.php';
require_once __DIR__ . '/models/Author.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Tag.php';
require_once __DIR__ . '/models/Article.php';
require_once __DIR__ . '/models/AffiliateLink.php';
require_once __DIR__ . '/models/KnowledgeItem.php';
require_once __DIR__ . '/models/RoadmapItem.php';
require_once __DIR__ . '/models/ImageLibrary.php';
require_once __DIR__ . '/models/CostRecord.php';
require_once __DIR__ . '/models/SocialPost.php';
require_once __DIR__ . '/models/ScrapeLog.php';
require_once __DIR__ . '/models/Supervisor.php';
require_once __DIR__ . '/models/Automation.php';
require_once __DIR__ . '/models/CompetitorSite.php';
require_once __DIR__ . '/models/CompetitorFeed.php';

// ── Services ─────────────────────────────────────────────────────────────────

require_once __DIR__ . '/services/AiAnalyzer.php';

// ── Controllers ───────────────────────────────────────────────────────────────

require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/BlogController.php';
require_once __DIR__ . '/controllers/ReviewController.php';
require_once __DIR__ . '/controllers/ComparisonController.php';
require_once __DIR__ . '/controllers/NewsController.php';
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/controllers/TagController.php';
require_once __DIR__ . '/controllers/AuthorController.php';
require_once __DIR__ . '/controllers/SearchController.php';
require_once __DIR__ . '/controllers/StaticController.php';
require_once __DIR__ . '/controllers/ApiController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/SupervisorController.php';
require_once __DIR__ . '/controllers/admin/AutomationController.php';

// ── Global Template Instance ──────────────────────────────────────────────────

global $tpl, $auth;
$tpl = new Template();
$auth = new Auth();

// ── Register Supervisor Error Handler ─────────────────────────────────────────
SupervisorErrorHandler::register();

// ── Cache & Log Directories ───────────────────────────────────────────────────

if (!is_dir(ROOT_PATH . '/cache')) {
    mkdir(ROOT_PATH . '/cache', 0755, true);
}

// ── Log directory ─────────────────────────────────────────────────────────────

if (!is_dir(ROOT_PATH . '/logs')) {
    mkdir(ROOT_PATH . '/logs', 0755, true);
}

// ── Routes ────────────────────────────────────────────────────────────────────

$router = new Router();
require_once __DIR__ . '/config/routes.php';

// ── Dispatch ──────────────────────────────────────────────────────────────────

$router->dispatch();
