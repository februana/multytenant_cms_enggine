/**
 * Rainier Theme - JavaScript Interactions
 * 
 * Handles:
 * - Welcome overlay & invitation opening
 * - Music control
 * - Countdown timer
 * - Navigation (mobile toggle, scroll behavior)
 * - Gallery modal
 * - RSVP form submission
 * - Copy to clipboard
 * - Toast notifications
 * - Scroll animations
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
    const musicControl = document.getElementById('music-control');
    const musicToggle = document.getElementById('music-toggle');
    const backgroundMusic = document.getElementById('background-music');
    const galleryModal = document.getElementById('gallery-modal');
    const modalImage = document.querySelector('.modal-image');
    const modalCaption = document.querySelector('.modal-caption');
    const modalClose = document.querySelector('.modal-close');
    const modalPrev = document.querySelector('.modal-prev');
    const modalNext = document.querySelector('.modal-next');
    const rsvpForm = document.getElementById('rsvp-form');
    const rsvpSuccess = document.getElementById('rsvp-success');
    const toast = document.getElementById('toast');
    const toastMessage = document.querySelector('.toast-message');
    const copyButtons = document.querySelectorAll('[data-copy-target]');
    const countdownDayEl = document.getElementById('hero-countdown-day');

    function hideLoadingScreen() {
        if (loadingScreen) {
            loadingScreen.classList.add('hidden');
            setTimeout(function() {
                loadingScreen.style.display = 'none';
            }, 500);
        }
    }

    // State
    let currentGalleryIndex = 0;
    let galleryItems = [];
    let isMusicPlaying = false;

    /**
     * Initialize everything when DOM is ready
     */
    function init() {
        initWelcomeOverlay();
        initNavbar();
        initCountdown();
        initGallery();
        initRSVP();
        initCopyButtons();
        initScrollEffects();
        
        setTimeout(collectGalleryItems, 500);
    }

    if (document.readyState === 'complete') {
        hideLoadingScreen();
    } else {
        window.addEventListener('load', hideLoadingScreen);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /**
     * Welcome Overlay & Invitation Opening
     */
    function initWelcomeOverlay() {
        if (!openInvitationBtn || !welcomeOverlay || !mainContent) return;

        openInvitationBtn.addEventListener('click', function() {
            // Hide welcome overlay
            welcomeOverlay.classList.add('hidden');
            
            // Show main content
            mainContent.classList.remove('hidden');
            
            // Enable body scroll
            document.body.classList.remove('hidden');
            
            // Start music if enabled
            if (backgroundMusic && musicControl) {
                musicControl.classList.remove('hidden');
                playMusic();
            }
            
            // Update URL hash
            history.pushState(null, null, '#home');
        });
    }

    /**
     * Music Control
     */
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

    function toggleMusic() {
        if (isMusicPlaying) {
            pauseMusic();
        } else {
            playMusic();
        }
    }

    function updateMusicIcon() {
        if (!musicToggle) return;
        
        if (isMusicPlaying) {
            musicToggle.innerHTML = '⏸️';
            musicToggle.classList.add('playing');
        } else {
            musicToggle.innerHTML = '🎵';
            musicToggle.classList.remove('playing');
        }
    }

    if (musicToggle) {
        musicToggle.addEventListener('click', toggleMusic);
    }

    /**
     * Navbar Behavior
     */
    function initNavbar() {
        if (!navbar || !navbarToggle || !navbarMenu) return;

        // Mobile menu toggle
        navbarToggle.addEventListener('click', function() {
            navbarMenu.classList.toggle('active');
            navbarToggle.classList.toggle('active');
        });

        // Close mobile menu on link click
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navbarMenu.classList.remove('active');
                navbarToggle.classList.remove('active');
            });
        });

        // Navbar scroll effect
        let lastScrollY = window.scrollY;
        
        window.addEventListener('scroll', function() {
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            lastScrollY = currentScrollY;
        });
    }

    /**
     * Countdown Timer
     */
    function initCountdown() {
        if (!countdownDayEl) return;

        // Get countdown target from data attribute or config
        const countdownTarget = document.body.dataset.countdownTarget || getConfigValue('countdown_target');
        
        if (!countdownTarget) {
            countdownDayEl.textContent = '00';
            return;
        }

        function updateCountdown() {
            const now = new Date().getTime();
            const target = new Date(countdownTarget).getTime();
            const distance = target - now;

            if (distance < 0) {
                countdownDayEl.textContent = '00';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            countdownDayEl.textContent = String(days).padStart(2, '0');
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    /**
     * Gallery Modal
     */
    function collectGalleryItems() {
        galleryItems = document.querySelectorAll('.gallery-item');
        
        galleryItems.forEach((item, index) => {
            item.addEventListener('click', function() {
                openModal(index);
            });
        });
    }

    function openModal(index) {
        if (!galleryModal || !modalImage || index >= galleryItems.length) return;

        currentGalleryIndex = index;
        const item = galleryItems[index];
        const img = item.querySelector('.gallery-image');
        const caption = item.querySelector('.gallery-caption');

        if (img) {
            modalImage.src = img.src;
            modalImage.alt = img.alt || '';
        }

        if (caption) {
            modalCaption.textContent = caption.textContent;
            modalCaption.style.display = 'block';
        } else {
            modalCaption.style.display = 'none';
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
        if (currentGalleryIndex > 0) {
            openModal(currentGalleryIndex - 1);
        } else {
            openModal(galleryItems.length - 1);
        }
    }

    function showNextImage() {
        if (currentGalleryIndex < galleryItems.length - 1) {
            openModal(currentGalleryIndex + 1);
        } else {
            openModal(0);
        }
    }

    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }

    if (modalPrev) {
        modalPrev.addEventListener('click', showPrevImage);
    }

    if (modalNext) {
        modalNext.addEventListener('click', showNextImage);
    }

    // Close modal on background click
    if (galleryModal) {
        galleryModal.addEventListener('click', function(e) {
            if (e.target === galleryModal) {
                closeModal();
            }
        });
    }

    // Keyboard navigation
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

    /**
     * RSVP Form
     */
    function initRSVP() {
        if (!rsvpForm) return;

        rsvpForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(rsvpForm);
            const data = Object.fromEntries(formData.entries());

            // Validate
            if (!data.name || !data.guests || !data.status) {
                showToast('Mohon lengkapi semua field!');
                return;
            }

            // Build WhatsApp message
            const message = buildRSVPMessage(data);
            
            // Open WhatsApp
            const whatsappNumber = getConfigValue('whatsapp_number') || '';
            const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;
            
            window.open(whatsappUrl, '_blank');

            // Show success
            rsvpForm.classList.add('hidden');
            if (rsvpSuccess) {
                rsvpSuccess.classList.remove('hidden');
            }

            showToast('Konfirmasi akan dikirim ke WhatsApp');
        });
    }

    function buildRSVPMessage(data) {
        const config = window.invitationConfig || {};
        const coupleNames = `${config.bride_nickname || ''} & ${config.groom_nickname || ''}`;
        
        return `*Konfirmasi Kehadiran*%0A%0A` +
               `Kepada Yth.${'%0A'}${coupleNames}%0A%0A` +
               `Dengan ini saya menyatakan:%0A%0A` +
               `Nama: ${data.name}%0A` +
               `Jumlah Tamu: ${data.guests} orang%0A` +
               `Status: ${data.status}%0A` +
               `${data.message ? `Ucapan: ${data.message}` : ''}%0A%0A` +
               `Terima kasih.`;
    }

    /**
     * Copy to Clipboard
     */
    function initCopyButtons() {
        copyButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.dataset.copyTarget;
                const targetEl = document.getElementById(targetId);
                
                if (targetEl) {
                    const textToCopy = targetEl.textContent.trim();
                    
                    navigator.clipboard.writeText(textToCopy).then(() => {
                        showToast('Berhasil disalin!');
                        
                        // Visual feedback
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
                }
            });
        });
    }

    /**
     * Scroll Effects
     */
    function initScrollEffects() {
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
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
        if (!toast || !toastMessage) return;

        toastMessage.textContent = message;
        toast.classList.remove('hidden');

        setTimeout(() => {
            toast.classList.add('hidden');
        }, 3000);
    }

    /**
     * Helper: Get config value
     */
    function getConfigValue(key) {
        // Try to get from global config object
        if (window.invitationConfig && window.invitationConfig[key]) {
            return window.invitationConfig[key];
        }
        
        // Try to get from meta tag
        const meta = document.querySelector(`meta[name="config-${key}"]`);
        if (meta) {
            return meta.content;
        }
        
        return '';
    }

})();
