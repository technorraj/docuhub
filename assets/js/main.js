/* ============================================================
   DocumentaryHub - Main JavaScript
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    // ---- Lazy Loading Images ----
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[loading="lazy"]');
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    imageObserver.unobserve(img);
                }
            });
        }, { rootMargin: '200px' });
        lazyImages.forEach(img => imageObserver.observe(img));
    }

    // ---- Favorite / Watchlist Toggle ----
    document.querySelectorAll('.fav-toggle').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const id = this.dataset.id;
            const icon = this.querySelector('i');
            const btn = this;

            fetch('ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=toggle_favorite&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (data.added) {
                        icon.className = 'bi bi-bookmark-fill';
                        btn.classList.add('active');
                        btn.title = 'Remove from Watchlist';
                        showToast('Added to Watchlist', 'success');
                    } else {
                        icon.className = 'bi bi-bookmark';
                        btn.classList.remove('active');
                        btn.title = 'Add to Watchlist';
                        showToast('Removed from Watchlist', 'info');
                    }
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(() => showToast('Something went wrong', 'error'));
        });
    });

    // ---- Watch Later button (detail page) ----
    const watchLaterBtn = document.getElementById('watchLaterBtn');
    if (watchLaterBtn) {
        watchLaterBtn.addEventListener('click', function () {
            const id = this.dataset.id;
            fetch('ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=toggle_favorite&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const icon = this.querySelector('i');
                    if (data.added) {
                        icon.className = 'bi bi-bookmark-check-fill';
                        this.innerHTML = '<i class="bi bi-bookmark-check-fill me-2"></i>In Watchlist';
                        this.classList.add('active');
                    } else {
                        icon.className = 'bi bi-bookmark-plus';
                        this.innerHTML = '<i class="bi bi-bookmark-plus me-2"></i>Watch Later';
                        this.classList.remove('active');
                    }
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            });
        });
    }

    // ---- Toast Notifications ----
    window.showToast = function (message, type = 'info') {
        const colors = {
            success: '#2ecc71',
            error: '#e74c3c',
            info: '#00A8E1',
            warning: '#f5a623'
        };
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed; bottom: 24px; right: 24px; z-index: 9999;
            background: #1A242F; color: white; padding: 12px 20px;
            border-radius: 8px; font-size: 0.875rem; font-weight: 500;
            border-left: 3px solid ${colors[type] || colors.info};
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            transform: translateY(20px); opacity: 0;
            transition: all 0.3s ease;
            max-width: 300px;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        requestAnimationFrame(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        });
        setTimeout(() => {
            toast.style.transform = 'translateY(20px)';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // ---- Auto-dismiss alerts ----
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // ---- Navbar scroll effect ----
    const navbar = document.querySelector('.dh-navbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(15,23,30,0.98)';
            } else {
                navbar.style.background = '';
            }
        });
    }

    // ---- Hero carousel auto-rotate ----
    const heroCarousel = document.getElementById('heroCarousel');
    if (heroCarousel) {
        const carousel = new bootstrap.Carousel(heroCarousel, {
            interval: 6000,
            ride: 'carousel',
            pause: 'hover'
        });
    }

    // ---- Category filter (search/categories page) ----
    const catPills = document.querySelectorAll('.dh-cat-pill[data-cat]');
    catPills.forEach(pill => {
        pill.addEventListener('click', function () {
            const cat = this.dataset.cat;
            const url = new URL(window.location.href);
            if (cat === 'all') {
                url.searchParams.delete('cat');
            } else {
                url.searchParams.set('cat', cat);
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    });

    // ---- Track watch progress (on watch page) ----
    const playerIframe = document.getElementById('docPlayer');
    if (playerIframe && playerIframe.dataset.docId) {
        // Record view after 5 seconds
        setTimeout(() => {
            fetch('ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=record_view&id=${playerIframe.dataset.docId}`
            });
        }, 5000);
    }

    // ---- Admin: confirm delete ----
    document.querySelectorAll('.btn-delete-confirm').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this? This cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ---- Admin: thumbnail preview ----
    const videoIdInput = document.getElementById('youtube_video_id');
    const thumbPreview = document.getElementById('thumbPreview');
    if (videoIdInput && thumbPreview) {
        videoIdInput.addEventListener('input', function () {
            const vid = this.value.trim();
            if (vid.length > 5) {
                thumbPreview.src = `https://img.youtube.com/vi/${vid}/mqdefault.jpg`;
                thumbPreview.style.display = 'block';
            }
        });
        // Trigger on load if value already set
        if (videoIdInput.value) videoIdInput.dispatchEvent(new Event('input'));
    }
});
