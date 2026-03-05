<?php
/**
 * Admin Roadmap Create View
 */
?>
<div class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Add Roadmap Keyword</h1>
        <a href="<?= url('/admin/roadmap') ?>" class="px-4 py-2 bg-gray-800 text-gray-300 rounded hover:bg-gray-700">Cancel</a>
    </div>

    <form method="POST" action="<?= url('/admin/roadmap/create') ?>"
        class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Target Title / Topic</label>
            <input type="text" name="title" required placeholder="e.g. 10 Best SEO Tools"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Primary Keyword</label>
            <input type="text" name="primary_keyword" placeholder="e.g. best seo tools"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Content Type</label>
                <select name="content_type"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                    <option value="blog">Blog Post</option>
                    <option value="review">Review</option>
                    <option value="comparison">Comparison</option>
                    <option value="news">News</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Priority</label>
                <select name="priority"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                    <option value="1">1 - Critical</option>
                    <option value="2">2 - High</option>
                    <option value="3" selected>3 - Normal</option>
                    <option value="4">4 - Low</option>
                    <option value="5">5 - Minimal</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-800 flex justify-end">
            <button type="submit"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                Add to Roadmap Queue
            </button>
        </div>
    </form>
</div>