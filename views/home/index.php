<?php
/**
 * Homepage — DevLync.com
 * Variables: $latestArticles, $latestReviews, $latestComparisons, $latestNews, $categories
 */
$urlPrefixes = ['blog' => 'blog', 'review' => 'reviews', 'comparison' => 'comparisons', 'news' => 'news'];
$typeColors = ['blog' => 'blue', 'review' => 'purple', 'comparison' => 'emerald', 'news' => 'rose'];
?>

<!-- ═══ HERO with Featured Slider ═══ -->
<section class="relative overflow-hidden bg-gradient-to-br from-gray-950 via-blue-950 to-indigo-950 text-white py-20 sm:py-28 px-4"
    x-data="{ current: 0, total: <?= count($latestArticles) ?> }"
    x-init="setInterval(() => current = (current + 1) % total, 4500)">
    <div class="absolute inset-0">
        <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-purple-500/5 rounded-full blur-3xl"></div>
    </div>
    <div class="relative max-w-7xl mx-auto">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <!-- Left: Text -->
            <div class="text-center lg:text-left">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-full px-4 py-1.5 text-sm font-medium mb-6">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    Honest, independent tool reviews
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-[1.1] mb-6 tracking-tight">
                    Find the Right<br><span class="bg-gradient-to-r from-blue-400 via-blue-300 to-indigo-400 bg-clip-text text-transparent">Developer Tools</span><br>Without the Guesswork
                </h1>
                <p class="text-lg text-blue-200/70 max-w-lg mb-8 leading-relaxed">
                    DevLync tests, reviews, and compares developer tools so you don't have to. Honest ratings, real benchmarks, zero bias.
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
                    <a href="<?= url('/reviews') ?>" class="btn-glow bg-white text-blue-900 font-bold px-7 py-3.5 rounded-2xl hover:bg-blue-50 transition-all shadow-lg shadow-white/10">
                        Browse Reviews
                    </a>
                    <a href="<?= url('/comparisons') ?>" class="border-2 border-white/20 text-white font-semibold px-7 py-3.5 rounded-2xl hover:bg-white/10 hover:border-white/40 transition-all backdrop-blur-sm">
                        See Comparisons
                    </a>
                </div>
            </div>

            <!-- Right: Sliding Featured Cards -->
            <?php if (!empty($latestArticles)): ?>
            <div class="hidden lg:block relative h-80">
                <?php foreach ($latestArticles as $i => $article):
                    $prefix = $urlPrefixes[$article['content_type']] ?? 'blog';
                ?>
                    <a href="<?= url('/' . $prefix . '/' . htmlspecialchars($article['slug'])) ?>"
                        x-show="current === <?= $i ?>"
                        x-transition:enter="transition ease-out duration-500"
                        x-transition:enter-start="opacity-0 translate-x-8 scale-95"
                        x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0 -translate-x-8 scale-95"
                        class="absolute inset-0 bg-white/5 backdrop-blur-md border border-white/10 rounded-3xl p-6 flex flex-col justify-end hover:bg-white/10 transition-colors group"
                        style="<?= $i > 0 ? 'display:none' : '' ?>">
                        <?php if ($article['featured_image_url']): ?>
                            <img src="<?= htmlspecialchars($article['featured_image_url']) ?>" alt="" class="absolute inset-0 w-full h-full object-cover rounded-3xl opacity-20 group-hover:opacity-30 transition-opacity" loading="lazy">
                        <?php endif; ?>
                        <div class="relative z-10">
                            <span class="inline-block bg-<?= $typeColors[$article['content_type']] ?? 'blue' ?>-500/30 text-<?= $typeColors[$article['content_type']] ?? 'blue' ?>-300 text-xs font-bold px-3 py-1 rounded-full mb-3 uppercase tracking-wider">
                                <?= ucfirst($article['content_type']) ?>
                            </span>
                            <h3 class="text-xl font-bold text-white line-clamp-2 mb-2 group-hover:text-blue-200 transition-colors"><?= htmlspecialchars($article['title']) ?></h3>
                            <?php if ($article['excerpt']): ?>
                                <p class="text-blue-200/50 text-sm line-clamp-2"><?= htmlspecialchars($article['excerpt']) ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>

                <!-- Dots -->
                <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 flex gap-2">
                    <?php for ($i = 0; $i < min(count($latestArticles), 6); $i++): ?>
                        <button @click="current = <?= $i ?>" class="w-2 h-2 rounded-full transition-all duration-300" :class="current === <?= $i ?> ? 'bg-white w-6' : 'bg-white/30 hover:bg-white/50'"></button>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══ LATEST ARTICLES ═══ -->
