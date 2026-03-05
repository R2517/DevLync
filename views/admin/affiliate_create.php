<?php
/**
 * Admin Affiliate Create View
 */
?>
<div class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Add Affiliate Link</h1>
        <a href="<?= url('/admin/affiliates') ?>" class="px-4 py-2 bg-gray-800 text-gray-300 rounded hover:bg-gray-700">Cancel</a>
    </div>

    <form method="POST" action="<?= url('/admin/affiliates/create') ?>"
        class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Brand Name</label>
            <input type="text" name="brand_name" required placeholder="e.g. GitHub Copilot"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Affiliate URL</label>
            <input type="url" name="affiliate_url" required placeholder="https://..."
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
        </div>

        <div class="pt-4 border-t border-gray-800 flex justify-end">
            <button type="submit"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                Save Link
            </button>
        </div>
    </form>
</div>