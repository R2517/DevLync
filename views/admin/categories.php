<?php
/**
 * Admin Categories View
 * Variables: $categories (array)
 */
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold dark:text-white text-gray-900">All Categories</h2>
            <p class="text-sm dark:text-white/40 text-gray-500 mt-0.5"><?= count($categories) ?> categories total</p>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($categories as $cat): ?>
            <div class="group rounded-2xl dark:bg-white/[0.03] bg-white border dark:border-white/5 border-gray-200 p-5 hover:shadow-glow transition-all duration-300">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                        <i data-lucide="<?= htmlspecialchars($cat['icon'] ?? 'folder') ?>" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold dark:text-white text-gray-900 text-sm group-hover:text-blue-500 transition-colors"><?= htmlspecialchars($cat['name']) ?></h3>
                        <p class="text-xs dark:text-white/40 text-gray-400 mt-0.5">/category/<?= htmlspecialchars($cat['slug']) ?></p>
                        <?php if (!empty($cat['description'])): ?>
                            <p class="text-xs dark:text-white/50 text-gray-500 mt-2 line-clamp-2"><?= htmlspecialchars($cat['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-4 pt-3 border-t dark:border-white/5 border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium dark:text-white/50 text-gray-500">
                            <i data-lucide="file-text" class="w-3 h-3"></i>
                            <?= (int)($cat['articles_count'] ?? 0) ?> articles
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium dark:text-white/50 text-gray-500">
                            <i data-lucide="arrow-up-down" class="w-3 h-3"></i>
                            Order: <?= (int)($cat['sort_order'] ?? 0) ?>
                        </span>
                    </div>
                    <a href="<?= url('/category/' . htmlspecialchars($cat['slug'])) ?>" target="_blank"
                        class="text-xs dark:text-accent-400 text-blue-600 hover:underline flex items-center gap-1">
                        View <i data-lucide="external-link" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($categories)): ?>
        <div class="text-center py-16">
            <i data-lucide="folder-open" class="w-12 h-12 dark:text-white/20 text-gray-300 mx-auto mb-3"></i>
            <p class="dark:text-white/40 text-gray-500 text-sm">No categories found. Categories are created via the API.</p>
        </div>
    <?php endif; ?>
</div>
