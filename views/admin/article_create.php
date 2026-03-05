<?php
/**
 * Admin Article Create View
 */
?>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Create New Article</h1>
        <a href="<?= url('/admin/articles') ?>" class="px-4 py-2 bg-gray-800 text-gray-300 rounded hover:bg-gray-700">Cancel</a>
    </div>

    <form method="POST" action="<?= url('/admin/articles/create') ?>"
        class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Title</label>
            <input type="text" name="title" required placeholder="e.g. 5 Best VS Code Extensions"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Type</label>
            <select name="type"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                <option value="blog">Blog Post</option>
                <option value="review">Review</option>
                <option value="comparison">Comparison</option>
                <option value="news">News</option>
            </select>
        </div>

        <div class="pt-4 border-t border-gray-800 flex justify-end">
            <button type="submit"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                Create Draft & Continue Editing
            </button>
        </div>
    </form>
</div>