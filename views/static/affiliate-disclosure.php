<?php /* views/static/affiliate-disclosure.php */ ?>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-extrabold dark:text-white text-gray-900 mb-3">Affiliate Disclosure</h1>
    <p class="dark:text-gray-400 text-gray-500 mb-8">Last updated:
        <?= date('F Y') ?>
    </p>
    <div class="dark:bg-yellow-500/10 bg-yellow-50 dark:border-yellow-500/20 border border-yellow-200 rounded-xl p-5 mb-8">
        <p class="font-medium dark:text-yellow-300 text-yellow-900">In plain English: We may earn a commission if you buy through our links,
            but our reviews and ratings are never influenced by this.</p>
    </div>
    <div class="space-y-6 dark:text-gray-300 text-gray-700 leading-relaxed">
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">What Are Affiliate Links?</h2>
            <p>Affiliate links are tracking links provided by software companies and marketplaces. When you click one of
                our affiliate links and make a purchase, we may earn a small commission at no extra cost to you. This is
                how DevLync generates revenue to keep the site running.</p>
        </section>
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">How We Mark Affiliate Links</h2>
            <p>Affiliate links are marked with <code>rel="nofollow noopener sponsored"</code> attributes in the HTML
                and, where required by law, disclosed within the article with a yellow disclosure box. All button-style
                calls-to-action are affiliate links unless stated otherwise.</p>
        </section>
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">Does This Influence Our Reviews?</h2>
            <p>No. Our ratings, verdicts, and recommendations are determined entirely by our testing process and
                editorial judgment — not by affiliate commission rates. Tools are rated identically whether we have an
                affiliate relationship or not.</p>
        </section>
        <section>
            <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-3">FTC Compliance</h2>
            <p>This disclosure complies with the FTC's <em>Guides Concerning the Use of Endorsements and Testimonials in
                    Advertising</em> (16 CFR § 255). If you have questions about our affiliate relationships, <a
                    href="<?= url('/contact') ?>" class="text-blue-600 hover:underline">contact us</a>.</p>
        </section>
    </div>
</div>