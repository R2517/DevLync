<?php /* views/static/fact-checking-policy.php */ ?>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-extrabold dark:text-white text-gray-900 mb-3">Fact-Checking Policy</h1>
    <p class="dark:text-gray-400 text-gray-500 mb-8">Last updated:
        <?= date('F Y') ?>
    </p>
    <div class="space-y-6 dark:text-gray-300 text-gray-700 leading-relaxed">
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">Our Commitment to Accuracy</h2>
            <p class="dark:text-gray-400 text-gray-500">DevLync is committed to publishing accurate, verifiable information. Every factual claim in our reviews
                and articles is supported by direct testing, official documentation, or credible third-party sources.
                We believe trustworthy content is the foundation of a useful developer resource, and we hold ourselves
                to the highest standard of editorial integrity.</p>
        </section>
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">Verification Process</h2>
            <ul class="list-disc pl-5 space-y-2">
                <li>Pricing, feature availability, and plan details are verified against the official product website at
                    time of publication.</li>
                <li>Performance benchmarks are derived from our own testing environment or from officially published
                    vendor data.</li>
                <li>Third-party claims are cited with links to primary sources whenever possible.</li>
                <li>AI-generated or AI-assisted content undergoes a manual editorial review for accuracy, tone, and completeness before publication.</li>
                <li>Screenshots and UI references are captured directly from the product being reviewed.</li>
            </ul>
        </section>
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">Corrections & Updates</h2>
            <p>When we identify or are notified of an error, we follow a structured correction process. Minor factual
                corrections (e.g., pricing changes or feature updates) are applied directly to the article with an
                updated date. Significant corrections are noted with an editor's note at the top of the article
                explaining what was changed and why.</p>
        </section>
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">Review Freshness</h2>
            <p>Developer tools evolve rapidly. We periodically revisit published reviews to ensure the information
                remains current. Articles that have not been verified in the last six months are flagged for review
                by our editorial team. If a tool has changed significantly, we update or republish the review.</p>
        </section>
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">Reporting Inaccuracies</h2>
            <p>If you find factual errors in any article, please <a href="<?= url('/contact') ?>"
                    class="text-blue-600 hover:underline">contact us</a>. We investigate all reported errors and, if
                confirmed, correct the article within 48 hours. Your feedback helps us maintain the quality our readers
                expect.</p>
        </section>
    </div>
</div>