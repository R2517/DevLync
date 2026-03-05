<?php
/**
 * Admin Article Edit View
 * Variables: $article (array)
 */
?>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Edit Article:
            <?= htmlspecialchars($article['title']) ?>
        </h1>
        <a href="<?= url('/admin/articles') ?>" class="px-4 py-2 bg-gray-800 text-gray-300 rounded hover:bg-gray-700">Cancel</a>
    </div>

    <form method="POST" action="<?= url('/admin/articles/' . $article['id'] . '/edit') ?>"
        class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($article['title']) ?>" required
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Slug</label>
            <input type="text" name="slug" value="<?= htmlspecialchars($article['slug']) ?>" required
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Status</label>
                <select name="status"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500">
                    <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="review" <?= $article['status'] === 'review' ? 'selected' : '' ?>>Review</option>
                    <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Published
                    </option>
                    <option value="archived" <?= $article['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Excerpt</label>
            <textarea name="excerpt" rows="3"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500"><?= htmlspecialchars($article['excerpt'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-400 mb-1">Content (HTML)</label>
            <textarea name="content" rows="15"
                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none focus:border-blue-500 font-mono text-sm leading-relaxed"><?= htmlspecialchars($article['content'] ?? '') ?></textarea>
            <p class="text-xs text-gray-500 mt-2">Note: For this basic demo editor, content is managed as raw HTML. In
                production with n8n, this is automated.</p>
        </div>

        <div class="pt-4 border-t border-gray-800 flex justify-end">
            <button type="submit"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                Save Changes
            </button>
        </div>
    </form>
</div>