<?php if (!empty($latestArticles)): ?>
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold dark:text-white text-gray-900">Latest from DevLync</h2>
                <p class="dark:text-gray-500 text-gray-500 text-sm mt-1">Fresh reviews, comparisons, and developer news</p>
            </div>
            <a href="<?= url('/blog') ?>" class="text-sm font-semibold text-blue-500 hover:text-blue-400 btn-glow px-4 py-2 rounded-xl">View all →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($latestArticles as $article):
                $prefix = $urlPrefixes[$article['content_type']] ?? 'blog';
                $color = $typeColors[$article['content_type']] ?? 'blue';
            ?>
                <article class="article-card dark:bg-white/[0.03] bg-white rounded-2xl dark:border-white/5 border border-gray-100 overflow-hidden group">
                    <a href="<?= url('/' . $prefix . '/' . htmlspecialchars($article['slug'])) ?>" class="block h-48 dark:bg-gray-800 bg-gray-100 overflow-hidden">
                        <?php if ($article['featured_image_url']): ?>
                            <img src="<?= htmlspecialchars($article['featured_image_url']) ?>" alt="<?= htmlspecialchars($article['featured_image_alt'] ?? $article['title']) ?>"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy"
                                onerror="this.src='<?= url('/assets/images/placeholder.svg') ?>'">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center"><i data-lucide="image" class="w-10 h-10 dark:text-gray-600 text-gray-300"></i></div>
                        <?php endif; ?>
                    </a>
                    <div class="p-5">
                        <span class="inline-block dark:bg-<?= $color ?>-500/15 bg-<?= $color ?>-100 dark:text-<?= $color ?>-400 text-<?= $color ?>-700 text-xs font-bold px-2.5 py-1 rounded-full mb-3 uppercase tracking-wider"><?= ucfirst($article['content_type']) ?></span>
                        <h3 class="font-bold dark:text-white text-gray-900 line-clamp-2 mb-2 leading-snug">
                            <a href="<?= url('/' . $prefix . '/' . htmlspecialchars($article['slug'])) ?>" class="hover:text-blue-500 transition-colors"><?= htmlspecialchars($article['title']) ?></a>
                        </h3>
                        <?php if ($article['excerpt']): ?>
                            <p class="dark:text-gray-500 text-gray-500 text-sm line-clamp-2 mb-3"><?= htmlspecialchars($article['excerpt']) ?></p>
                        <?php endif; ?>
                        <div class="flex items-center justify-between text-xs dark:text-gray-600 text-gray-400">
                            <span><?= htmlspecialchars($article['author_name'] ?? 'DevLync Team') ?></span>
                            <span><?= $article['reading_time'] ? $article['reading_time'] . ' min read' : '' ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- ═══ MUST READ REVIEWS ═══ -->
<?php if (!empty($latestReviews)): ?>
    <section class="dark:bg-white/[0.02] bg-gray-50 py-16 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i data-lucide="star" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold dark:text-white text-gray-900">Must Read Reviews</h2>
                        <p class="dark:text-gray-500 text-gray-500 text-sm mt-0.5">Deeply tested tool reviews with honest ratings</p>
                    </div>
                </div>
                <a href="<?= url('/reviews') ?>" class="text-sm font-semibold text-blue-500 hover:text-blue-400">All reviews →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <?php foreach ($latestReviews as $review): ?>
                    <a href="<?= url('/reviews/' . htmlspecialchars($review['slug'])) ?>"
                        class="article-card dark:bg-white/[0.03] bg-white rounded-2xl dark:border-white/5 border border-gray-100 p-5 block group">
                        <?php if ($review['featured_image_url']): ?>
                            <div class="overflow-hidden rounded-xl mb-3">
                                <img src="<?= htmlspecialchars($review['featured_image_url']) ?>" alt="<?= htmlspecialchars($review['featured_image_alt'] ?? '') ?>"
                                    class="w-full h-32 object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                            </div>
                        <?php endif; ?>
                        <h3 class="font-bold dark:text-white text-gray-900 line-clamp-2 text-sm leading-snug mb-2 group-hover:text-blue-500 transition-colors"><?= htmlspecialchars($review['title']) ?></h3>
                        <?php if ($review['overall_rating']): ?>
                            <div class="flex items-center gap-1.5 mt-auto">
                                <span class="text-lg font-black bg-gradient-to-r from-blue-500 to-purple-500 bg-clip-text text-transparent"><?= number_format((float) $review['overall_rating'], 1) ?></span>
                                <span class="dark:text-gray-600 text-gray-400 text-xs font-medium">/10</span>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- ═══ COMPARISONS ═══ -->
<?php if (!empty($latestComparisons)): ?>
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i data-lucide="git-compare" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold dark:text-white text-gray-900">Trending Comparisons</h2>
                    <p class="dark:text-gray-500 text-gray-500 text-sm mt-0.5">Side-by-side tool comparisons with clear winners</p>
                </div>
            </div>
            <a href="<?= url('/comparisons') ?>" class="text-sm font-semibold text-blue-500 hover:text-blue-400">All comparisons →</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($latestComparisons as $comparison): ?>
                <a href="<?= url('/comparisons/' . htmlspecialchars($comparison['slug'])) ?>"
                    class="article-card group dark:bg-gradient-to-br dark:from-emerald-500/10 dark:to-teal-500/5 bg-gradient-to-br from-emerald-50 to-teal-50 dark:border-emerald-500/10 border border-emerald-100 rounded-2xl p-6 block hover:dark:from-emerald-500/15 hover:from-emerald-100 transition-all">
                    <span class="inline-block dark:bg-emerald-500/20 bg-emerald-100 dark:text-emerald-400 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full mb-3 uppercase tracking-wider">Comparison</span>
                    <h3 class="font-bold dark:text-white text-gray-900 line-clamp-2 text-base group-hover:text-emerald-500 transition-colors"><?= htmlspecialchars($comparison['title']) ?></h3>
                    <?php if ($comparison['excerpt']): ?>
                        <p class="dark:text-gray-400 text-gray-600 text-sm mt-2 line-clamp-2"><?= htmlspecialchars($comparison['excerpt']) ?></p>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- ═══ LATEST NEWS ═══ -->
