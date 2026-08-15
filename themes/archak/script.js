/**
 * Archak Theme - JavaScript
 */
(function() {
    'use strict';

    // Preloader
    function hidePreloader() {
        const preloader = document.getElementById('preloader');
        if (preloader) {
            setTimeout(function() {
                preloader.classList.add('hidden');
            }, 500);
        }
    }

    if (document.readyState === 'complete') {
        hidePreloader();
    } else {
        window.addEventListener('load', hidePreloader);
    }

    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // Mobile nav toggle
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
        navMenu.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
            });
        });
    }

    // Scroll animations
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('[data-fade-up], [data-slide-left], [data-slide-right], [data-zoom], [data-flip]');
        elements.forEach(function(el) {
            const rect = el.getBoundingClientRect();
            const triggerPoint = window.innerHeight * 0.8;
            if (rect.top < triggerPoint) {
                el.classList.add('visible');
            }
        });
    };
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll();

    // Countdown
    const countdownTarget = document.querySelector('[data-countdown-target]');
    if (countdownTarget) {
        const targetDate = new Date(countdownTarget.getAttribute('data-countdown-target')).getTime();
        const updateCountdown = function() {
            const now = new Date().getTime();
            const distance = targetDate - now;
            if (distance > 0) {
                document.getElementById('cd-days').textContent = Math.floor(distance / (1000 * 60 * 60 * 24));
                document.getElementById('cd-hours').textContent = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                document.getElementById('cd-minutes').textContent = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                document.getElementById('cd-seconds').textContent = Math.floor((distance % (1000 * 60)) / 1000);
            }
        };
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    // Gallery lightbox
    const galleryModal = document.getElementById('galleryModal');
    const modalImg = document.getElementById('lightboxImg');
    const modalCaption = document.getElementById('lightboxCaption');
    if (galleryModal && modalImg) {
        const closeBtn = galleryModal.querySelector('.lightbox-close');
        const prevBtn = galleryModal.querySelector('.lightbox-prev');
        const nextBtn = galleryModal.querySelector('.lightbox-next');
        let images = [];
        let currentIndex = 0;

        document.querySelectorAll('.gallery-masonry img').forEach(function(img) {
            images.push({ src: img.src, alt: img.alt });
            img.addEventListener('click', function() {
                currentIndex = images.findIndex(function(i) { return i.src === img.src; });
                modalImg.src = this.src;
                modalCaption.textContent = this.alt || '';
                galleryModal.classList.add('active');
            });
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                galleryModal.classList.remove('active');
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                modalImg.src = images[currentIndex].src;
                modalCaption.textContent = images[currentIndex].alt || '';
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                currentIndex = (currentIndex + 1) % images.length;
                modalImg.src = images[currentIndex].src;
                modalCaption.textContent = images[currentIndex].alt || '';
            });
        }

        galleryModal.addEventListener('click', function(e) {
            if (e.target === galleryModal) {
                galleryModal.classList.remove('active');
            }
        });
    }

    // Copy account number
    document.querySelectorAll('.btn-copy-account').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const account = this.getAttribute('data-account');
            navigator.clipboard.writeText(account).then(function() {
                const successMsg = this.nextElementSibling;
                if (successMsg && successMsg.classList.contains('copy-success')) {
                    successMsg.style.display = 'block';
                    setTimeout(function() {
                        successMsg.style.display = 'none';
                    }, 2000);
                }
            }.bind(this));
        });
    });

    // RSVP Form
    const rsvpForm = document.getElementById('rsvpForm');
    if (rsvpForm) {
        rsvpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const messageEl = document.getElementById('formMessage');
            
            fetch('api/rsvp.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    messageEl.textContent = 'Thank you! Your RSVP has been submitted.';
                    messageEl.style.color = '#28a745';
                    rsvpForm.reset();
                } else {
                    messageEl.textContent = data.message || 'Submission failed. Please try again.';
                    messageEl.style.color = '#dc3545';
                }
            })
            .catch(function() {
                messageEl.textContent = 'An error occurred. Please try again.';
                messageEl.style.color = '#dc3545';
            });
        });
    }

    // Music control
    const musicControl = document.getElementById('music-control');
    const bgMusic = document.getElementById('bg-music');
    if (musicControl && bgMusic) {
        const playIcon = musicControl.querySelector('.icon-play');
        const pauseIcon = musicControl.querySelector('.icon-pause');
        let isPlaying = false;

        musicControl.addEventListener('click', function() {
            if (isPlaying) {
                bgMusic.pause();
                playIcon.style.display = 'block';
                pauseIcon.style.display = 'none';
            } else {
                bgMusic.play();
                playIcon.style.display = 'none';
                pauseIcon.style.display = 'block';
            }
            isPlaying = !isPlaying;
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });

})();
