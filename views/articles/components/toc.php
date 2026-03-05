<?php
/**
 * Table of Contents Component
 * Auto-generates TOC from H2/H3 headings in article content.
 * Variables: $content (HTML string)
 */
if (empty($content))
    return;

// Match headings with or without id attributes
preg_match_all('/<h([23])(?:\s[^>]*)?>(.*?)<\/h[23]>/is', $content, $rawMatches, PREG_SET_ORDER);
$tocItems = [];
foreach ($rawMatches as $m) {
    $level = (int) $m[1];
    $label = trim(strip_tags($m[2]));
    if (preg_match('/id="([^"]+)"/', $m[0], $idMatch)) {
        $id = $idMatch[1];
    } else {
        $id = trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($label)), '-');
    }
    if ($label !== '' && $id !== '') {
        $tocItems[] = ['level' => $level, 'id' => $id, 'label' => $label];
    }
}
if (count($tocItems) < 3)
    return;
?>
<div class="my-8 not-prose" x-data="{ open: true }">
    <div class="bg-gradient-to-br from-slate-50 to-blue-50 border border-blue-100 rounded-2xl overflow-hidden shadow-sm">
        <button @click="open = !open" class="flex items-center justify-between w-full text-left px-6 py-4 hover:bg-blue-50/50 transition-colors">
            <span class="font-bold text-gray-900 text-sm uppercase tracking-wider flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h10" />
                    </svg>
                </span>
                Table of Contents
                <span class="text-xs font-normal text-gray-400 ml-1">(<?= count($tocItems) ?> sections)</span>
            </span>
            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        <nav x-show="open" x-transition.duration.200ms class="px-6 pb-5 space-y-0.5">
            <?php $idx = 0; foreach ($tocItems as $item): $idx++; ?>
                <a href="#<?= htmlspecialchars($item['id']) ?>"
                   class="toc-link flex items-center gap-2 py-1.5 rounded-lg px-3 text-sm transition-all hover:bg-white hover:shadow-sm <?= $item['level'] === 3 ? 'ml-6 text-xs text-gray-500' : 'font-medium text-gray-700' ?>">
                    <?php if ($item['level'] === 2): ?>
                        <span class="w-5 h-5 rounded-md bg-blue-100 text-blue-600 text-[10px] font-bold flex items-center justify-center flex-shrink-0"><?= $idx ?></span>
                    <?php else: ?>
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300 flex-shrink-0 ml-1.5"></span>
                    <?php endif; ?>
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
