<?php
/**
 * FAQ Accordion Component
 * Alpine.js powered FAQ with FAQPage schema markup.
 * Variables: $faq (array of {question, answer} or JSON string)
 */
$faqItems = is_string($faq ?? '') ? json_decode($faq, true) : ($faq ?? []);
if (empty($faqItems))
    return;
?>
<div class="my-10 not-prose">
    <div class="flex items-center gap-3 mb-6">
        <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.065 2.05-1.818 3.772-1.818 1.963 0 3.556 1.024 3.556 2.27 0 1.07-.837 1.973-2.013 2.227-.607.13-1.215.652-1.215 1.273V14m0 3h.01" />
            </svg>
        </span>
        <h2 class="text-xl font-extrabold text-gray-900">Frequently Asked Questions</h2>
    </div>
    <div class="space-y-3">
        <?php foreach ($faqItems as $i => $item): ?>
            <div class="rounded-xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow"
                x-data="{ open: <?= $i === 0 ? 'true' : 'false' ?> }">
                <button @click="open = !open"
                    class="flex items-center justify-between w-full text-left px-5 py-4 transition-colors"
                    :class="open ? 'bg-blue-50' : 'bg-white hover:bg-gray-50'"
                    :aria-expanded="open">
                    <span class="flex items-center gap-3 pr-4">
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 text-xs font-bold transition-colors"
                              :class="open ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500'">
                            Q<?= $i + 1 ?>
                        </span>
                        <span class="text-sm sm:text-base font-semibold" :class="open ? 'text-blue-900' : 'text-gray-900'">
                            <?= htmlspecialchars($item['question'] ?? '') ?>
                        </span>
                    </span>
                    <svg class="w-5 h-5 flex-shrink-0 transition-all duration-200" :class="open ? 'rotate-180 text-blue-600' : 'text-gray-400'"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-transition.duration.200ms class="px-5 pb-5 pt-2 bg-white border-t border-gray-100" style="display:none">
                    <div class="pl-10 text-gray-600 text-sm leading-relaxed">
                        <?= htmlspecialchars($item['answer'] ?? '') ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- FAQPage Schema -->
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($item) => [
        '@type' => 'Question',
        'name' => $item['question'] ?? '',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $item['answer'] ?? '',
        ],
    ], $faqItems),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
