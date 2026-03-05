/**
 * DevLync.com — Main JavaScript
 * Alpine.js handles interactivity; this file handles everything else.
 */

// ── TOC: Scroll-Spy Active Link ──────────────────────────────────────────────
(function () {
    const tocLinks = document.querySelectorAll('.toc-link');
    if (!tocLinks.length) return;

    const headings = document.querySelectorAll('.article-content h2, .article-content h3');
    if (!headings.length) return;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    tocLinks.forEach((link) => {
                        link.classList.toggle('active', link.getAttribute('href') === '#' + id);
                    });
                }
            });
        },
        { rootMargin: '-80px 0px -70% 0px', threshold: 0 }
    );

    headings.forEach((h) => {
        if (h.id) observer.observe(h);
    });
})();

// ── Smooth Scroll for TOC Links ──────────────────────────────────────────────
document.addEventListener('click', function (e) {
    const link = e.target.closest('a[href^="#"]');
    if (!link) return;
    const target = document.getElementById(link.getAttribute('href').slice(1));
    if (!target) return;
    e.preventDefault();
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
});

// ── Affiliate Link Click Tracker ─────────────────────────────────────────────
document.addEventListener('click', function (e) {
    const link = e.target.closest('.affiliate-link');
    if (!link) return;
    const brand = link.dataset.brand;
    if (!brand) return;
    // Fire-and-forget tracking
    fetch('/api/track-click?brand=' + encodeURIComponent(brand)).catch(() => { });
});

// ── Reading Progress Bar ─────────────────────────────────────────────────────
(function () {
    const bar = document.getElementById('reading-progress');
    if (!bar) return;

    function updateProgress() {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
        bar.style.width = Math.min(progress, 100) + '%';
    }

    window.addEventListener('scroll', updateProgress, { passive: true });
})();

// ── Lazy-Load Images ─────────────────────────────────────────────────────────
(function () {
    if (!('IntersectionObserver' in window)) return;
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    lazyImages.forEach((img) => {
        img.addEventListener('load', () => img.classList.add('loaded'));
    });
})();

// ── Admin: Confirm Delete ────────────────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    const msg = btn.dataset.confirm || 'Are you sure?';
    if (!window.confirm(msg)) {
        e.preventDefault();
    }
});

// ── Flash Message Auto-Dismiss ────────────────────────────────────────────────
(function () {
    const flash = document.getElementById('flash-message');
    if (!flash) return;
    setTimeout(() => {
        flash.style.transition = 'opacity 0.5s';
        flash.style.opacity = '0';
        setTimeout(() => flash.remove(), 500);
    }, 4000);
})();

// ── Initialize Lucide Icons (called in layout) ─────────────────────────────
// lucide.createIcons() is called in the layout <script> tag
