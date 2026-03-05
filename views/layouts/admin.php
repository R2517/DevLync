<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('admin-theme') !== 'light' }" :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($meta['title'] ?? 'Admin') ?> — DevLync Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="<?= url('/assets/images/favicon.svg') ?>">
    <link rel="icon" type="image/png" href="<?= url('/assets/images/favicon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        surface: { 50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 800: '#1e1b4b', 900: '#0f0b2d', 950: '#080620' },
                        accent: { 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5' },
                    },
                    boxShadow: {
                        glow: '0 0 20px rgba(99,102,241,0.15)',
                        'glow-lg': '0 0 40px rgba(99,102,241,0.2)',
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
        }

        .admin-sidebar {
            background: linear-gradient(180deg, #0f0b2d 0%, #1a1145 50%, #0f0b2d 100%);
        }

        .dark .admin-sidebar {
            background: linear-gradient(180deg, #0f0b2d 0%, #1a1145 50%, #0f0b2d 100%);
        }

        html:not(.dark) .admin-sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
        }

        .nav-glow {
            position: relative;
            overflow: hidden;
        }

        .nav-glow::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            opacity: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(139, 92, 246, 0.08));
            transition: opacity 0.3s ease;
        }

        .nav-glow:hover::before,
        .nav-glow.active::before {
            opacity: 1;
        }

        .nav-glow.active {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.1), inset 0 0 0 1px rgba(99, 102, 241, 0.15);
        }

        .glass-card {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .gradient-border {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.1)) padding-box, linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(139, 92, 246, 0.15)) border-box;
            border: 1px solid transparent;
        }

        .sidebar-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.2);
            border-radius: 4px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeInUp 0.4s ease both;
        }

        .animate-in-delay-1 {
            animation-delay: 0.05s;
        }

        .animate-in-delay-2 {
            animation-delay: 0.1s;
        }
    </style>
</head>

