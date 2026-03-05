<?php
/**
 * Admin Affiliates View
 * Variables: $affiliates (array), $pending (array)
 */
$statusColors = ['active' => 'green', 'pending' => 'yellow', 'paused' => 'orange', 'expired' => 'red', 'inactive' => 'gray'];
$activated = (int) ($_GET['activated'] ?? 0);
$articlesUpdated = (int) ($_GET['articles_updated'] ?? 0);
$updatedId = (int) ($_GET['updated'] ?? 0);
$deleted = (int) ($_GET['deleted'] ?? 0);
?>
<div class="space-y-6">

    <?php if ($activated): ?>
        <div class="bg-green-900/30 border border-green-700/40 rounded-lg px-4 py-3 text-sm text-green-300">
            Affiliate link activated! <?= $articlesUpdated ?> article(s) updated with the real URL.
        </div>
    <?php endif; ?>
    <?php if ($updatedId): ?>
        <div class="bg-blue-900/30 border border-blue-700/40 rounded-lg px-4 py-3 text-sm text-blue-300">
            Affiliate link updated successfully. Articles will be updated on the next affiliate module run.
        </div>
    <?php endif; ?>
    <?php if ($deleted): ?>
        <div class="bg-red-900/30 border border-red-700/40 rounded-lg px-4 py-3 text-sm text-red-300">
            Affiliate link deleted.
        </div>
    <?php endif; ?>

    <!-- Pending Approval -->
    <?php if (!empty($pending)): ?>
        <div class="bg-yellow-900/20 border border-yellow-700/40 rounded-xl p-5">
            <h2 class="font-bold text-yellow-400 text-sm mb-3">Pending Affiliate URLs (<?= count($pending) ?>)</h2>
            <p class="text-xs text-gray-400 mb-3">These brands were auto-discovered from articles. Add the real affiliate URL to activate them.</p>
            <div class="space-y-2">
                <?php foreach ($pending as $link): ?>
                    <div class="flex items-center justify-between bg-gray-900 border border-gray-800 rounded-lg p-3 gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white"><?= htmlspecialchars($link['brand_name']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($link['notes'] ?? '') ?></p>
                        </div>
                        <div class="flex gap-2 items-center">
                            <form method="POST" action="<?= url('/admin/affiliates/activate') ?>" class="flex gap-2 items-center">
                                <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                                <input type="text" name="affiliate_url" placeholder="https://affiliate-link.com/..." required
                                    value="<?= htmlspecialchars($link['affiliate_url'] ?? '') ?>"
                                    class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none focus:ring-1 focus:ring-green-500 w-64">
                                <button type="submit"
                                    class="text-xs bg-green-700 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg transition-colors font-medium whitespace-nowrap">Activate</button>
                            </form>
                            <form method="POST" action="<?= url('/admin/affiliates/delete') ?>"
                                onsubmit="return confirm('Delete this pending link?')">
                                <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                                <button type="submit"
                                    class="text-xs text-red-400 hover:text-red-300 px-2 py-1.5">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- All Affiliate Links -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
            <h2 class="font-semibold text-white text-sm">All Affiliate Links (<?= count($affiliates) ?>)</h2>
            <a href="<?= url('/admin/affiliates/create') ?>"
                class="text-xs px-3 py-1.5 rounded-lg font-bold bg-blue-600 text-white hover:bg-blue-500 transition-colors shadow-sm">
                + Add Affiliate
            </a>
        </div>
        <?php if (empty($affiliates)): ?>
            <div class="text-center py-12 text-gray-500 text-sm">No affiliate links yet. They will be auto-discovered when articles are generated.</div>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead class="border-b border-gray-800">
                    <tr class="text-xs text-gray-400 uppercase tracking-wider text-left">
                        <th class="px-4 py-3">Brand</th>
                        <th class="px-4 py-3">Affiliate URL</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Clicks</th>
                        <th class="px-4 py-3">Articles</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <?php foreach ($affiliates as $link): ?>
                        <?php $sc = $statusColors[$link['status']] ?? 'gray'; ?>
                        <tr class="hover:bg-gray-800/40 group" x-data="{ editing: false }">
                            <td class="px-4 py-3">
                                <p class="text-white font-medium"><?= htmlspecialchars($link['brand_name']) ?></p>
                                <p class="text-xs text-gray-500">/<?= htmlspecialchars($link['brand_slug']) ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <template x-if="!editing">
                                    <div>
                                        <?php if ($link['affiliate_url']): ?>
                                            <a href="<?= htmlspecialchars($link['affiliate_url']) ?>" target="_blank"
                                                class="text-blue-400 hover:text-blue-300 text-xs truncate block max-w-xs">
                                                <?= htmlspecialchars($link['affiliate_url']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-600 text-xs italic">No URL set</span>
                                        <?php endif; ?>
                                    </div>
                                </template>
                                <template x-if="editing">
                                    <form method="POST" action="<?= url('/admin/affiliates/update') ?>" class="flex gap-1.5">
                                        <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                                        <input type="text" name="affiliate_url" value="<?= htmlspecialchars($link['affiliate_url'] ?? '') ?>"
                                            placeholder="https://..."
                                            class="bg-gray-800 border border-gray-600 rounded px-2 py-1 text-xs text-white w-56 focus:ring-1 focus:ring-indigo-500">
                                        <select name="status"
                                            class="bg-gray-800 border border-gray-600 rounded px-2 py-1 text-xs text-white">
                                            <?php foreach (['active', 'pending', 'paused', 'expired', 'inactive'] as $s): ?>
                                                <option value="<?= $s ?>" <?= $link['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit"
                                            class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white px-2.5 py-1 rounded font-medium">Save</button>
                                        <button type="button" @click="editing = false"
                                            class="text-xs text-gray-400 hover:text-gray-200 px-1.5">Cancel</button>
                                    </form>
                                </template>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs bg-<?= $sc ?>-900/50 text-<?= $sc ?>-400 border border-<?= $sc ?>-700/30 px-2 py-0.5 rounded-full capitalize">
                                    <?= $link['status'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-300 text-xs">
                                <?= number_format((int) ($link['click_count'] ?? 0)) ?>
                            </td>
                            <td class="px-4 py-3 text-gray-300 text-xs">
                                <?= (int) ($link['articles_count'] ?? 0) ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 items-center">
                                    <button @click="editing = !editing"
                                        class="text-xs text-indigo-400 hover:text-indigo-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                        Edit
                                    </button>
                                    <form method="POST" action="<?= url('/admin/affiliates/delete') ?>" class="inline"
                                        onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($link['brand_name'])) ?>?')">
                                        <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                                        <button type="submit"
                                            class="text-xs text-red-400 hover:text-red-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>