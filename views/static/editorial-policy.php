<?php /* views/static/editorial-policy.php */ ?>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-extrabold dark:text-white text-gray-900 mb-3">Editorial Policy</h1>
    <p class="dark:text-gray-400 text-gray-500 mb-8">Last updated:
        <?= date('F Y') ?>
    </p>
    <div class="space-y-6 dark:text-gray-300 text-gray-700 leading-relaxed">
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">Independence</h2>
            <p class="dark:text-gray-400 text-gray-500">DevLync is an editorially independent publication. Advertisers and affiliate partners have no influence
                over our coverage, ratings, or recommendations. No brand can purchase a positive review or a higher
                rating.</p>
        </section>
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">How Reviews Are Written</h2>
            <p>Every tool reviewed on DevLync is installed and used in realistic development scenarios. We evaluate
                tools across standardized criteria: features, performance, pricing, support quality, and ease of use.
                Ratings are assigned on a 0–10 scale, calibrated against category benchmarks.</p>
            <p class="mt-3">For comparison articles, we test each tool under equivalent conditions and present findings
                in structured tables so readers can make informed decisions quickly. We do not accept compensation for
                favorable placement within comparison rankings.</p>
        </section>
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">AI-Assisted Content</h2>
            <p>DevLync uses AI assistance to research and draft articles. All AI-generated content is reviewed by our
                editorial team for accuracy before publication. We believe in transparency: where AI assistance is
                significant, we note it within the article.</p>
        </section>
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">Updates & Corrections</h2>
            <p>We update articles when tools release major changes. If you spot an error, <a href="<?= url('/contact') ?>"
                    class="text-blue-600 hover:underline">contact us</a> and we'll investigate and correct it promptly.
            </p>
        </section>
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">Affiliate Links</h2>
            <p>Some articles contain affiliate links. Clicking these and making a purchase may result in DevLync earning
                a commission. This does not affect our ratings or recommendations. See our <a
                    href="<?= url('/affiliate-disclosure') ?>" class="text-blue-600 hover:underline">affiliate disclosure</a> for full
                details.</p>
        </section>
    </div>
</div>