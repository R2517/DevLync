<?php
/**
 * Admin Dashboard View
 * Variables: $stats (array)
 */
$gradients = [
    ['from-blue-500/20 to-blue-600/5', 'from-blue-500 to-blue-600', 'text-blue-400', 'border-blue-500/20'],
    ['from-purple-500/20 to-purple-600/5', 'from-purple-500 to-purple-600', 'text-purple-400', 'border-purple-500/20'],
    ['from-emerald-500/20 to-emerald-600/5', 'from-emerald-500 to-emerald-600', 'text-emerald-400', 'border-emerald-500/20'],
    ['from-rose-500/20 to-rose-600/5', 'from-rose-500 to-rose-600', 'text-rose-400', 'border-rose-500/20'],
    ['from-amber-500/20 to-amber-600/5', 'from-amber-500 to-amber-600', 'text-amber-400', 'border-amber-500/20'],
    ['from-teal-500/20 to-teal-600/5', 'from-teal-500 to-teal-600', 'text-teal-400', 'border-teal-500/20'],
    ['from-orange-500/20 to-orange-600/5', 'from-orange-500 to-orange-600', 'text-orange-400', 'border-orange-500/20'],
    ['from-pink-500/20 to-pink-600/5', 'from-pink-500 to-pink-600', 'text-pink-400', 'border-pink-500/20'],
];
?>
<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="relative overflow-hidden rounded-2xl dark:bg-gradient-to-r dark:from-accent-600/20 dark:via-purple-600/10 dark:to-surface-950 bg-gradient-to-r from-accent-500/10 via-purple-500/5 to-white border dark:border-white/5 border-gray-200 p-6 animate-in">
        <div class="absolute top-0 right-0 w-64 h-64 bg-accent-500/5 rounded-full blur-3xl -mr-20 -mt-20"></div>
        <div class="relative">
            <h2 class="text-xl font-bold dark:text-white text-gray-900">Welcome back, Admin</h2>
            <p class="text-sm dark:text-white/50 text-gray-500 mt-1">Here is your DevLync content overview for <?= date('F j, Y') ?></p>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        $cards = [
            ['Articles', $stats['articles'], 'file-text'],
            ['Reviews', $stats['reviews'], 'star'],
            ['Comparisons', $stats['comparisons'], 'git-compare'],
            ['News', $stats['news'], 'newspaper'],
            ['Pending', $stats['pending'], 'clock'],
            ['Affiliates', $stats['affiliates'], 'link-2'],
            ['Cost Today', '$' . number_format((float) $stats['cost_today'], 2), 'zap'],
            ['Cost/Month', '$' . number_format((float) $stats['cost_month'], 2), 'credit-card'],
        ];
        foreach ($cards as $i => [$label, $value, $icon]):
            $g = $gradients[$i];
        ?>
            <div class="group relative overflow-hidden rounded-2xl dark:bg-gradient-to-br dark:<?= $g[0] ?> dark:border dark:<?= $g[3] ?> bg-white border border-gray-200 p-4 hover:shadow-glow transition-all duration-300 animate-in" style="animation-delay: <?= $i * 0.04 ?>s">
                <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br <?= $g[1] ?> rounded-full blur-2xl opacity-10 group-hover:opacity-20 transition-opacity duration-300 -mr-5 -mt-5"></div>
                <div class="relative flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br <?= $g[1] ?> flex items-center justify-center shadow-lg flex-shrink-0">
                        <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <div class="text-xl font-extrabold dark:text-white text-gray-900"><?= $value ?></div>
                        <div class="text-[11px] font-medium dark:text-white/40 text-gray-400 uppercase tracking-wide"><?= $label ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Roadmap Status -->
        <div class="rounded-2xl dark:bg-white/[0.03] bg-white border dark:border-white/5 border-gray-200 p-5 hover:shadow-glow transition-all duration-300 animate-in animate-in-delay-1">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-accent-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="map" class="w-3.5 h-3.5 text-white"></i>
                </div>
                <h3 class="font-bold text-sm dark:text-white text-gray-900">Roadmap Status</h3>
            </div>
            <div class="space-y-3">
                <?php
                $roadmapColors = ['planned' => 'bg-blue-500', 'in_progress' => 'bg-amber-500', 'completed' => 'bg-emerald-500', 'cancelled' => 'bg-gray-500'];
                $roadmapTotal = array_sum($stats['roadmap']) ?: 1;
                foreach ($stats['roadmap'] as $status => $count):
                    $pct = round(($count / $roadmapTotal) * 100);
                    $barColor = $roadmapColors[$status] ?? 'bg-gray-500';
                ?>
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="dark:text-white/60 text-gray-500 capitalize font-medium"><?= str_replace('_', ' ', $status) ?></span>
                            <span class="dark:text-white font-bold text-gray-900"><?= $count ?></span>
                        </div>
                        <div class="h-1.5 dark:bg-white/5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full <?= $barColor ?> rounded-full transition-all duration-700" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Knowledge -->
        <div class="col-span-1 lg:col-span-2 rounded-2xl dark:bg-white/[0.03] bg-white border dark:border-white/5 border-gray-200 p-5 hover:shadow-glow transition-all duration-300 animate-in animate-in-delay-2">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                    <i data-lucide="brain" class="w-3.5 h-3.5 text-white"></i>
                </div>
                <h3 class="font-bold text-sm dark:text-white text-gray-900">Recent Knowledge Items</h3>
            </div>
            <div class="space-y-1">
                <?php foreach ($stats['recent_knowledge'] as $ki): ?>
                    <div class="flex items-center gap-3 py-2.5 border-b dark:border-white/5 border-gray-100 last:border-0 group/item">
                        <span class="text-[10px] font-semibold uppercase tracking-wider dark:text-accent-400 text-accent-600 dark:bg-accent-500/10 bg-accent-500/5 px-2.5 py-1 rounded-lg flex-shrink-0">
                            <?= htmlspecialchars($ki['source_type']) ?>
                        </span>
                        <p class="text-sm dark:text-white/70 text-gray-600 line-clamp-1 group-hover/item:dark:text-white group-hover/item:text-gray-900 transition-colors">
                            <?= htmlspecialchars($ki['title']) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="rounded-2xl dark:bg-white/[0.03] bg-white border dark:border-white/5 border-gray-200 p-5 animate-in" style="animation-delay: 0.2s">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                <i data-lucide="zap" class="w-3.5 h-3.5 text-white"></i>
            </div>
            <h3 class="font-bold text-sm dark:text-white text-gray-900">Quick Actions</h3>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= url('/admin/articles?status=draft') ?>"
                class="inline-flex items-center gap-2 text-xs font-semibold bg-gradient-to-r from-accent-500 to-purple-600 text-white px-4 py-2.5 rounded-xl hover:shadow-glow hover:scale-[1.02] transition-all duration-200">
                <i data-lucide="file-edit" class="w-3.5 h-3.5"></i> Draft Articles
            </a>
            <a href="<?= url('/admin/images') ?>"
                class="inline-flex items-center gap-2 text-xs font-semibold dark:bg-white/5 bg-gray-100 dark:text-white/70 text-gray-600 dark:hover:bg-white/10 hover:bg-gray-200 px-4 py-2.5 rounded-xl hover:scale-[1.02] transition-all duration-200">
                <i data-lucide="image" class="w-3.5 h-3.5"></i> Review Images
            </a>
            <a href="<?= url('/admin/roadmap') ?>"
                class="inline-flex items-center gap-2 text-xs font-semibold dark:bg-white/5 bg-gray-100 dark:text-white/70 text-gray-600 dark:hover:bg-white/10 hover:bg-gray-200 px-4 py-2.5 rounded-xl hover:scale-[1.02] transition-all duration-200">
                <i data-lucide="map" class="w-3.5 h-3.5"></i> Roadmap Queue
            </a>
            <a href="<?= url('/admin/supervisor') ?>"
                class="inline-flex items-center gap-2 text-xs font-semibold dark:bg-white/5 bg-gray-100 dark:text-white/70 text-gray-600 dark:hover:bg-white/10 hover:bg-gray-200 px-4 py-2.5 rounded-xl hover:scale-[1.02] transition-all duration-200">
                <i data-lucide="activity" class="w-3.5 h-3.5"></i> Supervisor
            </a>
            <a href="<?= url('/admin/cache-clear') ?>"
                class="inline-flex items-center gap-2 text-xs font-semibold dark:bg-orange-500/10 bg-orange-50 dark:text-orange-400 text-orange-600 dark:hover:bg-orange-500/20 hover:bg-orange-100 px-4 py-2.5 rounded-xl hover:scale-[1.02] transition-all duration-200 border dark:border-orange-500/20 border-orange-200"
                data-confirm="Clear all page cache?">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Clear Cache
            </a>
        </div>
    </div>
</div>