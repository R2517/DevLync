<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — DevLync</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/svg+xml" href="<?= url('/assets/images/favicon.svg') ?>">
    <link rel="icon" type="image/png" href="<?= url('/assets/images/favicon.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-950 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold text-white">DevLync Admin</h1>
            <p class="text-gray-400 text-sm mt-1">Sign in to manage your content</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-900/40 border border-red-700 text-red-300 rounded-xl p-3 mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/admin/login') ?>"
            class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Admin Password</label>
                <input type="password" name="password" id="admin-password"
                    class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    autofocus required>
            </div>
            <button type="submit" id="login-btn"
                class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl transition-colors">
                Sign In
            </button>
        </form>
    </div>
</body>

</html>