<?php
/**
 * Admin Articles List View
 * Variables: $result (array), $status (string|null), $type (string|null), $page (int)
 */
$items = $result['items'] ?? [];
$total = $result['total'] ?? 0;
$pages = $result['pages'] ?? 1;
$types = ['blog', 'review', 'comparison', 'news'];
$typeMap = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'];
$statuses = ['draft', 'review', 'published', 'archived'];
$statusColors = [
    'draft' => 'yellow',
    'review' => 'blue',
    'published' => 'green',
    'archived' => 'gray',
];
?>
<div class="space-y-5">
    <!-- Filters -->
    <div class="flex flex-wrap gap-3 items-center">
        <a href="<?= url('/admin/articles/create') ?>"
            class="text-xs px-4 py-1.5 rounded-lg font-bold bg-blue-600 text-white hover:bg-blue-500 transition-colors shadow-sm">
            + Create Article
        </a>
        <div class="w-px h-6 bg-gray-700 mx-1"></div>
        <?php foreach ($statuses as $s): ?>
            <a href="<?= url('/admin/articles') ?>?status=<?= $s ?><?= $type ? '&type=' . $type : '' ?>"
                class="text-xs px-3 py-1.5 rounded-lg font-medium transition-colors <?= ($status ?? '') === $s ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white' ?>">
                <?= ucfirst($s) ?>
            </a>
        <?php endforeach; ?>
        <a href="<?= url('/admin/articles') ?>"
            class="text-xs px-3 py-1.5 rounded-lg font-medium bg-gray-800 text-gray-400 hover:text-white transition-colors">All</a>
        <div class="flex-1"></div>
        <?php foreach ($types as $t): ?>
            <a href="<?= url('/admin/articles') ?>?type=<?= $t ?><?= $status ? '&status=' . $status : '' ?>"
                class="text-xs px-3 py-1.5 rounded-lg font-medium transition-colors <?= ($type ?? '') === $t ? 'bg-purple-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white' ?>">
                <?= ucfirst($t) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <p class="text-xs text-gray-500">
        <?= number_format($total) ?> articles
    </p>

    <!-- Table -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <?php if (empty($items)): ?>
            <div class="text-center py-12 text-gray-500 text-sm">No articles found.</div>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead class="border-b border-gray-800">
                    <tr class="text-left text-xs text-gray-400 uppercase tracking-wider">
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Author</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <?php foreach ($items as $a): ?>
                        <tr class="hover:bg-gray-800/50">
                            <td class="px-4 py-3">
                                <a href="<?= url('/' . ($typeMap[$a['content_type']] ?? 'blog') . '/' . htmlspecialchars($a['slug'])) ?>"
                                    target="_blank" class="text-white hover:text-blue-400 line-clamp-1 font-medium">
                                    <?= htmlspecialchars($a['title']) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs capitalize text-gray-400">
                                    <?= $a['content_type'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <?php $sc = $statusColors[$a['status']] ?? 'gray'; ?>
                                <span
                                    class="text-xs bg-<?= $sc ?>-900/50 text-<?= $sc ?>-400 border border-<?= $sc ?>-700/30 px-2 py-0.5 rounded-full capitalize">
                                    <?= $a['status'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-xs">
                                <?= htmlspecialchars($a['author_name'] ?? '—') ?>
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-xs">
                                <?= $a['published_at'] ? date('M j, Y', strtotime($a['published_at'])) : date('M j, Y', strtotime($a['created_at'])) ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <?php if ($a['status'] === 'draft' || $a['status'] === 'review'): ?>
                                        <a href="<?= url('/admin/articles/' . $a['id'] . '/publish') ?>"
                                            class="text-xs text-green-400 hover:text-green-300 font-medium">Publish</a>
                                    <?php endif; ?>
                                    <a href="<?= url('/admin/articles/' . $a['id'] . '/edit') ?>"
                                        class="text-xs text-blue-400 hover:text-blue-300 font-medium">Edit</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
        <nav class="flex gap-2 justify-center">
            <?php if ($page > 1): ?>
                <a href="<?= url('/admin/articles') ?>?page=<?= $page - 1 ?><?= $status ? '&status=' . $status : '' ?><?= $type ? '&type=' . $type : '' ?>"
                    class="px-3 py-1.5 bg-gray-800 text-gray-400 rounded-lg text-xs hover:text-white">← Prev</a>
            <?php endif; ?>
            <?php if ($page < $pages): ?>
                <a href="<?= url('/admin/articles') ?>?page=<?= $page + 1 ?><?= $status ? '&status=' . $status : '' ?><?= $type ? '&type=' . $type : '' ?>"
                    class="px-3 py-1.5 bg-gray-800 text-gray-400 rounded-lg text-xs hover:text-white">Next →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>