<?php if (!empty($latestNews)): ?>
    <section class="dark:bg-white/[0.02] bg-gray-50 py-16 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-rose-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i data-lucide="newspaper" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold dark:text-white text-gray-900">Developer News</h2>
                        <p class="dark:text-gray-500 text-gray-500 text-sm mt-0.5">Latest updates from the developer tools ecosystem</p>
                    </div>
                </div>
                <a href="<?= url('/news') ?>" class="text-sm font-semibold text-blue-500 hover:text-blue-400">All news →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <?php foreach ($latestNews as $item): ?>
                    <a href="<?= url('/news/' . htmlspecialchars($item['slug'])) ?>"
                        class="article-card dark:bg-white/[0.03] bg-white rounded-2xl dark:border-white/5 border border-gray-100 p-5 block group">
                        <span class="inline-block dark:bg-rose-500/15 bg-rose-100 dark:text-rose-400 text-rose-700 text-xs font-bold px-2.5 py-1 rounded-full mb-3 uppercase tracking-wider">News</span>
                        <h3 class="font-bold dark:text-white text-gray-900 line-clamp-3 text-sm leading-snug group-hover:text-blue-500 transition-colors"><?= htmlspecialchars($item['title']) ?></h3>
                        <p class="dark:text-gray-600 text-gray-400 text-xs mt-3"><?= $item['published_at'] ? date('M j, Y', strtotime($item['published_at'])) : '' ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- ═══ CATEGORIES ═══ -->
<?php if (!empty($categories)): ?>
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-bold dark:text-white text-gray-900">Browse by Category</h2>
            <p class="dark:text-gray-500 text-gray-500 mt-2">Find tools and reviews in your area of interest</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= url('/category/' . htmlspecialchars($cat['slug'])) ?>"
                    class="article-card flex flex-col items-center gap-3 p-5 rounded-2xl dark:bg-white/[0.03] bg-white dark:border-white/5 border border-gray-100 group text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-glow transition-all duration-300">
                        <i data-lucide="<?= htmlspecialchars($cat['icon'] ?? 'folder') ?>" class="w-5 h-5 text-white"></i>
                    </div>
                    <span class="text-sm font-semibold dark:text-white text-gray-700 group-hover:text-blue-500 leading-snug transition-colors"><?= htmlspecialchars($cat['name']) ?></span>
                    <?php if ($cat['articles_count']): ?>
                        <span class="text-xs dark:text-gray-600 text-gray-400"><?= (int) $cat['articles_count'] ?> articles</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- ═══ TRUST SIGNALS (EEAT) ═══ -->
<section class="dark:border-white/5 border-t border-gray-100 py-16 dark:bg-white/[0.01] bg-white transition-colors">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 text-center">
        <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-2">Why Trust DevLync?</h2>
        <p class="dark:text-gray-500 text-gray-500 text-sm mb-10">We install every tool, run real tests, and write honest reviews</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
            <div class="flex flex-col items-center gap-3">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl flex items-center justify-center shadow-lg">
                    <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="font-bold dark:text-white text-gray-900">Independent Reviews</h3>
                <p class="text-sm dark:text-gray-500 text-gray-500">No paid placements. Every tool is evaluated on merit alone.</p>
            </div>
            <div class="flex flex-col items-center gap-3">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl flex items-center justify-center shadow-lg">
                    <i data-lucide="test-tube" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="font-bold dark:text-white text-gray-900">Real-World Testing</h3>
                <p class="text-sm dark:text-gray-500 text-gray-500">We use tools in actual projects before writing a single word.</p>
            </div>
            <div class="flex flex-col items-center gap-3">
                <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl flex items-center justify-center shadow-lg">
                    <i data-lucide="refresh-cw" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="font-bold dark:text-white text-gray-900">Kept Up To Date</h3>
                <p class="text-sm dark:text-gray-500 text-gray-500">Reviews are updated when tools change. We check regularly.</p>
            </div>
        </div>
        <div class="mt-10 flex justify-center gap-6 text-sm dark:text-gray-600 text-gray-500">
            <a href="<?= url('/editorial-policy') ?>" class="hover:text-blue-500 underline decoration-dotted transition-colors">Editorial Policy</a>
            <a href="<?= url('/fact-checking-policy') ?>" class="hover:text-blue-500 underline decoration-dotted transition-colors">Fact-Checking</a>
            <a href="<?= url('/affiliate-disclosure') ?>" class="hover:text-blue-500 underline decoration-dotted transition-colors">Affiliate Disclosure</a>
        </div>
    </div>
</section>