<body
    class="dark:bg-surface-950 bg-gray-50 dark:text-white text-gray-900 min-h-screen flex transition-colors duration-300"
    x-data="{ sidebarOpen: true }">

    <!-- ── Sidebar ── -->
    <aside
        class="admin-sidebar w-64 flex-shrink-0 flex flex-col min-h-screen fixed inset-y-0 left-0 z-40 border-r dark:border-white/5 border-gray-200 transition-all duration-300"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-64'">
        <!-- Logo -->
        <div class="px-5 py-5 flex items-center justify-between">
            <a href="<?= url('/admin') ?>" class="flex items-center gap-2.5 group">
                <div
                    class="w-9 h-9 bg-gradient-to-br from-accent-500 to-purple-600 rounded-xl flex items-center justify-center shadow-glow group-hover:shadow-glow-lg transition-shadow duration-300">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                </div>
                <div>
                    <span class="font-extrabold text-lg dark:text-white text-gray-900">Dev<span
                            class="text-accent-500">Lync</span></span>
                    <span
                        class="block text-[10px] font-medium dark:text-white/40 text-gray-400 -mt-0.5 tracking-wider uppercase">Admin
                        Panel</span>
                </div>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto sidebar-scrollbar">
            <p
                class="text-[10px] font-semibold dark:text-white/30 text-gray-400 uppercase tracking-widest px-3 mb-2 mt-1">
                Main</p>
            <?php
            $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $mainNav = [
                [url('/admin'), 'layout-dashboard', 'Dashboard', 'Overview & stats'],
                [url('/admin/automation'), 'bot', 'Automation', 'Workflow center'],
                [url('/admin/articles'), 'file-text', 'Articles', 'Manage content'],
                [url('/admin/categories'), 'grid-3x3', 'Categories', 'Organize topics'],
                [url('/admin/images'), 'image', 'Images', 'Review uploads'],
            ];
            foreach ($mainNav as [$navUrl, $navIcon, $navLabel, $navHint]):
                $active = str_starts_with($currentPath, $navUrl) && ($navUrl !== url('/admin') || $currentPath === $navUrl);
                ?>
                <a href="<?= $navUrl ?>"
                    class="nav-glow group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?= $active ? 'active dark:text-white text-accent-600 dark:bg-accent-500/10 bg-accent-500/5' : 'dark:text-white/50 text-gray-500 hover:dark:text-white/80 hover:text-gray-700' ?>">
                    <div
                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200 <?= $active ? 'dark:bg-accent-500/20 bg-accent-500/10 shadow-glow' : 'dark:bg-white/5 bg-gray-100 group-hover:dark:bg-white/10 group-hover:bg-gray-200' ?>">
                        <i data-lucide="<?= $navIcon ?>" class="w-4 h-4 <?= $active ? 'text-accent-400' : '' ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="block leading-tight"><?= $navLabel ?></span>
                        <span
                            class="block text-[10px] <?= $active ? 'dark:text-accent-400/60 text-accent-500/60' : 'dark:text-white/25 text-gray-400' ?> leading-tight mt-0.5"><?= $navHint ?></span>
                    </div>
                    <?php if ($active): ?>
                        <div class="w-1.5 h-1.5 rounded-full bg-accent-400 animate-pulse"></div><?php endif; ?>
                </a>
            <?php endforeach; ?>

            <p
                class="text-[10px] font-semibold dark:text-white/30 text-gray-400 uppercase tracking-widest px-3 mb-2 mt-5">
                Tools</p>
            <?php
            $toolsNav = [
                [url('/admin/roadmap'), 'map', 'Roadmap', 'Feature tracker'],
                [url('/admin/affiliates'), 'link-2', 'Affiliates', 'Link management'],
                [url('/admin/costs'), 'credit-card', 'AI Costs', 'Spending tracker'],
            ];
            foreach ($toolsNav as [$navUrl, $navIcon, $navLabel, $navHint]):
                $active = str_starts_with($currentPath, $navUrl);
                ?>
                <a href="<?= $navUrl ?>"
                    class="nav-glow group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?= $active ? 'active dark:text-white text-accent-600 dark:bg-accent-500/10 bg-accent-500/5' : 'dark:text-white/50 text-gray-500 hover:dark:text-white/80 hover:text-gray-700' ?>">
                    <div
                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200 <?= $active ? 'dark:bg-accent-500/20 bg-accent-500/10 shadow-glow' : 'dark:bg-white/5 bg-gray-100 group-hover:dark:bg-white/10 group-hover:bg-gray-200' ?>">
                        <i data-lucide="<?= $navIcon ?>" class="w-4 h-4 <?= $active ? 'text-accent-400' : '' ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="block leading-tight"><?= $navLabel ?></span>
                        <span
                            class="block text-[10px] <?= $active ? 'dark:text-accent-400/60 text-accent-500/60' : 'dark:text-white/25 text-gray-400' ?> leading-tight mt-0.5"><?= $navHint ?></span>
                    </div>
                </a>
            <?php endforeach; ?>

            <p
                class="text-[10px] font-semibold dark:text-white/30 text-gray-400 uppercase tracking-widest px-3 mb-2 mt-5">
                System</p>
            <?php
            $sysNav = [
                [url('/admin/supervisor'), 'activity', 'Supervisor', 'AI monitoring'],
                [url('/admin/settings'), 'sliders-horizontal', 'Settings', 'Configuration'],
            ];
            foreach ($sysNav as [$navUrl, $navIcon, $navLabel, $navHint]):
                $active = str_starts_with($currentPath, $navUrl);
                ?>
                <a href="<?= $navUrl ?>"
                    class="nav-glow group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?= $active ? 'active dark:text-white text-accent-600 dark:bg-accent-500/10 bg-accent-500/5' : 'dark:text-white/50 text-gray-500 hover:dark:text-white/80 hover:text-gray-700' ?>">
                    <div
                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200 <?= $active ? 'dark:bg-accent-500/20 bg-accent-500/10 shadow-glow' : 'dark:bg-white/5 bg-gray-100 group-hover:dark:bg-white/10 group-hover:bg-gray-200' ?>">
                        <i data-lucide="<?= $navIcon ?>" class="w-4 h-4 <?= $active ? 'text-accent-400' : '' ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="block leading-tight"><?= $navLabel ?></span>
                        <span
                            class="block text-[10px] <?= $active ? 'dark:text-accent-400/60 text-accent-500/60' : 'dark:text-white/25 text-gray-400' ?> leading-tight mt-0.5"><?= $navHint ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Bottom: User + Logout -->
        <div class="px-3 py-3 border-t dark:border-white/5 border-gray-200 space-y-1">
            <a href="<?= url('/') ?>" target="_blank"
                class="nav-glow flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium dark:text-white/50 text-gray-500 hover:dark:text-white/80 hover:text-gray-700 transition-all duration-200">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                <span>View Site</span>
            </a>
            <a href="<?= url('/admin/logout') ?>"
                class="nav-glow flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-all duration-200">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- ── Main Area ── -->
    <div class="flex-1 ml-64 min-h-screen flex flex-col transition-all duration-300">
        <!-- Top Bar -->
        <header
            class="glass-card dark:bg-surface-950/80 bg-white/80 border-b dark:border-white/5 border-gray-200 px-6 py-3 flex items-center justify-between sticky top-0 z-30 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="p-2 rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 transition-colors lg:hidden">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div>
                    <h1 class="text-base font-bold dark:text-white text-gray-900">
                        <?= htmlspecialchars($meta['title'] ?? 'Dashboard') ?></h1>
                    <p class="text-[11px] dark:text-white/40 text-gray-400 -mt-0.5">DevLync Admin Panel</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php if (!empty($flashMessage)): ?>
                    <span id="flash-message"
                        class="bg-emerald-500/15 text-emerald-400 border border-emerald-500/20 px-3 py-1 rounded-full text-xs font-medium animate-in">
                        <?= htmlspecialchars($flashMessage) ?>
                    </span>
                <?php endif; ?>
                <!-- Dark/Light Toggle -->
                <button @click="darkMode = !darkMode; localStorage.setItem('admin-theme', darkMode ? 'dark' : 'light')"
                    class="relative w-14 h-7 rounded-full transition-all duration-300 flex items-center px-1"
                    :class="darkMode ? 'bg-accent-500/20 border border-accent-500/30' : 'bg-gray-200 border border-gray-300'">
                    <div class="w-5 h-5 rounded-full shadow-md transition-all duration-300 flex items-center justify-center"
                        :class="darkMode ? 'translate-x-7 bg-accent-500' : 'translate-x-0 bg-white'">
                        <i :data-lucide="darkMode ? 'moon' : 'sun'" class="w-3 h-3"
                            :class="darkMode ? 'text-white' : 'text-yellow-500'"></i>
                    </div>
                </button>
                <div
                    class="w-8 h-8 bg-gradient-to-br from-accent-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-glow">
                    A
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 p-6">
            <?= $content ?>
        </main>

        <!-- Admin Footer -->
        <footer class="px-6 py-3 border-t dark:border-white/5 border-gray-200 text-center">
            <p class="text-[11px] dark:text-white/25 text-gray-400">DevLync Admin &copy; <?= date('Y') ?> &middot; Built
                with care</p>
        </footer>
    </div>

    <script src="<?= url('/assets/js/app.js') ?>"></script>
    <script>
        lucide.createIcons();
        // Re-init icons after Alpine updates (dark/light toggle icon)
        document.addEventListener('alpine:initialized', () => {
            setTimeout(() => lucide.createIcons(), 100);
        });
    </script>
</body>

</html>