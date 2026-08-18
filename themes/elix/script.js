/**
 * Elix Theme - JavaScript Functionality
 * 
 * Adapted from: https://github.com/elix-stack/wedding-invitation-1
 */

(function() {
    'use strict';
    
    // ============================================
    // Configuration & State
    // ============================================
    let isMusicPlaying = false;
    let isLoading = true;
    let isInvitationOpened = false;
    
    // ============================================
    // DOM Elements
    // ============================================
    const loadingOverlay = document.getElementById('loadingOverlay');
    const welcomeOverlay = document.getElementById('welcomeOverlay');
    const mainContent = document.getElementById('mainContent');
    const musicControl = document.getElementById('musicControl');
    const musicBtn = document.getElementById('musicBtn');
    const audio = document.getElementById('audio');
    const navbar = document.querySelector('.navbar');
    const rsvpForm = document.getElementById('rsvpForm');
    const rsvpSuccess = document.getElementById('rsvpSuccess');
    const loadGalleryBtn = document.getElementById('loadGalleryBtn');
    const galleryGrid = document.getElementById('galleryGrid');
    
    // ============================================
    // Initialization
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize AOS Animation
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                offset: 100
            });
        }
        
        // Hide loading overlay
        setTimeout(function() {
            if (loadingOverlay) {
                loadingOverlay.classList.add('hidden');
                isLoading = false;
            }
        }, 1500);
        
        // Setup guest name from URL
        setupGuestName();
        
        // Setup scroll listeners
        setupScrollListeners();
        
        // Setup form handlers
        setupFormHandlers();
        
        // Check for gallery data
        checkGalleryData();
    });
    
    // ============================================
    // Guest Name Setup
    // ============================================
    function setupGuestName() {
        const urlParams = new URLSearchParams(window.location.search);
        const guestName = (urlParams.get('to') || urlParams.get('guest') || urlParams.get('name') || '').trim();
        
        if (guestName) {
            const guestNameDisplay = document.getElementById('guestNameDisplay');
            if (guestNameDisplay) {
                guestNameDisplay.textContent = guestName;
            }
            
            // Also set form field if exists
            const guestNameInput = document.getElementById('guestName');
            if (guestNameInput) {
                guestNameInput.value = guestName;
            }
        }
    }
    
    // ============================================
    // Scroll Listeners
    // ============================================
    function setupScrollListeners() {
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            if (navbar) {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
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
                        
                        // Close mobile menu if open
                        const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('navbarNav'));
                        if (offcanvas) {
                            offcanvas.hide();
                        }
                    }
                }
            });
        });
    }
    
    // ============================================
    // Open Invitation
    // ============================================
    function openInvitation() {
        if (isInvitationOpened) return;
        
        isInvitationOpened = true;
        
        // Slide up welcome overlay
        if (welcomeOverlay) {
            welcomeOverlay.classList.add('opened');
        }
        
        // Show main content
        if (mainContent) {
            mainContent.style.display = 'block';
            setTimeout(function() {
                mainContent.style.opacity = '1';
            }, 100);
        }
        
        // Show music control if enabled
        if (musicControl) {
            musicControl.style.display = 'block';
        }
        
        // Play music if enabled
        playMusic();
        
        // Trigger confetti if available
        triggerConfetti();
        
        // Refresh AOS after layout change
        if (typeof AOS !== 'undefined') {
            setTimeout(function() {
                AOS.refresh();
            }, 300);
        }
    }
    
    // Make openInvitation globally available
    window.openInvitation = openInvitation;
    
    // ============================================
    // Music Control
    // ============================================
    function playMusic() {
        if (!audio) return;
        
        audio.play().then(function() {
            isMusicPlaying = true;
            if (musicBtn) {
                musicBtn.classList.add('playing');
            }
        }).catch(function(error) {
            console.log('Auto-play prevented:', error);
            isMusicPlaying = false;
        });
    }
    
    function toggleMusic() {
        if (!audio || !musicBtn) return;
        
        if (isMusicPlaying) {
            audio.pause();
            musicBtn.classList.remove('playing');
            isMusicPlaying = false;
        } else {
            audio.play();
            musicBtn.classList.add('playing');
            isMusicPlaying = true;
        }
    }
    
    // Make toggleMusic globally available
    window.toggleMusic = toggleMusic;
    
    // ============================================
    // Confetti Effect
    // ============================================
    function triggerConfetti() {
        if (typeof confetti === 'function') {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#d4a574', '#c9a962', '#ffffff', '#f0e6d2']
            });
        }
    }
    
    // ============================================
    // Countdown Timer
    // ============================================
    function initCountdown() {
        const countdownTarget = document.body.getAttribute('data-time');
        if (!countdownTarget) return;
        
        const targetDate = new Date(countdownTarget).getTime();
        
        const countdownInterval = setInterval(function() {
            const now = new Date().getTime();
            const distance = targetDate - now;
            
            if (distance < 0) {
                clearInterval(countdownInterval);
                document.getElementById('days').textContent = '00';
                document.getElementById('hours').textContent = '00';
                document.getElementById('minutes').textContent = '00';
                document.getElementById('seconds').textContent = '00';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('days').textContent = String(days).padStart(2, '0');
            document.getElementById('hours').textContent = String(hours).padStart(2, '0');
            document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
            document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
        }, 1000);
    }
    
    // Initialize countdown when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCountdown);
    } else {
        initCountdown();
    }
    
    // ============================================
    // Copy to Clipboard
    // ============================================
    function copyToClipboard(text, button) {
        if (!text) return;
        
        navigator.clipboard.writeText(text).then(function() {
            // Show success feedback
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check me-2"></i>Tersalin!';
            button.classList.remove('btn-outline-primary', 'btn-outline-success');
            button.classList.add('btn-success');
            
            setTimeout(function() {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
                button.classList.add(text.length > 15 ? 'btn-outline-primary' : 'btn-outline-success');
            }, 2000);
        }).catch(function(error) {
            console.error('Copy failed:', error);
            alert('Gagal menyalin. Silakan salin manual.');
        });
    }
    
    // Make copyToClipboard globally available
    window.copyToClipboard = copyToClipboard;
    
    // ============================================
    // Form Handlers
    // ============================================
    function setupFormHandlers() {
        if (!rsvpForm) return;
        
        rsvpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(rsvpForm);
            
            fetch(rsvpForm.action, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                if (response.ok) {
                    rsvpForm.style.display = 'none';
                    if (rsvpSuccess) {
                        rsvpSuccess.style.display = 'block';
                    }
                } else {
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            });
        });
    }
    
    // ============================================
    // Gallery Functions
    // ============================================
    function checkGalleryData() {
        // Check if gallery data exists
        fetch('gallery.php')
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data && data.length > 0) {
                    if (loadGalleryBtn) {
                        loadGalleryBtn.style.display = 'inline-block';
                    }
                }
            })
            .catch(function(error) {
                console.error('Gallery check error:', error);
            });
    }
    
    function loadGallery() {
        if (!galleryGrid) return;
        
        galleryGrid.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        fetch('gallery.php')
            .then(function(response) {
                return response.json();
            })
            .then(function(images) {
                if (!images || images.length === 0) {
                    galleryGrid.innerHTML = '<p class="text-center text-muted">Belum ada galeri.</p>';
                    return;
                }
                
                galleryGrid.innerHTML = '';
                
                images.forEach(function(image, index) {
                    const galleryItem = document.createElement('div');
                    galleryItem.className = 'gallery-item';
                    galleryItem.setAttribute('data-aos', 'zoom-in');
                    galleryItem.setAttribute('data-aos-delay', (index * 50).toString());
                    
                    const img = document.createElement('img');
                    img.src = image;
                    img.alt = 'Gallery image ' + (index + 1);
                    img.loading = 'lazy';
                    
                    const overlay = document.createElement('div');
                    overlay.className = 'gallery-overlay';
                    overlay.innerHTML = '<i class="fas fa-search-plus"></i>';
                    
                    galleryItem.appendChild(img);
                    galleryItem.appendChild(overlay);
                    galleryGrid.appendChild(galleryItem);
                    
                    // Add click handler for lightbox
                    galleryItem.addEventListener('click', function() {
                        openLightbox(image);
                    });
                });
                
                if (loadGalleryBtn) {
                    loadGalleryBtn.style.display = 'none';
                }
                
                // Refresh AOS
                if (typeof AOS !== 'undefined') {
                    AOS.refresh();
                }
            })
            .catch(function(error) {
                console.error('Gallery load error:', error);
                galleryGrid.innerHTML = '<p class="text-center text-muted">Gagal memuat galeri.</p>';
            });
    }
    
    // Make loadGallery globally available
    window.loadGallery = loadGallery;
    
    // ============================================
    // Lightbox Modal
    // ============================================
    function openLightbox(imageSrc) {
        let lightboxModal = document.querySelector('.lightbox-modal');
        
        if (!lightboxModal) {
            lightboxModal = document.createElement('div');
            lightboxModal.className = 'lightbox-modal';
            lightboxModal.innerHTML = `
                <span class="lightbox-close">&times;</span>
                <img class="lightbox-content" src="" alt="Full size image">
            `;
            document.body.appendChild(lightboxModal);
            
            // Add close handler
            const closeBtn = lightboxModal.querySelector('.lightbox-close');
            closeBtn.addEventListener('click', function() {
                lightboxModal.classList.remove('active');
            });
            
            lightboxModal.addEventListener('click', function(e) {
                if (e.target === lightboxModal) {
                    lightboxModal.classList.remove('active');
                }
            });
            
            // Keyboard support
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && lightboxModal.classList.contains('active')) {
                    lightboxModal.classList.remove('active');
                }
            });
        }
        
        const lightboxImg = lightboxModal.querySelector('.lightbox-content');
        lightboxImg.src = imageSrc;
        lightboxModal.classList.add('active');
    }
    
})();
