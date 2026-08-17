/**
 * Rainier Theme - JavaScript Interactions
 * 
 * Handles:
 * - Welcome overlay & invitation opening
 * - Music control
 * - Countdown timer
 * - Dynamic Gallery loading & Lightbox modal
 * - Navigation (mobile toggle, scroll behavior)
 * - RSVP form submission
 * - Copy to clipboard
 * - Toast notifications
 * - Smooth scroll effects
 */

(function() {
    'use strict';

    // DOM Elements
    const welcomeOverlay = document.getElementById('welcome-overlay');
    const openInvitationBtn = document.getElementById('open-invitation');
    const mainContent = document.getElementById('main-content');
    const loadingScreen = document.getElementById('loading-screen');
    const navbar = document.getElementById('navbar');
    const navbarToggle = document.querySelector('.navbar-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    const musicToggle = document.getElementById('music-toggle');
    const backgroundMusic = document.getElementById('background-music');
    const galleryGrid = document.getElementById('gallery-grid') || document.querySelector('.gallery-grid');
    const galleryModal = document.getElementById('gallery-modal');
    const modalImage = document.querySelector('.modal-image') || document.getElementById('modalImg');
    const modalCaption = document.querySelector('.modal-caption') || document.getElementById('modalCaption');
    const modalClose = document.querySelector('.modal-close');
    const modalPrev = document.querySelector('.modal-prev');
    const modalNext = document.querySelector('.modal-next');
    const rsvpForm = document.getElementById('rsvp-form') || document.getElementById('rsvpForm');
    const toast = document.getElementById('toast');
    const toastMessage = document.querySelector('.toast-message');

    // State
    let currentGalleryIndex = 0;
    let galleryItemsData = [];
    let isMusicPlaying = false;

    function hideLoadingScreen() {
        try {
            const screen = loadingScreen || document.getElementById('loading-screen');
            if (screen) {
                screen.classList.add('hidden');
                setTimeout(function() {
                    try {
                        screen.style.display = 'none';
                    } catch (e) {}
                }, 500);
            }
        } catch (e) {
            console.warn('Error hiding loading screen:', e);
        }
    }

    // Fallback timer: force-hide preloader after 1.5 seconds max
    setTimeout(hideLoadingScreen, 1500);

    /**
     * Initialize everything when DOM is ready
     */
    function init() {
        try { initWelcomeOverlay(); } catch (e) { console.warn('Overlay init error:', e); }
        try { initMusicControl(); } catch (e) { console.warn('Music init error:', e); }
        try { initNavbar(); } catch (e) { console.warn('Navbar init error:', e); }
        try { initCountdown(); } catch (e) { console.warn('Countdown init error:', e); }
        try { initDynamicGallery(); } catch (e) { console.warn('Gallery init error:', e); }
        try { initRSVP(); } catch (e) { console.warn('RSVP init error:', e); }
        try { initCopyButtons(); } catch (e) { console.warn('Copy buttons init error:', e); }
        try { initScrollEffects(); } catch (e) { console.warn('Scroll effects init error:', e); }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        hideLoadingScreen();
        init();
    } else {
        window.addEventListener('load', function() {
            hideLoadingScreen();
            init();
        });
        document.addEventListener('DOMContentLoaded', function() {
            hideLoadingScreen();
            init();
        });
    }

    /**
     * Welcome Overlay & Invitation Opening
     */
    function initWelcomeOverlay() {
        if (!openInvitationBtn || !welcomeOverlay || !mainContent) return;

        openInvitationBtn.addEventListener('click', function() {
            welcomeOverlay.classList.add('hidden');
            mainContent.classList.remove('hidden');
            document.body.classList.remove('hidden');
            
            if (backgroundMusic) {
                playMusic();
            }
            
            history.pushState(null, null, '#home');
        });
    }

    /**
     * Music Control
     */
    function initMusicControl() {
        if (!musicToggle || !backgroundMusic) return;

        musicToggle.addEventListener('click', function() {
            if (isMusicPlaying) {
                pauseMusic();
            } else {
                playMusic();
            }
        });
    }

    function playMusic() {
        if (!backgroundMusic) return;
        
        backgroundMusic.play().then(() => {
            isMusicPlaying = true;
            updateMusicIcon();
        }).catch(err => {
            console.log('Autoplay prevented:', err);
        });
    }

    function pauseMusic() {
        if (!backgroundMusic) return;
        
        backgroundMusic.pause();
        isMusicPlaying = false;
        updateMusicIcon();
    }

    function updateMusicIcon() {
        if (!musicToggle) return;
        
        const iconSpan = musicToggle.querySelector('.music-icon');
        if (isMusicPlaying) {
            if (iconSpan) iconSpan.textContent = '⏸️';
            musicToggle.classList.add('playing');
        } else {
            if (iconSpan) iconSpan.textContent = '🎵';
            musicToggle.classList.remove('playing');
        }
    }

    /**
     * Navbar Behavior
     */
    function initNavbar() {
        if (!navbar || !navbarToggle || !navbarMenu) return;

        navbarToggle.addEventListener('click', function() {
            navbarMenu.classList.toggle('active');
            navbarToggle.classList.toggle('active');
        });

        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navbarMenu.classList.remove('active');
                navbarToggle.classList.remove('active');
            });
        });

        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    /**
     * Countdown Timer
     */
    function initCountdown() {
        const cdDays = document.getElementById('cd-days') || document.getElementById('hero-countdown-day');
        const cdHours = document.getElementById('cd-hours');
        const cdMinutes = document.getElementById('cd-minutes');
        const cdSeconds = document.getElementById('cd-seconds');

        if (!cdDays) return;

        const countdownTarget = document.body.dataset.countdownTarget || getConfigValue('countdown_target');
        
        if (!countdownTarget) {
            return;
        }

        function updateCountdown() {
            const now = new Date().getTime();
            const target = new Date(countdownTarget).getTime();
            const distance = target - now;

            if (distance < 0) {
                if (cdDays) cdDays.textContent = '00';
                if (cdHours) cdHours.textContent = '00';
                if (cdMinutes) cdMinutes.textContent = '00';
                if (cdSeconds) cdSeconds.textContent = '00';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            if (cdDays) cdDays.textContent = String(days).padStart(2, '0');
            if (cdHours) cdHours.textContent = String(hours).padStart(2, '0');
            if (cdMinutes) cdMinutes.textContent = String(minutes).padStart(2, '0');
            if (cdSeconds) cdSeconds.textContent = String(seconds).padStart(2, '0');
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    /**
     * Dynamic Gallery Loading & Modal Lightbox
     */
    function initDynamicGallery() {
        if (!galleryGrid) return;

        fetch('gallery.php')
            .then(res => res.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    galleryGrid.innerHTML = '<p class="no-gallery">Tidak ada foto galeri.</p>';
                    return;
                }

                galleryItemsData = data.map(item => {
                    if (typeof item === 'string') {
                        return { src: item, thumb: item };
                    }
                    return { src: item.src, thumb: item.thumb || item.src };
                });

                galleryGrid.innerHTML = '';
                galleryItemsData.forEach((item, index) => {
                    const card = document.createElement('div');
                    card.className = 'gallery-item glass-panel';
                    card.setAttribute('data-index', index);

                    card.innerHTML = `
                        <div class="gallery-image-wrapper">
                            <img src="${item.thumb}" alt="Gallery photo ${index + 1}" class="gallery-image" loading="lazy">
                        </div>
                    `;

                    card.addEventListener('click', function() {
                        openModal(index);
                    });

                    galleryGrid.appendChild(card);
                });
            })
            .catch(err => {
                console.error('Error fetching gallery:', err);
                galleryGrid.innerHTML = '<p class="error">Gagal memuat galeri.</p>';
            });

        initGalleryModalControls();
    }

    function initGalleryModalControls() {
        if (modalClose) {
            modalClose.addEventListener('click', closeModal);
        }

        if (modalPrev) {
            modalPrev.addEventListener('click', showPrevImage);
        }

        if (modalNext) {
            modalNext.addEventListener('click', showNextImage);
        }

        if (galleryModal) {
            galleryModal.addEventListener('click', function(e) {
                if (e.target === galleryModal) {
                    closeModal();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (!galleryModal || galleryModal.classList.contains('hidden')) return;

            if (e.key === 'Escape') {
                closeModal();
            } else if (e.key === 'ArrowLeft') {
                showPrevImage();
            } else if (e.key === 'ArrowRight') {
                showNextImage();
            }
        });
    }

    function openModal(index) {
        if (!galleryModal || !modalImage || index < 0 || index >= galleryItemsData.length) return;

        currentGalleryIndex = index;
        const item = galleryItemsData[index];

        modalImage.src = item.src;
        modalImage.alt = `Gallery photo ${index + 1}`;

        if (modalCaption) {
            modalCaption.textContent = `Foto ${index + 1} dari ${galleryItemsData.length}`;
            modalCaption.style.display = 'block';
        }

        galleryModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!galleryModal) return;

        galleryModal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function showPrevImage() {
        if (galleryItemsData.length === 0) return;
        const prevIndex = (currentGalleryIndex - 1 + galleryItemsData.length) % galleryItemsData.length;
        openModal(prevIndex);
    }

    function showNextImage() {
        if (galleryItemsData.length === 0) return;
        const nextIndex = (currentGalleryIndex + 1) % galleryItemsData.length;
        openModal(nextIndex);
    }

    /**
     * RSVP Form
     */
    function initRSVP() {
        if (!rsvpForm) return;

        rsvpForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(rsvpForm);
            const msgEl = document.getElementById('formMessage');
            if (msgEl) {
                msgEl.textContent = 'Mengirim...';
            }

            fetch('save.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (msgEl) {
                        msgEl.textContent = data.message || 'Terima kasih! RSVP Anda telah terkirim.';
                        msgEl.className = 'form-message success';
                    }
                    rsvpForm.reset();
                    showToast('RSVP Berhasil Terkirim!');
                } else {
                    if (msgEl) {
                        msgEl.textContent = data.message || 'Gagal mengirim RSVP.';
                        msgEl.className = 'form-message error';
                    }
                    showToast(data.message || 'Gagal mengirim RSVP');
                }
            })
            .catch(() => {
                if (msgEl) {
                    msgEl.textContent = 'Terjadi kesalahan koneksi.';
                    msgEl.className = 'form-message error';
                }
                showToast('Terjadi kesalahan koneksi');
            });
        });
    }

    /**
     * Copy to Clipboard
     */
    function initCopyButtons() {
        const copyButtons = document.querySelectorAll('.btn-copy, [data-copy-target]');
        copyButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                let textToCopy = '';
                const targetId = this.dataset.copyTarget;
                const targetEl = targetId ? document.getElementById(targetId) : null;
                
                if (targetEl) {
                    textToCopy = targetEl.textContent.trim();
                } else if (this.dataset.account) {
                    textToCopy = this.dataset.account.trim();
                }

                if (!textToCopy) return;

                navigator.clipboard.writeText(textToCopy).then(() => {
                    showToast('Berhasil disalin!');

                    const originalText = this.textContent;
                    this.textContent = '✓ Disalin!';
                    this.classList.add('copied');

                    setTimeout(() => {
                        this.textContent = originalText;
                        this.classList.remove('copied');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy:', err);
                    showToast('Gagal menyalin');
                });
            });
        });
    }

    /**
     * Scroll Effects
     */
    function initScrollEffects() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href && href !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });
    }

    /**
     * Toast Notifications
     */
    function showToast(message) {
        if (!toast) return;

        const msgSpan = toastMessage || toast.querySelector('.toast-message') || toast;
        msgSpan.textContent = message;
        toast.classList.remove('hidden');

        setTimeout(() => {
            toast.classList.add('hidden');
        }, 3000);
    }

    /**
     * Helper: Get config value
     */
    function getConfigValue(key) {
        if (window.WeddingConfig) {
            if (window.WeddingConfig[key]) return window.WeddingConfig[key];
            if (window.WeddingConfig.schedule && window.WeddingConfig.schedule[key]) return window.WeddingConfig.schedule[key];
            if (window.WeddingConfig.wedding && window.WeddingConfig.wedding[key]) return window.WeddingConfig.wedding[key];
        }

        if (window.invitationConfig && window.invitationConfig[key]) {
            return window.invitationConfig[key];
        }
        
        const meta = document.querySelector(`meta[name="config-${key}"]`);
        if (meta) {
            return meta.content;
        }
        
        return '';
    }

})();
