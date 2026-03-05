<?php
/**
 * Content Post-Processor
 * Fixes article content rendered by the old markdown parser:
 * - Converts raw pipe tables to styled HTML tables
 * - Strips duplicate FAQ sections from content (rendered separately via accordion)
 * - Removes old <div class="article-content"> wrapper
 *
 * Usage: Include this file, then call processArticleContent($html)
 */

function processArticleContent(string $html): string
{
    // Strip old wrapper div
    $html = preg_replace('/^<div class="article-content">\s*/i', '', $html) ?? $html;
    $html = preg_replace('/\s*<\/div>\s*$/i', '', $html) ?? $html;

    // Strip inline FAQ section (Q: / A: format) — it's rendered separately via structured faq accordion
    // Match from FAQ heading to end of content or next major heading
    $html = preg_replace('/<h[23][^>]*>\s*(?:FAQ|Frequently Asked Questions)\s*<\/h[23]>.*$/is', '', $html) ?? $html;

    // Convert raw pipe tables embedded in HTML (from old parser with <br /> line breaks)
    $html = convertPipeTablesToHtml($html);

    return trim($html);
}

function convertPipeTablesToHtml(string $html): string
{
    // Split content by <br> variants to find pipe table blocks
    // Pattern: lines starting with | and separated by <br /> or <br>
    $pattern = '/((?:\|[^\n<]+\|(?:\s*<br\s*\/?>)?(?:\s*\n)?){2,})/i';

    return preg_replace_callback($pattern, function (array $matches) {
        $block = $matches[1];
        // Split into rows by <br /> or <br> or newline
        $rowTexts = preg_split('/<br\s*\/?>|\n/', $block);
        $rows = [];

        foreach ($rowTexts as $rowText) {
            $rowText = trim($rowText);
            if ($rowText === '' || !str_contains($rowText, '|')) {
                continue;
            }
            // Skip separator rows (|---|---|)
            if (preg_match('/^\|[\s\-:|]+\|$/', $rowText)) {
                continue;
            }
            // Extract cells
            $rowText = trim($rowText, '| ');
            $cells = array_map('trim', explode('|', $rowText));
            if (!empty($cells)) {
                $rows[] = $cells;
            }
        }

        if (count($rows) < 2) {
            return $matches[0]; // Not a real table, return as-is
        }

        // Build HTML table
        $tableHtml = '<div class="overflow-x-auto my-6"><table class="min-w-full border border-gray-200 rounded-lg text-sm">' . "\n";

        // Header row
        $tableHtml .= '<thead class="bg-gray-100"><tr>';
        foreach ($rows[0] as $cell) {
            $tableHtml .= '<th class="px-4 py-3 text-left font-semibold text-gray-700 border-b border-gray-200">' . $cell . '</th>';
        }
        $tableHtml .= "</tr></thead>\n<tbody>\n";

        // Data rows
        for ($i = 1; $i < count($rows); $i++) {
            $rowClass = $i % 2 === 0 ? ' class="bg-gray-50"' : '';
            $tableHtml .= "<tr{$rowClass}>";
            foreach ($rows[$i] as $cell) {
                $tableHtml .= '<td class="px-4 py-3 border-b border-gray-100">' . $cell . '</td>';
            }
            $tableHtml .= "</tr>\n";
        }

        $tableHtml .= "</tbody></table></div>\n";
        return $tableHtml;
    }, $html) ?? $html;
}
