<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — DevLync</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>

<body class="bg-gray-950 min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-1/4 right-1/4 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl"></div>
    <div class="relative text-center max-w-md">
        <div class="text-9xl font-black bg-gradient-to-r from-blue-500 to-purple-500 bg-clip-text text-transparent mb-4">404</div>
        <h1 class="text-2xl font-bold text-white mb-3">Page Not Found</h1>
        <p class="text-gray-500 mb-8">The page you're looking for doesn't exist or may have moved.</p>
        <div class="flex gap-3 justify-center">
            <a href="<?= url('/') ?>"
                class="bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold px-6 py-3 rounded-2xl hover:shadow-[0_0_30px_rgba(59,130,246,0.3)] transition-all">Go Home</a>
            <a href="<?= url('/blog') ?>"
                class="border border-white/10 text-gray-300 font-semibold px-6 py-3 rounded-2xl hover:bg-white/5 transition-all">Browse Blog</a>
        </div>
    </div>
</body>

</html>