(function () {
    'use strict';

    var root = document.getElementById('cms-parang-root');
    if (!root) return;

    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, { once: true });
        else fn();
    }

    function initCountdown() {
        var targetValue = document.body.getAttribute('data-countdown-target') || '';
        var days = document.getElementById('parang-days');
        var hours = document.getElementById('parang-hours');
        var minutes = document.getElementById('parang-minutes');
        var seconds = document.getElementById('parang-seconds');
        if (!targetValue || !days || !hours || !minutes || !seconds) return;

        function pad(value) { return String(value).padStart(2, '0'); }
        function update() {
            var distance = new Date(targetValue).getTime() - Date.now();
            if (!Number.isFinite(distance) || distance < 0) distance = 0;
            var totalSeconds = Math.floor(distance / 1000);
            days.textContent = pad(Math.floor(totalSeconds / 86400));
            hours.textContent = pad(Math.floor((totalSeconds % 86400) / 3600));
            minutes.textContent = pad(Math.floor((totalSeconds % 3600) / 60));
            seconds.textContent = pad(totalSeconds % 60);
        }
        update();
        window.setInterval(update, 1000);
    }

    function initMobileMenu() {
        var trigger = document.getElementById('parang-mobile-menu');
        var source = document.querySelector('.parang-desktop-nav');
        if (!trigger || !source) return;
        var drawer = document.createElement('div');
        drawer.className = 'parang-mobile-drawer';
        drawer.setAttribute('aria-hidden', 'true');
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'parang-mobile-drawer-close';
        close.setAttribute('aria-label', 'Tutup navigasi');
        close.innerHTML = '<span class="parang-icon" aria-hidden="true">close</span>';
        drawer.appendChild(close);
        source.querySelectorAll('.parang-nav-link').forEach(function (link) {
            var copy = link.cloneNode(true);
            copy.addEventListener('click', closeDrawer);
            drawer.appendChild(copy);
        });
        root.appendChild(drawer);

        function closeDrawer() {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
        }
        trigger.addEventListener('click', function () {
            var open = drawer.classList.toggle('is-open');
            drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
        });
        close.addEventListener('click', closeDrawer);
    }

    function initMusic() {
        var toggle = document.getElementById('parang-music-toggle');
        var audio = document.getElementById('parang-background-music');
        if (!toggle || !audio) return;
        toggle.addEventListener('click', function () {
            if (audio.paused) {
                audio.play().then(function () { toggle.querySelector('.parang-icon').textContent = 'pause'; }).catch(function () {});
            } else {
                audio.pause();
                toggle.querySelector('.parang-icon').textContent = 'music_note';
            }
        });
    }

    function initCopyButtons() {
        root.querySelectorAll('[data-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var value = button.getAttribute('data-copy') || '';
                if (!value) return;
                var done = function () {
                    var original = button.textContent;
                    button.textContent = 'Berhasil disalin';
                    window.setTimeout(function () { button.textContent = original; }, 1600);
                };
                if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(value).then(done).catch(function () {});
                else {
                    var input = document.createElement('textarea');
                    input.value = value;
                    document.body.appendChild(input);
                    input.select();
                    try { document.execCommand('copy'); done(); } catch (e) {}
                    input.remove();
                }
            });
        });
    }

    function initRsvp() {
        var form = document.getElementById('parang-rsvp-form');
        var message = document.getElementById('parang-form-message');
        if (!form || !message) return;
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            message.textContent = 'Mengirim konfirmasi...';
            fetch('save.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    message.textContent = data.message || (data.success ? 'Terima kasih.' : 'Gagal mengirim konfirmasi.');
                    if (data.success) form.reset();
                })
                .catch(function () { message.textContent = 'Gagal mengirim konfirmasi. Silakan coba lagi.'; });
        });
    }

    function initReveal() {
        var elements = root.querySelectorAll('.parang-reveal');
        if (!('IntersectionObserver' in window)) {
            elements.forEach(function (element) { element.classList.add('is-visible'); });
            return;
        }
        var observer = new IntersectionObserver(function (entries, current) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                current.unobserve(entry.target);
            });
        }, { threshold: 0.12 });
        elements.forEach(function (element) { observer.observe(element); });
    }

    function initActiveNavigation() {
        var links = Array.prototype.slice.call(root.querySelectorAll('.parang-nav-link'));
        var sections = links.map(function (link) { return document.querySelector(link.getAttribute('href')); }).filter(Boolean);
        if (!('IntersectionObserver' in window) || !sections.length) return;
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                links.forEach(function (link) { link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id); });
            });
        }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });
        sections.forEach(function (section) { observer.observe(section); });
    }

    ready(function () {
        initCountdown();
        initMobileMenu();
        initMusic();
        initCopyButtons();
        initRsvp();
        initReveal();
        initActiveNavigation();
    });
}());
