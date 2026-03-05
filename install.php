<?php
declare(strict_types=1);

/**
 * DevLync Database Installer
 *
 * Run this ONCE to set up the database schema and seed default settings.
 * Access: https://devlync.com/install.php?token=YOUR_INSTALL_TOKEN
 * Delete this file after installation!
 */

// Safety: only allow with a hardcoded or env token
$allowedToken = getenv('INSTALL_TOKEN') ?: 'devlync_install_2024';
$providedToken = $_GET['token'] ?? '';
if (!hash_equals($allowedToken, $providedToken)) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Invalid or missing install token.</p>');
}

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';

$db = Database::getInstance();
$errors = [];
$success = [];

// ─────────────────────────────────────────────
// Load and execute the schema SQL
// ─────────────────────────────────────────────
$schemaFile = __DIR__ . '/DEVLYNC DATA/database-schema.sql';
if (!file_exists($schemaFile)) {
    $errors[] = 'Schema file not found at: ' . $schemaFile;
} else {
    $sql = file_get_contents($schemaFile);
    // Split on semicolons and run each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => strlen($s) > 5
    );

    foreach ($statements as $stmt) {
        try {
            $db->execute($stmt);
        } catch (PDOException $e) {
            // Ignore "table already exists" errors during re-runs
            if (!str_contains($e->getMessage(), 'already exists')) {
                $errors[] = 'SQL Error: ' . $e->getMessage() . "\n<pre>" . htmlspecialchars(substr($stmt, 0, 100)) . '</pre>';
            }
        }
    }
    $success[] = 'Schema applied (' . count($statements) . ' statements processed)';
}

// ─────────────────────────────────────────────
// Seed default settings
// ─────────────────────────────────────────────
$defaultSettings = [
    ['site_name', 'DevLync'],
    ['admin_password', password_hash('changeme123', PASSWORD_BCRYPT)],
    ['api_key', bin2hex(random_bytes(32))],
    ['site_tagline', 'Developer Tools Discovery & Reviews'],
];

foreach ($defaultSettings as [$key, $value]) {
    try {
        $db->execute(
            'INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)',
            [$key, $value]
        );
    } catch (PDOException $e) {
        $errors[] = "Setting $key: " . $e->getMessage();
    }
}
$success[] = 'Default settings seeded';

// ─────────────────────────────────────────────
// Seed default author
// ─────────────────────────────────────────────
try {
    $db->execute(
        "INSERT IGNORE INTO authors (name, slug, short_bio, bio, expertise, is_active)
         VALUES (?, ?, ?, ?, ?, 1)",
        [
            'DevLync Editorial',
            'editorial',
            'The DevLync editorial team reviews developer tools with a focus on real-world usage.',
            'The DevLync editorial team is made up of developers and technical writers who independently test and evaluate developer tools, APIs, and software projects.',
            json_encode(['Developer Tools', 'Software Reviews', 'APIs', 'DevOps']),
        ]
    );
    $success[] = 'Default editorial author seeded';
} catch (PDOException $e) {
    $errors[] = 'Author seed: ' . $e->getMessage();
}

// ─────────────────────────────────────────────
// Seed default categories
// ─────────────────────────────────────────────
$categories = [
    ['AI Tools', 'ai-tools', 'Reviews and comparisons of AI developer tools', 'AI Tools'],
    ['DevOps', 'devops', 'CI/CD, containers, monitoring and infrastructure tools', 'DevOps & Infrastructure'],
    ['APIs & SDKs', 'apis-sdks', 'API platforms, SDKs, and backend services', 'APIs & SDKs'],
    ['Frontend Tools', 'frontend-tools', 'Frontend development frameworks and tooling', 'Frontend Development'],
    ['Database Tools', 'database-tools', 'Databases, ORMs, and data management tools', 'Database Tools'],
    ['Security Tools', 'security-tools', 'Developer security, auth, and compliance tools', 'Security & Auth'],
];

foreach ($categories as [$name, $slug, $desc, $pillar]) {
    try {
        $db->execute(
            'INSERT IGNORE INTO categories (name, slug, description, pillar_topic, is_active)
             VALUES (?, ?, ?, ?, 1)',
            [$name, $slug, $desc, $pillar]
        );
    } catch (PDOException $e) {
        $errors[] = "Category $slug: " . $e->getMessage();
    }
}
$success[] = count($categories) . ' default categories seeded';

// ─────────────────────────────────────────────
// Create empty .index files in cache and logs
// ─────────────────────────────────────────────
foreach ([ROOT_PATH . '/cache', ROOT_PATH . '/logs', ROOT_PATH . '/assets/images/articles'] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        $success[] = "Created directory: $dir";
    }
    // Protect directories with .htaccess
    if (!file_exists($dir . '/.htaccess') && str_contains($dir, 'cache')) {
        file_put_contents($dir . '/.htaccess', "Deny from all\n");
    }
}

// ─────────────────────────────────────────────
// Output
// ─────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">
    <title>DevLync Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-950 text-white p-8 font-mono">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">DevLync Database Installer</h1>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-900/40 border border-red-700 rounded-xl p-4 mb-4">
                <h2 class="text-red-400 font-bold mb-2">⚠ Errors</h2>
                <ul class="space-y-1">
                    <?php foreach ($errors as $err): ?>
                        <li class="text-red-300 text-sm">
                            <?= $err ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="bg-green-900/40 border border-green-700 rounded-xl p-4 mb-6">
            <h2 class="text-green-400 font-bold mb-2">✅ Completed</h2>
            <ul class="space-y-1">
                <?php foreach ($success as $msg): ?>
                    <li class="text-green-300 text-sm">•
                        <?= htmlspecialchars($msg) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="bg-yellow-900/40 border border-yellow-700 rounded-xl p-4 text-yellow-300 text-sm">
            <p class="font-bold mb-1">⚠️ IMPORTANT: Delete This File!</p>
            <p>Run: <code class="bg-black/30 px-2 py-0.5 rounded">rm install.php</code></p>
            <p class="mt-2">Default admin password is <code class="bg-black/30 px-2 py-0.5 rounded">changeme123</code> —
                change it at <a href="/admin/settings" class="underline">/admin/settings</a></p>
        </div>
    </div>
</body>

</html>