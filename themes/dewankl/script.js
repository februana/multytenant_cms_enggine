/**
 * DewanaKL Theme - JavaScript
 * Adapted from https://github.com/dewanakl/undangan
 */

(function() {
    'use strict';
    
    // Initialize AOS Animation
    function initAOS() {
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 1000,
                once: true,
                offset: 100
            });
        }
    }
    
    // Countdown Timer
    function initCountdown() {
        const countdownEl = document.getElementById('countdown');
        if (!countdownEl) return;
        
        const targetDate = countdownEl.getAttribute('data-countdown');
        if (!targetDate) return;
        
        const target = new Date(targetDate).getTime();
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = target - now;
            
            if (distance < 0) {
                document.getElementById('days').textContent = '0';
                document.getElementById('hours').textContent = '0';
                document.getElementById('minutes').textContent = '0';
                document.getElementById('seconds').textContent = '0';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('days').textContent = days;
            document.getElementById('hours').textContent = String(hours).padStart(2, '0');
            document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
            document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
    
    // Music Control
    function initMusic() {
        const musicBtn = document.getElementById('button-music');
        const audio = document.getElementById('backgroundMusic');
        
        if (!musicBtn || !audio) return;
        
        let isPlaying = false;
        
        musicBtn.addEventListener('click', function() {
            if (isPlaying) {
                audio.pause();
                musicBtn.innerHTML = '<i class="fa-solid fa-circle-play"></i>';
            } else {
                audio.play();
                musicBtn.innerHTML = '<i class="fa-solid fa-circle-pause spin-button"></i>';
            }
            isPlaying = !isPlaying;
        });
        
        // Auto play when invitation opened
        window.addEventListener('invitationOpened', function() {
            audio.play().then(function() {
                isPlaying = true;
                musicBtn.classList.remove('d-none');
            }).catch(function(e) {
                console.log('Auto play prevented:', e);
            });
        });
    }
    
    // Open Invitation
    function initOpenInvitation() {
        const openBtn = document.getElementById('openInvitationBtn');
        const welcomeEl = document.getElementById('welcome');
        const loadingEl = document.getElementById('loading');
        const rootEl = document.getElementById('root');
        
        if (!openBtn) return;
        
        openBtn.addEventListener('click', function() {
            // Fade out welcome page
            if (welcomeEl) {
                welcomeEl.style.transition = 'opacity 0.5s ease';
                welcomeEl.style.opacity = '0';
                setTimeout(function() {
                    welcomeEl.style.display = 'none';
                }, 500);
            }
            
            // Show root content
            if (rootEl) {
                rootEl.classList.remove('opacity-0');
                rootEl.classList.add('opacity-100');
            }
            
            // Trigger custom event for music
            window.dispatchEvent(new Event('invitationOpened'));
            
            // Refresh AOS after opening
            setTimeout(function() {
                if (typeof AOS !== 'undefined') {
                    AOS.refresh();
                }
            }, 100);
        });
        
        function hideLoading() {
            if (loadingEl) {
                loadingEl.style.transition = 'opacity 0.5s ease';
                loadingEl.style.opacity = '0';
                setTimeout(function() {
                    loadingEl.style.display = 'none';
                }, 500);
            }
            if (welcomeEl) {
                welcomeEl.style.transition = 'opacity 0.5s ease';
                welcomeEl.style.opacity = '1';
            }
        }

        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            hideLoading();
        } else {
            window.addEventListener('load', hideLoading);
            document.addEventListener('DOMContentLoaded', hideLoading);
        }
    }
    
    // Copy to Clipboard
    function initCopyButtons() {
        document.addEventListener('click', function(e) {
            const copyBtn = e.target.closest('.amplop-copy-btn');
            if (!copyBtn) return;
            
            const account = copyBtn.getAttribute('data-account');
            if (!account) return;
            
            navigator.clipboard.writeText(account).then(function() {
                // Show feedback
                const feedback = copyBtn.parentElement.querySelector('.amplop-feedback');
                if (feedback) {
                    feedback.classList.add('show');
                    setTimeout(function() {
                        feedback.classList.remove('show');
                    }, 2000);
                }
            }).catch(function(err) {
                console.error('Failed to copy:', err);
            });
        });
    }
    
    // Gallery Lightbox
    function initGallery() {
        const modalEl = document.getElementById('modal-image');
        if (!modalEl) return;
        
        const modalImg = document.getElementById('show-modal-image');
        const modalLink = document.getElementById('button-modal-click');
        
        // Initialize Bootstrap modal
        const modal = new bootstrap.Modal(modalEl);
        
        document.addEventListener('click', function(e) {
            const img = e.target.closest('img.cursor-pointer');
            if (!img) return;
            
            const src = img.src || img.getAttribute('data-src');
            if (!src) return;
            
            modalImg.src = src;
            if (modalLink) {
                modalLink.href = src;
            }
            modal.show();
        });
    }
    
    // Guest Name Display
    function initGuestName() {
        const guestNameEl = document.getElementById('guest-name');
        if (!guestNameEl) return;
        
        const message = guestNameEl.getAttribute('data-message');
        const urlParams = new URLSearchParams(window.location.search);
        const guestName = urlParams.get('to') || urlParams.get('name');
        
        if (guestName) {
            guestNameEl.innerHTML = '<h3 class="my-2">' + decodeURIComponent(guestName) + '</h3>';
        } else if (message) {
            guestNameEl.innerHTML = '<h3 class="my-2">' + message + '</h3>';
        }
    }
    
    // Form Submission
    function initRSVPForm() {
        const form = document.getElementById('rsvpForm');
        if (!form) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const messageEl = document.getElementById('formMessage');
            
            // Get CSRF token
            const csrfToken = document.getElementById('csrfToken');
            if (csrfToken && !csrfToken.value) {
                // Fetch CSRF token from save.php endpoint if not set
                fetch('save.php?get_csrf=1')
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.csrf_token) {
                            csrfToken.value = data.csrf_token;
                            formData.set('csrf_token', data.csrf_token);
                        }
                        submitForm(formData, messageEl);
                    })
                    .catch(function() {
                        submitForm(formData, messageEl);
                    });
            } else {
                submitForm(formData, messageEl);
            }
        });
        
        function submitForm(formData, messageEl) {
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Mengirim...';
            }
            
            fetch('save.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (messageEl) {
                        messageEl.textContent = data.message || 'Terima kasih! RSVP Anda telah terkirim.';
                        messageEl.className = 'form-message text-center mt-3 text-success';
                    }
                    form.reset();
                } else {
                    if (messageEl) {
                        messageEl.textContent = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
                        messageEl.className = 'form-message text-center mt-3 text-danger';
                    }
                }
            })
            .catch(function() {
                if (messageEl) {
                    messageEl.textContent = 'Terjadi kesalahan koneksi. Silakan coba lagi.';
                    messageEl.className = 'form-message text-center mt-3 text-danger';
                }
            })
            .finally(function() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        }
    }
    
    // Smooth Scroll for Bottom Navbar
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
    
    // Initialize all
    function init() {
        initAOS();
        initCountdown();
        initMusic();
        initOpenInvitation();
        initCopyButtons();
        initGallery();
        initGuestName();
        initRSVPForm();
        initSmoothScroll();
    }
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
