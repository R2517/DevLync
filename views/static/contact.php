<?php /* views/static/contact.php */ ?>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-extrabold dark:text-white text-gray-900 mb-3">Contact DevLync</h1>
    <p class="dark:text-gray-400 text-gray-500 mb-8">Questions, corrections, partnerships, or press inquiries — we would love to hear from you.</p>
    <div class="grid sm:grid-cols-2 gap-6 mb-10">
        <div class="dark:bg-blue-500/10 bg-blue-50 dark:border-blue-500/20 border border-blue-100 rounded-xl p-5">
            <h2 class="font-bold dark:text-blue-300 text-blue-900 mb-1">Editorial Inquiries</h2>
            <p class="dark:text-blue-400/80 text-blue-700 text-sm">Corrections, tip submissions, or editorial feedback. If you spot an error in one of our reviews or articles, let us know and we will investigate within 48 hours.</p>
            <a href="mailto:editorial@devlync.com"
                class="text-blue-600 text-sm font-medium mt-2 block hover:text-blue-800">editorial@devlync.com</a>
        </div>
        <div class="dark:bg-white/[0.03] bg-gray-50 dark:border-white/5 border border-gray-100 rounded-xl p-5">
            <h2 class="font-bold dark:text-white text-gray-900 mb-1">Partnerships & Advertising</h2>
            <p class="dark:text-gray-400 text-gray-500 text-sm">Interested in sponsored content, affiliate programs, or submitting your developer tool for review? We are always looking for great tools to cover.</p>
            <a href="mailto:partnerships@devlync.com"
                class="text-gray-600 text-sm font-medium mt-2 block hover:text-gray-900">partnerships@devlync.com</a>
        </div>
    </div>

    <div class="space-y-6 dark:text-gray-300 text-gray-700 leading-relaxed mb-10">
        <h2 class="text-xl font-bold dark:text-white text-gray-900">Response Times</h2>
        <p>We aim to respond to all inquiries within one to two business days. Editorial corrections are prioritized and typically addressed within 48 hours of receipt. For urgent matters, please include "URGENT" in the subject line of your email.</p>

        <h2 class="text-xl font-bold dark:text-white text-gray-900">Frequently Asked Questions</h2>
        <div class="space-y-4">
            <div>
                <p class="font-semibold dark:text-white text-gray-900">Can I submit my tool for review?</p>
                <p class="dark:text-gray-400 text-gray-500 text-sm">Yes. Email partnerships@devlync.com with a link to your product, a brief description, and any trial or demo access you can provide. Submitting a tool does not guarantee coverage or a favorable review.</p>
            </div>
            <div>
                <p class="font-semibold dark:text-white text-gray-900">Do you accept guest posts?</p>
                <p class="dark:text-gray-400 text-gray-500 text-sm">We occasionally publish guest contributions from experienced developers. Pitches should be original, technically accurate, and relevant to our audience. Send your proposal to editorial@devlync.com.</p>
            </div>
            <div>
                <p class="font-semibold dark:text-white text-gray-900">How do I report a factual error?</p>
                <p class="dark:text-gray-400 text-gray-500 text-sm">Please email editorial@devlync.com with the article URL and a description of the error. We take accuracy seriously and will correct confirmed mistakes promptly. Learn more in our <a href="<?= url('/fact-checking-policy') ?>" class="text-blue-600 hover:underline">fact-checking policy</a>.</p>
            </div>
        </div>
    </div>

    <div class="dark:bg-yellow-500/10 bg-yellow-50 dark:border-yellow-500/20 border border-yellow-200 rounded-xl p-5">
        <p class="dark:text-yellow-300 text-yellow-800 text-sm font-medium">Brands cannot pay for higher ratings or favorable coverage.
            See our <a href="<?= url('/editorial-policy') ?>" class="underline">editorial policy</a>.</p>
    </div>
</div>