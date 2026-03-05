<!DOCTYPE html>
<html lang="en" class="scroll-smooth" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }" :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Primary SEO Meta -->
    <title><?= htmlspecialchars($meta['title'] ?? 'DevLync — Developer Tools Discovery & Reviews') ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta['description'] ?? 'DevLync is the go-to platform for honest developer tool reviews, comparisons, and news.') ?>">
    <?php if (!empty($meta['canonical'])): ?>
        <link rel="canonical" href="<?= htmlspecialchars($meta['canonical']) ?>">
    <?php endif; ?>
    <?php if (!empty($meta['robots'])): ?>
        <meta name="robots" content="<?= htmlspecialchars($meta['robots']) ?>">
    <?php else: ?>
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type" content="<?= htmlspecialchars($meta['og_type'] ?? 'website') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($meta['title'] ?? 'DevLync') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta['description'] ?? '') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($meta['canonical'] ?? 'https://devlync.com') ?>">
    <meta property="og:site_name" content="DevLync">
    <?php if (!empty($meta['og_image'])): ?>
        <meta property="og:image" content="<?= htmlspecialchars($meta['og_image']) ?>">
        <meta property="og:image:alt" content="<?= htmlspecialchars($meta['og_image_alt'] ?? '') ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($meta['title'] ?? 'DevLync') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($meta['description'] ?? '') ?>">
    <?php if (!empty($meta['og_image'])): ?>
        <meta name="twitter:image" content="<?= htmlspecialchars($meta['og_image']) ?>">
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= url('/assets/images/favicon.svg') ?>">
    <link rel="icon" type="image/png" href="<?= url('/assets/images/favicon.png') ?>">

    <!-- RSS Feed -->
    <link rel="alternate" type="application/rss+xml" title="DevLync Feed" href="<?= url('/feed') ?>">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Space Grotesk', 'Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: { 50:'#eff6ff', 100:'#dbeafe', 400:'#60a5fa', 500:'#3b82f6', 600:'#2563eb', 700:'#1d4ed8', 800:'#1e40af', 900:'#1e3a8a' }
                    },
                    boxShadow: {
                        glow: '0 0 20px rgba(59,130,246,0.15)',
                        'glow-lg': '0 0 40px rgba(59,130,246,0.2)',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">

    <!-- Schema Markup (JSON-LD) -->
    <?= $schemaMarkup ?? '' ?>
</head>

<body class="dark:bg-gray-950 bg-white dark:text-gray-100 text-gray-900 font-sans antialiased transition-colors duration-300">

    <!-- ═══ HEADER / NAV ═══ -->
    <header class="sticky top-0 z-50 transition-all duration-300" id="site-header"
        x-data="{ mobileOpen: false, scrolled: false }"
        x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 20 }, { passive: true })"
        :class="scrolled ? 'dark:bg-gray-950/80 bg-white/80 backdrop-blur-xl shadow-lg dark:shadow-gray-950/50 border-b dark:border-white/5 border-gray-100' : 'dark:bg-transparent bg-transparent'">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">

            <!-- Logo -->
            <a href="<?= url('/') ?>" class="flex items-center gap-2.5 group" aria-label="DevLync Home">
                <div class="w-9 h-9 bg-gradient-to-br from-brand-500 to-blue-700 rounded-xl flex items-center justify-center group-hover:shadow-glow transition-all duration-300">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                </div>
                <span class="text-xl font-extrabold dark:text-white text-gray-900">Dev<span class="text-brand-500">Lync</span></span>
            </a>

            <!-- Desktop Nav (centered) -->
            <div class="hidden md:flex items-center gap-1 font-display text-[15px] font-bold tracking-wide dark:bg-white/5 bg-gray-100 rounded-full px-2 py-1.5">
                <?php
                $navLinks = [
                    ['/blog', 'Blog'], ['/reviews', 'Reviews'], ['/comparisons', 'Comparisons'],
                    ['/news', 'News'], ['/about', 'About'],
                ];
                $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                foreach ($navLinks as [$navPath, $navLabel]):
                    $isActive = str_starts_with($currentUri, url($navPath));
                ?>
                    <a href="<?= url($navPath) ?>"
                        class="px-5 py-2 rounded-full transition-all duration-200 <?= $isActive ? 'bg-brand-500 text-white shadow-glow' : 'dark:text-gray-400 text-gray-600 hover:dark:text-white hover:text-gray-900 hover:dark:bg-white/10 hover:bg-white' ?>">
                        <?= $navLabel ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Right: Search + Theme + Mobile -->
            <div class="flex items-center gap-2">
                <a href="<?= url('/search') ?>" class="p-2 rounded-lg dark:text-gray-400 text-gray-500 hover:dark:text-white hover:text-gray-900 dark:hover:bg-white/5 hover:bg-gray-100 transition-colors" aria-label="Search">
                    <i data-lucide="search" class="w-5 h-5"></i>
                </a>
                <!-- Dark/Light Toggle -->
                <button @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')"
                    class="p-2 rounded-lg dark:text-gray-400 text-gray-500 hover:dark:text-white hover:text-gray-900 dark:hover:bg-white/5 hover:bg-gray-100 transition-colors" aria-label="Toggle theme">
                    <i data-lucide="moon" class="w-5 h-5" x-show="!darkMode"></i>
                    <i data-lucide="sun" class="w-5 h-5" x-show="darkMode" style="display:none"></i>
                </button>
                <!-- Mobile menu button -->
                <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 transition-colors" aria-label="Toggle menu">
                    <i data-lucide="menu" class="w-5 h-5" x-show="!mobileOpen"></i>
                    <i data-lucide="x" class="w-5 h-5" x-show="mobileOpen" style="display:none"></i>
                </button>
            </div>
        </nav>

        <!-- Mobile Menu -->
        <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            class="md:hidden border-t dark:border-white/5 border-gray-100 dark:bg-gray-950/95 bg-white/95 backdrop-blur-xl px-4 py-3 space-y-1" style="display:none">
            <?php foreach ($navLinks as [$navPath, $navLabel]): ?>
                <a href="<?= url($navPath) ?>" class="block px-4 py-2.5 rounded-xl dark:text-gray-300 text-gray-700 hover:dark:text-white hover:text-brand-600 hover:dark:bg-white/5 hover:bg-brand-50 font-display font-bold text-[15px] tracking-wide transition-all"><?= $navLabel ?></a>
            <?php endforeach; ?>
        </div>
    </header>

    <!-- ═══ MAIN CONTENT ═══ -->
    <main>
        <?= $content ?>
    </main>

    <!-- ═══ FOOTER ═══ -->
    <footer class="relative dark:bg-gray-950 bg-gray-950 text-gray-400 mt-20 overflow-hidden">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-brand-500/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-purple-500/5 rounded-full blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-10">
            <!-- Column 1: Brand -->
            <div class="sm:col-span-2 md:col-span-1">
                <a href="<?= url('/') ?>" class="flex items-center gap-2.5 mb-4 group">
                    <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-blue-700 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <span class="text-white font-extrabold text-lg">Dev<span class="text-brand-400">Lync</span></span>
                </a>
                <p class="text-sm leading-relaxed text-gray-500">Developer tools discovery platform. Honest reviews, detailed comparisons, and the latest news to help you build better.</p>
                <div class="mt-5 flex items-center gap-3">
                    <a href="<?= url('/feed') ?>" class="text-xs text-gray-500 hover:text-orange-400 transition-colors flex items-center gap-1.5 bg-white/5 px-3 py-1.5 rounded-full">
                        <i data-lucide="rss" class="w-3 h-3"></i> RSS Feed
                    </a>
                </div>
            </div>

            <!-- Column 2: Content -->
            <div>
                <h4 class="text-white font-bold mb-5 text-xs uppercase tracking-widest">Content</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="<?= url('/blog') ?>" class="hover:text-white hover:translate-x-1 inline-block transition-all duration-200">Blog</a></li>
                    <li><a href="<?= url('/reviews') ?>" class="hover:text-white hover:translate-x-1 inline-block transition-all duration-200">Reviews</a></li>
                    <li><a href="<?= url('/comparisons') ?>" class="hover:text-white hover:translate-x-1 inline-block transition-all duration-200">Comparisons</a></li>
                    <li><a href="<?= url('/news') ?>" class="hover:text-white hover:translate-x-1 inline-block transition-all duration-200">News</a></li>
                </ul>
            </div>

            <!-- Column 3: Categories -->
            <div>
                <h4 class="text-white font-bold mb-5 text-xs uppercase tracking-widest">Categories</h4>
                <ul class="space-y-2.5 text-sm">
                    <?php if (!empty($footerCategories)): ?>
                        <?php foreach (array_slice($footerCategories, 0, 6) as $cat): ?>
                            <li><a href="<?= url('/category/' . htmlspecialchars($cat['slug'])) ?>" class="hover:text-white hover:translate-x-1 inline-block transition-all duration-200"><?= htmlspecialchars($cat['name']) ?></a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="<?= url('/category/code-editors') ?>" class="hover:text-white transition-colors">Code Editors</a></li>
                        <li><a href="<?= url('/category/ai-developer-tools') ?>" class="hover:text-white transition-colors">AI Dev Tools</a></li>
                        <li><a href="<?= url('/category/devops-tools') ?>" class="hover:text-white transition-colors">DevOps Tools</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Column 4: About/Legal -->
            <div>
                <h4 class="text-white font-bold mb-5 text-xs uppercase tracking-widest">About</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="<?= url('/about') ?>" class="hover:text-white hover:translate-x-1 inline-block transition-all duration-200">About Us</a></li>
                    <li><a href="<?= url('/editorial-policy') ?>" class="hover:text-white hover:translate-x-1 inline-block transition-all duration-200">Editorial Policy</a></li>
                    <li><a href="<?= url('/fact-checking-policy') ?>" class="hover:text-white hover:translate-x-1 inline-block transition-all duration-200">Fact-Checking</a></li>
                    <li><a href="<?= url('/affiliate-disclosure') ?>" class="hover:text-white hover:translate-x-1 inline-block transition-all duration-200">Affiliate Disclosure</a></li>
                    <li><a href="<?= url('/contact') ?>" class="hover:text-white hover:translate-x-1 inline-block transition-all duration-200">Contact</a></li>
                    <li><a href="<?= url('/privacy-policy') ?>" class="hover:text-white hover:translate-x-1 inline-block transition-all duration-200">Privacy Policy</a></li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="relative border-t border-white/5 py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-600">
                <span>&copy; <?= date('Y') ?> DevLync. All rights reserved.</span>
                <span>Developer tools discovery & reviews platform</span>
            </div>
        </div>
    </footer>

    <!-- Custom JS -->
    <script src="<?= url('/assets/js/app.js') ?>"></script>
    <!-- Lucide Icons Init -->
    <script>lucide.createIcons();</script>
</body>

</html>