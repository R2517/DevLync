<?php
declare(strict_types=1);

/**
 * StaticController
 * Renders static informational pages: About, Contact, Privacy, Editorial Policy, etc.
 */
class StaticController
{
    /**
     * Renders the About page.
     *
     * @return void
     */
    public function about(): void
    {
        global $tpl;
        $meta = [
            'title' => 'About DevLync — Our Mission & Editorial Standards',
            'description' => 'DevLync is an independent developer tools review platform built for engineers. Learn about our mission, editorial standards, and the team behind every review.',
            'canonical' => 'https://devlync.com/about',
        ];
        $schemaMarkup = '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'AboutPage',
            'name' => 'About DevLync',
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'DevLync', 'url' => 'https://devlync.com'],
        ], JSON_UNESCAPED_SLASHES) . '</script>';
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('static/about');
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * Renders the Editorial Policy page.
     *
     * @return void
     */
    public function editorialPolicy(): void
    {
        global $tpl;
        $meta = [
            'title' => 'Editorial Policy — How We Review Tools | DevLync',
            'description' => 'Our editorial policy explains how every DevLync review is written, tested, and kept up to date. Transparency is core to everything we do.',
            'canonical' => 'https://devlync.com/editorial-policy',
        ];
        $schemaMarkup = '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Editorial Policy',
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'DevLync', 'url' => 'https://devlync.com'],
        ], JSON_UNESCAPED_SLASHES) . '</script>';
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('static/editorial-policy');
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * Renders the Fact-Checking Policy page.
     *
     * @return void
     */
    public function factCheckingPolicy(): void
    {
        global $tpl;
        $meta = [
            'title' => 'Fact-Checking Policy — Accuracy & Verification | DevLync',
            'description' => 'Learn how DevLync fact-checks and verifies every claim in our developer tool reviews, comparisons, and news articles before publication.',
            'canonical' => 'https://devlync.com/fact-checking-policy',
        ];
        $schemaMarkup = '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Fact-Checking Policy',
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'DevLync', 'url' => 'https://devlync.com'],
        ], JSON_UNESCAPED_SLASHES) . '</script>';
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('static/fact-checking-policy');
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * Renders the Affiliate Disclosure page.
     *
     * @return void
     */
    public function affiliateDisclosure(): void
    {
        global $tpl;
        $meta = [
            'title' => 'Affiliate Disclosure — DevLync',
            'description' => 'DevLync may earn commissions from affiliate links. This disclosure explains how affiliate links work and how they affect our reviews.',
            'canonical' => 'https://devlync.com/affiliate-disclosure',
        ];
        $schemaMarkup = '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Affiliate Disclosure',
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'DevLync', 'url' => 'https://devlync.com'],
        ], JSON_UNESCAPED_SLASHES) . '</script>';
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('static/affiliate-disclosure');
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * Renders the Contact page.
     *
     * @return void
     */
    public function contact(): void
    {
        global $tpl;
        $meta = [
            'title' => 'Contact Us — Get in Touch with the DevLync Team',
            'description' => 'Have a question, correction, or partnership inquiry? Reach out to the DevLync team. We respond to all editorial and business inquiries promptly.',
            'canonical' => 'https://devlync.com/contact',
        ];
        $schemaMarkup = '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'name' => 'Contact DevLync',
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'DevLync', 'url' => 'https://devlync.com'],
        ], JSON_UNESCAPED_SLASHES) . '</script>';
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('static/contact');
        include VIEWS_PATH . '/layouts/main.php';
    }

    /**
     * Renders the Privacy Policy page.
     *
     * @return void
     */
    public function privacyPolicy(): void
    {
        global $tpl;
        $meta = [
            'title' => 'Privacy Policy — Your Data & Rights | DevLync',
            'description' => 'The DevLync privacy policy explains what personal data we collect, how we use cookies and analytics, and your rights under GDPR and applicable privacy laws.',
            'canonical' => 'https://devlync.com/privacy-policy',
        ];
        $schemaMarkup = '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => 'Privacy Policy',
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'DevLync', 'url' => 'https://devlync.com'],
        ], JSON_UNESCAPED_SLASHES) . '</script>';
        $footerCategories = Category::getActive();
        $content = $tpl->renderPartial('static/privacy-policy');
        include VIEWS_PATH . '/layouts/main.php';
    }
}
