<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$links = [
    ['url' => url('/admin/automation'), 'label' => 'Dashboard'],
    ['url' => url('/admin/automation/scraper'), 'label' => 'Scraper Config'],
    ['url' => url('/admin/automation/knowledge'), 'label' => 'Knowledge Base'],
    ['url' => url('/admin/automation/competitors'), 'label' => 'Competitors'],
    ['url' => url('/admin/automation/scrape-logs'), 'label' => 'Scrape Logs'],
    ['url' => url('/admin/automation/logs'), 'label' => 'Logs'],
    ['url' => url('/admin/automation/providers'), 'label' => 'AI Providers'],
    ['url' => url('/admin/automation/social'), 'label' => 'Social Platforms'],
    ['url' => url('/admin/automation/settings'), 'label' => 'Settings'],
];
?>
<div class="rounded-xl border border-gray-700 bg-gray-800 p-3">
    <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Automation Center</div>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($links as $link): ?>
            <?php
            $active = $currentPath === $link['url'];
            if (!$active && $link['url'] !== url('/admin/automation')) {
                $active = str_starts_with($currentPath, $link['url']);
            }
            ?>
            <a
                href="<?= htmlspecialchars($link['url']) ?>"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition <?= $active ? 'bg-indigo-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?>">
                <?= htmlspecialchars($link['label']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

