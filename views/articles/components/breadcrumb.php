<?php
/**
 * Breadcrumb Component
 * Generates SEO breadcrumb trail with structured data.
 * Variables: $breadcrumbs = [['label' => 'Home', 'url' => '/'], ...]
 */
?>
<nav aria-label="Breadcrumb" class="mb-5">
    <ol class="flex flex-wrap items-center gap-1 text-sm text-gray-500" itemscope
        itemtype="https://schema.org/BreadcrumbList">
        <?php foreach ($breadcrumbs as $i => $crumb): ?>
            <li class="flex items-center gap-1" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <?php if ($i < count($breadcrumbs) - 1): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" class="hover:text-blue-600 transition-colors"
                        itemprop="item">
                        <span itemprop="name">
                            <?= htmlspecialchars($crumb['label']) ?>
                        </span>
                    </a>
                    <span class="text-gray-300" aria-hidden="true">›</span>
                <?php else: ?>
                    <span class="text-gray-900 font-medium" itemprop="name" aria-current="page">
                        <?= htmlspecialchars($crumb['label']) ?>
                    </span>
                <?php endif; ?>
                <meta itemprop="position" content="<?= $i + 1 ?>">
            </li>
        <?php endforeach; ?>
    </ol>
</nav>